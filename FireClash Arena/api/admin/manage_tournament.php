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

$tournament_id = $_GET['id'] ?? null;
if (!$tournament_id) {
    header("Location: tournament.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_taken = false;
    if (isset($_POST['update_room'])) {
        $room_id = $_POST['room_id'];
        $room_password = $_POST['room_password'];
        $new_status = $_POST['status'];
        $stmt_current = $pdo->prepare("SELECT status, entry_fee, title FROM tournaments WHERE id = ?");
        $stmt_current->execute([$tournament_id]);
        $tournament_before = $stmt_current->fetch();
        
        if ($new_status === 'Cancelled' && $tournament_before['status'] !== 'Cancelled') {
            $entry_fee = $tournament_before['entry_fee'];
            $tournament_title = $tournament_before['title'];
            $stmt_participants = $pdo->prepare("SELECT user_id FROM participants WHERE tournament_id = ?");
            $stmt_participants->execute([$tournament_id]);
            $participants_to_refund = $stmt_participants->fetchAll(PDO::FETCH_COLUMN);
            $refund_count = 0;
            $refund_error = '';
            if (!empty($participants_to_refund)) {
                $notification_title = "Tournament Cancelled: " . htmlspecialchars($tournament_title);
                $notification_message = "This tournament has been cancelled. Your entry fee of ₨" . number_format($entry_fee) . " has been refunded.";
                foreach ($participants_to_refund as $user_id) {
                    try {
                        $pdo->beginTransaction();
                        $stmt_refund = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                        $stmt_refund->execute([$entry_fee, $user_id]);
                        $refund_desc = "Refund for cancelled tournament: " . htmlspecialchars($tournament_title);
                        $stmt_log = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'credit', ?)");
                        $stmt_log->execute([$user_id, $entry_fee, $refund_desc]);
                        $pdo->commit();
                        $refund_count++;
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $refund_error = "A critical error occurred during refund processing.";
                        break;
                    }
                }
            }
            if (empty($refund_error)) {
                $stmt_notify = $pdo->prepare("INSERT INTO notifications (title, message) VALUES (?, ?)");
                $stmt_notify->execute([$notification_title, $notification_message]);
                $_SESSION['message'] = "Tournament cancelled. " . $refund_count . " participant(s) have been refunded.";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = $refund_error;
                $_SESSION['message_type'] = 'error';
            }
        }
        $stmt_update = $pdo->prepare("UPDATE tournaments SET room_id = ?, room_password = ?, status = ? WHERE id = ?");
        $stmt_update->execute([$room_id, $room_password, $new_status, $tournament_id]);
        $action_taken = true;
    }

    if (isset($_POST['declare_winner'])) {
        $winner_id = $_POST['winner_id'];
        $prize_pool = $_POST['prize_pool'];
        $stmt_check = $pdo->prepare("SELECT status FROM tournaments WHERE id = ?");
        $stmt_check->execute([$tournament_id]);
        if ($stmt_check->fetchColumn() === 'Completed') {
            $_SESSION['message'] = "A winner has already been declared for this tournament.";
            $_SESSION['message_type'] = 'error';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt_update_t = $pdo->prepare("UPDATE tournaments SET winner_id = ?, status = 'Completed' WHERE id = ?");
                $stmt_update_t->execute([$winner_id, $tournament_id]);
                $stmt_update_w = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $stmt_update_w->execute([$prize_pool, $winner_id]);
                $desc = "Prize money for tournament #$tournament_id";
                $stmt_log = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'credit', ?)");
                $stmt_log->execute([$winner_id, $prize_pool, $desc]);
                $pdo->commit();
                $_SESSION['message'] = "Winner declared and prize distributed successfully!";
                $_SESSION['message_type'] = 'success';
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['message'] = "An error occurred while declaring the winner.";
                $_SESSION['message_type'] = 'error';
            }
        }
        $action_taken = true;
    }

    if ($action_taken) {
        header("Location: manage_tournament.php?id=" . $tournament_id);
        exit();
    }
}

$stmt_tourney = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
$stmt_tourney->execute([$tournament_id]);
$tournament = $stmt_tourney->fetch();

$stmt_parts = $pdo->prepare("SELECT u.id, u.username, u.in_game_name, u.game_uid, p.winner_claim_screenshot FROM users u JOIN participants p ON u.id = p.user_id WHERE p.tournament_id = ?");
$stmt_parts->execute([$tournament_id]);
$participants = $stmt_parts->fetchAll();
?>

