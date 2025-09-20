<?php
require_once 'common/header.php';

// --- MESSAGE HANDLING (SESSION-BASED) ---
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
} else {
    $message = '';
    $message_type = '';
}

// --- JOIN TOURNAMENT LOGIC ---
if (is_user_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_join'])) {
    $tournament_id = $_POST['tournament_id'];
    $entry_fee = $_POST['entry_fee'];
    $user_id = $current_user['id'];
    
    $in_game_name = trim($_POST['in_game_name']);
    $game_uid = trim($_POST['game_uid']);
    if (!empty($in_game_name) && !empty($game_uid)) {
        $stmt_update_user = $pdo->prepare("UPDATE users SET in_game_name = ?, game_uid = ? WHERE id = ?");
        $stmt_update_user->execute([$in_game_name, $game_uid, $user_id]);
    }

    $stmt_balance = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt_balance->execute([$user_id]);
    $user_balance = $stmt_balance->fetchColumn();

    if ($user_balance < $entry_fee) {
        $_SESSION['message'] = 'Insufficient wallet balance.';
        $_SESSION['message_type'] = 'error';
        header("Location: wallet.php");
        exit();
    } else {
        $stmt_check = $pdo->prepare("SELECT id FROM participants WHERE user_id = ? AND tournament_id = ?");
        $stmt_check->execute([$user_id, $tournament_id]);
        if ($stmt_check->rowCount() > 0) {
            $_SESSION['message'] = 'You have already joined this tournament.';
            $_SESSION['message_type'] = 'error';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt_wallet = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
                $stmt_wallet->execute([$entry_fee, $user_id]);
                $stmt_participant = $pdo->prepare("INSERT INTO participants (user_id, tournament_id) VALUES (?, ?)");
                $stmt_participant->execute([$user_id, $tournament_id]);
                $desc = "Entry fee for tournament #$tournament_id";
                $stmt_trans = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'debit', ?)");
                $stmt_trans->execute([$user_id, $entry_fee, $desc]);
                $pdo->commit();
                $_SESSION['message'] = 'Successfully joined the tournament!';
                $_SESSION['message_type'] = 'success';
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['message'] = 'An error occurred. Please try again.';
                $_SESSION['message_type'] = 'error';
            }
        }
    }
    header("Location: index.php");
    exit();
}

// Safely get the list of joined tournaments only if the user is logged in
$joined_tournaments = [];
if (is_user_logged_in()) {
    $stmt_joined = $pdo->prepare("SELECT tournament_id FROM participants WHERE user_id = ?");
    $stmt_joined->execute([$current_user['id']]);
    $joined_tournaments = $stmt_joined->fetchAll(PDO::FETCH_COLUMN);
}

// Fetch all upcoming tournaments
$tournaments = $pdo->query("SELECT * FROM tournaments WHERE status = 'Upcoming' ORDER BY match_time ASC")->fetchAll();
?>

