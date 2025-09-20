<?php
require_once 'common/header.php';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
} else {
    $message = '';
    $message_type = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_pvp'])) {
    $entry_fee = $_POST['entry_fee'];
    $prize_pool = $entry_fee * 2 * 0.75;
    $user_balance = $current_user['wallet_balance'];

    if (empty($entry_fee) || !is_numeric($entry_fee) || $entry_fee <= 0) {
        $_SESSION['message'] = "Please enter a valid entry fee.";
        $_SESSION['message_type'] = 'error';
    } elseif ($entry_fee > $user_balance) {
        $_SESSION['message'] = "Insufficient wallet balance to create this challenge.";
        $_SESSION['message_type'] = 'error';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt_wallet = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmt_wallet->execute([$entry_fee, $current_user['id']]);
            $desc = "Entry fee for creating a PvP match.";
            $stmt_log = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'debit', ?)");
            $stmt_log->execute([$current_user['id'], $entry_fee, $desc]);
            $stmt_pvp = $pdo->prepare("INSERT INTO pvp_matches (creator_user_id, entry_fee, prize_pool, map, character_skill, gun_ammo, rounds, match_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_pvp->execute([$current_user['id'], $entry_fee, $prize_pool, $_POST['map'], $_POST['character_skill'], $_POST['gun_ammo'], $_POST['rounds'], $_POST['match_time']]);
            $pdo->commit();
            $_SESSION['message'] = "PvP challenge created successfully!";
            $_SESSION['message_type'] = 'success';
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['message'] = "An error occurred: " . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    header("Location: pvp.php");
    exit();
}

$stmt_my_pvp = $pdo->prepare("SELECT p.*, u.username as creator_username FROM pvp_matches p JOIN users u ON p.creator_user_id = u.id WHERE p.status = 'Open' AND p.creator_user_id = ? ORDER BY p.created_at DESC");
$stmt_my_pvp->execute([$current_user['id']]);
$my_challenges = $stmt_my_pvp->fetchAll();

$stmt_open_pvp = $pdo->prepare("SELECT p.*, u.username as creator_username FROM pvp_matches p JOIN users u ON p.creator_user_id = u.id WHERE p.status = 'Open' AND p.creator_user_id != ? ORDER BY p.created_at DESC");
$stmt_open_pvp->execute([$current_user['id']]);
$open_challenges = $stmt_open_pvp->fetchAll();
?>

<h2 class="text-2xl font-bold mb-6 text-white">PvP Challenge Zone</h2>
<?php if ($message): ?><div class="p-3 mb-4 rounded-md text-center text-white <?php echo $message_type === 'success' ? 'bg-green-500' : 'bg-red-500'; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<div class="bg-gray-800 p-4 rounded-lg mb-6"><h3 class="text-lg font-bold text-orange-400 mb-4">Create Your Own 1v1 Match</h3><form method="POST" action="pvp.php"><div class="grid grid-cols-2 gap-4"><input type="number" step="0.01" name="entry_fee" placeholder="Entry Fee (₨)" class="bg-gray-700 p-2 rounded col-span-2" required><select name="map" class="bg-gray-700 p-2 rounded" required><option value="Bermuda">Map: Bermuda</option><option value="Purgatory">Map: Purgatory</option><option value="Kalahari">Map: Kalahari</option></select><select name="rounds" class="bg-gray-700 p-2 rounded" required><option value="7">Rounds: 7</option><option value="13">Rounds: 13</option><option value="15">Rounds: 15</option></select><select name="gun_ammo" class="bg-gray-700 p-2 rounded" required><option value="Unlimited">Gun Ammo: Unlimited</option><option value="Limited">Gun Ammo: Limited</option></select><select name="character_skill" class="bg-gray-700 p-2 rounded" required><option value="Yes">Character Skill: Yes</option><option value="No">Character Skill: No</option></select><input type="datetime-local" name="match_time" class="bg-gray-700 p-2 rounded col-span-2" required></div><div class="text-xs text-yellow-400 mt-4 p-2 bg-yellow-900/50 rounded-md text-center">A 25% commission will be deducted from the prize pool. No refunds on cancellation.</div><button type="submit" name="create_pvp" class="w-full bg-orange-600 text-white font-bold py-2 mt-4 rounded-md">Create Challenge</button></form></div>

<div class="mb-6">
    <h3 class="text-lg font-bold text-white mb-4">My Open Challenges</h3>
    <div class="space-y-3">
        <?php if (count($my_challenges) > 0): foreach ($my_challenges as $challenge): ?>
        <div class="bg-gray-700 p-4 rounded-lg border border-orange-500/50">
            <p class="text-center text-sm text-gray-300">Your challenge is live and waiting for an opponent.</p>
            <p class="text-center text-lg font-bold text-orange-400 mt-1">Prize: ₨<?php echo number_format($challenge['prize_pool'], 2); ?></p>
        </div>
        <?php endforeach; else: ?>
        <p class="text-center text-gray-500 text-sm">You have no open challenges.</p>
        <?php endif; ?>
    </div>
</div>

<div>
    <h3 class="text-lg font-bold text-white mb-4">Challenges From Others</h3>
    <div class="space-y-3">
        <?php if (count($open_challenges) > 0): foreach ($open_challenges as $challenge): ?>
        <div class="bg-gray-800 p-4 rounded-lg">
            <div class="flex justify-between items-center mb-3">
                <div>
                    <p class="text-sm text-gray-400">Challenger: <span class="font-bold text-white"><?php echo htmlspecialchars($challenge['creator_username']); ?></span></p>
                    <p class="text-lg font-bold text-orange-400">Prize: ₨<?php echo number_format($challenge['prize_pool'], 2); ?></p>
                </div>
                <form method="POST" action="accept_pvp.php">
                    <input type="hidden" name="pvp_match_id" value="<?php echo $challenge['id']; ?>">
                    <input type="hidden" name="entry_fee" value="<?php echo $challenge['entry_fee']; ?>">
                    <button type="submit" name="accept_challenge" class="bg-green-600 text-white font-bold py-2 px-4 rounded-md">Accept (₨<?php echo number_format($challenge['entry_fee'], 2); ?>)</button>
                </form>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-center text-xs text-gray-300 border-t border-gray-700 pt-3">
                <div class="flex flex-col items-center"><i class="fa-solid fa-map-location-dot mb-1 text-gray-400"></i><span><?php echo htmlspecialchars($challenge['map']); ?></span></div>
                <div class="flex flex-col items-center"><i class="fa-solid fa-person-rifle mb-1 text-gray-400"></i><span>Skill: <?php echo htmlspecialchars($challenge['character_skill']); ?></span></div>
                <div class="flex flex-col items-center"><i class="fa-solid fa-cubes mb-1 text-gray-400"></i><span>Ammo: <?php echo htmlspecialchars($challenge['gun_ammo']); ?></span></div>
                <div class="flex flex-col items-center"><i class="fa-solid fa-shield-halved mb-1 text-gray-400"></i><span>Rounds: <?php echo htmlspecialchars($challenge['rounds']); ?></span></div>
            </div>
        </div>
        <?php endforeach; else: ?>
        <p class="text-center text-gray-500 py-4">No open challenges right now.</p>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'common/bottom.php'; ?>