<h2 class="text-2xl font-bold mb-6 text-white">Manage: <?php echo htmlspecialchars($tournament['title']); ?></h2>
<?php if ($message): ?><div class="p-3 mb-4 rounded-md text-center text-white <?php echo $message_type === 'success' ? 'bg-green-500' : 'bg-red-500'; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-gray-800 p-6 rounded-lg">
            <h3 class="text-xl font-semibold mb-4">Update Details & Status</h3>
            <form method="POST" action="manage_tournament.php?id=<?php echo $tournament_id; ?>">
                <div class="mb-4"><label class="block mb-1 text-sm">Room ID</label><input type="text" name="room_id" value="<?php echo htmlspecialchars($tournament['room_id'] ?? ''); ?>" class="w-full bg-gray-700 p-2 rounded"></div>
                <div class="mb-4"><label class="block mb-1 text-sm">Room Password</label><input type="text" name="room_password" value="<?php echo htmlspecialchars($tournament['room_password'] ?? ''); ?>" class="w-full bg-gray-700 p-2 rounded"></div>
                <div class="mb-4"><label class="block mb-1 text-sm">Status</label><select name="status" class="w-full bg-gray-700 p-2 rounded"><option value="Upcoming" <?php if($tournament['status'] == 'Upcoming') echo 'selected'; ?>>Upcoming</option><option value="Live" <?php if($tournament['status'] == 'Live') echo 'selected'; ?>>Live</option><option value="Completed" <?php if($tournament['status'] == 'Completed') echo 'selected'; ?>>Completed</option><option value="Cancelled" <?php if($tournament['status'] == 'Cancelled') echo 'selected'; ?>>Cancelled</option></select></div>
                <button type="submit" name="update_room" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded">Update Details</button>
            </form>
        </div>
        <div class="bg-gray-800 p-6 rounded-lg">
            <h3 class="text-xl font-semibold mb-4 text-orange-400">Declare Winner</h3>
            <?php if($tournament['status'] !== 'Completed'): ?>
            <form method="POST" action="manage_tournament.php?id=<?php echo $tournament_id; ?>">
                <input type="hidden" name="prize_pool" value="<?php echo $tournament['prize_pool']; ?>">
                <div class="mb-4"><label class="block mb-1 text-sm">Select Winner</label><select name="winner_id" class="w-full bg-gray-700 p-2 rounded" required><option value="">-- Select a Participant --</option><?php foreach($participants as $p): ?><option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['username']); ?></option><?php endforeach; ?></select></div>
                <button type="submit" name="declare_winner" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded">Declare Winner & Distribute Prize</button>
            </form>
            <?php else: ?><?php if (!empty($tournament['winner_id'])) { $stmt_winner = $pdo->prepare("SELECT username FROM users WHERE id = ?"); $stmt_winner->execute([$tournament['winner_id']]); $winner = $stmt_winner->fetch(); if ($winner) { echo '<p class="text-green-400">Winner has been declared: <strong>' . htmlspecialchars($winner['username']) . '</strong></p>'; } } ?><?php endif; ?>
        </div>
    </div>
    <div class="bg-gray-800 p-6 rounded-lg">
        <h3 class="text-xl font-semibold mb-4">Participants (<?php echo count($participants); ?>)</h3>
        <ul class="space-y-2">
            <?php if (!empty($participants)): foreach($participants as $p): ?>
            <li class="bg-gray-700 p-3 rounded">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-white"><?php echo htmlspecialchars($p['username']); ?></span>
                    <?php if (!empty($p['winner_claim_screenshot'])): ?>
                    <a href="../uploads/screenshots/<?php echo htmlspecialchars($p['winner_claim_screenshot']); ?>" target="_blank" class="text-xs bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600">View Proof</a>
                    <?php endif; ?>
                </div>
                <div class="text-xs text-gray-400 mt-1">
                    <p>IGN: <span class="text-gray-200"><?php echo htmlspecialchars($p['in_game_name'] ?? 'N/A'); ?></span></p>
                    <p>UID: <span class="text-gray-200"><?php echo htmlspecialchars($p['game_uid'] ?? 'N/A'); ?></span></p>
                </div>
            </li>
            <?php endforeach; else: ?>
            <li class="text-gray-400">No participants yet.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>
<?php require_once 'common/bottom.php'; ?>