<?php if ($message): ?>
<div class="p-3 mb-4 rounded-md text-center text-white <?php echo $message_type === 'success' ? 'bg-green-500' : 'bg-red-500'; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<h2 class="text-2xl font-bold mb-4 text-white">Upcoming Tournaments</h2>
<div class="space-y-4">
    <?php if (count($tournaments) > 0): foreach ($tournaments as $t): ?>
    <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="p-4">
            <div class="flex justify-between items-start mb-3">
                <h3 class="text-lg font-bold text-orange-400"><?php echo htmlspecialchars($t['title']); ?></h3>
                <span class="text-xs bg-green-600 text-white font-semibold px-2 py-1 rounded-full">Upcoming</span>
            </div>
            
            <div class="grid grid-cols-3 gap-y-3 gap-x-2 text-center text-xs text-gray-300 border-t border-b border-gray-700 py-3 mb-3">
                <div class="flex flex-col items-center"><i class="fa-solid fa-map-location-dot mb-1 text-orange-400"></i><span><?php echo htmlspecialchars($t['map']); ?></span></div>
                <div class="flex flex-col items-center"><i class="fa-solid fa-person-rifle mb-1 text-orange-400"></i><span>Skill: <?php echo htmlspecialchars($t['character_skill']); ?></span></div>
                <div class="flex flex-col items-center"><i class="fa-solid fa-cubes mb-1 text-orange-400"></i><span>Ammo: <?php echo htmlspecialchars($t['gun_ammo']); ?></span></div>
            </div>

            <div class="grid grid-cols-2 gap-4 text-center text-sm">
                <div><p class="text-gray-400">Prize Pool</p><p class="font-bold text-white">₨<?php echo number_format($t['prize_pool']); ?></p></div>
                <div><p class="text-gray-400">Per Kill</p><p class="font-bold text-white">₨<?php echo number_format($t['prize_per_kill']); ?></p></div>
                <div><p class="text-gray-400">Entry Fee</p><p class="font-bold text-white">₨<?php echo number_format($t['entry_fee']); ?></p></div>
                <div><p class="text-gray-400">Match Time</p><p class="font-bold text-white"><?php echo date('d M, h:i A', strtotime($t['match_time'])); ?></p></div>
            </div>
            
            <div class="mt-4">
                <?php if (in_array($t['id'], $joined_tournaments)): ?>
                    <button class="w-full bg-green-600 text-white font-bold py-2 px-4 rounded-md cursor-not-allowed" disabled><i class="fa-solid fa-check mr-2"></i>Joined</button>
                <?php else: ?>
                    <button onclick="openJoinModal(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars($t['title'], ENT_QUOTES); ?>', <?php echo $t['entry_fee']; ?>)" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded-md">Join Now</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; else: ?>
    <p class="text-center text-gray-400">No upcoming tournaments right now. Check back later!</p>
    <?php endif; ?>
</div>

<!-- Join Confirmation Modal -->
<?php if (is_user_logged_in()): ?>
<div id="joinModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="bg-gray-800 rounded-lg p-6 w-11/12 max-w-sm">
        <h3 class="text-xl font-bold mb-2 text-orange-400">Confirm Your Entry</h3>
        <p class="text-sm text-gray-400 mb-4">You are joining <strong id="modalTournamentTitle" class="text-white"></strong>.</p>
        <form id="joinForm" method="POST" action="index.php">
            <input type="hidden" name="tournament_id" id="modalTournamentId">
            <input type="hidden" name="entry_fee" id="modalEntryFee">
            <div class="mb-4">
                <label for="modalInGameName" class="block text-sm font-medium text-gray-400 mb-1">In Game Name (IGN)</label>
                <input type="text" name="in_game_name" id="modalInGameName" value="<?php echo htmlspecialchars($current_user['in_game_name'] ?? ''); ?>" class="w-full bg-gray-700 rounded p-2 text-white" required>
            </div>
            <div class="mb-4">
                <label for="modalGameUid" class="block text-sm font-medium text-gray-400 mb-1">Game UID</label>
                <input type="text" name="game_uid" id="modalGameUid" value="<?php echo htmlspecialchars($current_user['game_uid'] ?? ''); ?>" class="w-full bg-gray-700 rounded p-2 text-white" required>
            </div>
            <div class="flex items-center gap-4">
                <button type="button" onclick="closeJoinModal()" class="w-full bg-gray-600 text-white py-2 rounded">Cancel</button>
                <button type="submit" name="confirm_join" class="w-full bg-green-600 text-white py-2 rounded">Confirm & Join</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openJoinModal(id, title, fee) {
        const ign = "<?php echo $current_user['in_game_name'] ?? '' ?>";
        const uid = "<?php echo $current_user['game_uid'] ?? '' ?>";
        if (!ign || !uid) {
            alert("Please complete your profile with your In-Game Name and Game UID before joining a match.");
            window.location.href = "profile.php";
            return;
        }
        const balance = <?php echo $current_user['wallet_balance']; ?>;
        if (balance < fee) {
            if(confirm("You have insufficient balance to join. Do you want to add money to your wallet?")) {
                window.location.href = "wallet.php";
            }
            return;
        }
        document.getElementById('modalTournamentTitle').innerText = title;
        document.getElementById('modalTournamentId').value = id;
        document.getElementById('modalEntryFee').value = fee;
        document.getElementById('joinModal').classList.remove('hidden');
    }
    function closeJoinModal() {
        document.getElementById('joinModal').classList.add('hidden');
    }
</script>
<?php endif; ?>

<?php require_once 'common/bottom.php'; ?>