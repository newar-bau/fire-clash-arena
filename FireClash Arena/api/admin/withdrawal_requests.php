<?php
require_once 'common/header.php';

// Use the session to store messages so they survive the redirect
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
} else {
    $message = '';
    $message_type = '';
}

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $user_id = $_POST['user_id'];
    $amount = $_POST['amount'];

    if (isset($_POST['approve_withdrawal'])) {
        // Extra check to ensure the request is still pending
        $stmt_check = $pdo->prepare("SELECT status FROM withdrawals WHERE id = ?");
        $stmt_check->execute([$request_id]);
        if ($stmt_check->fetchColumn() === 'Pending') {
            $stmt_bal = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
            $stmt_bal->execute([$user_id]);
            $user_balance = $stmt_bal->fetchColumn();

            if ($user_balance < $amount) {
                $_SESSION['message'] = "User does not have sufficient funds (₨$user_balance). Cannot process withdrawal.";
                $_SESSION['message_type'] = 'error';
            } else {
                try {
                    $pdo->beginTransaction();
                    $stmt1 = $pdo->prepare("UPDATE withdrawals SET status = 'Completed' WHERE id = ?");
                    $stmt1->execute([$request_id]);
                    $stmt2 = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
                    $stmt2->execute([$amount, $user_id]);
                    $stmt3 = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'debit', ?)");
                    $stmt3->execute([$user_id, $amount, 'Withdrawal processed']);
                    $pdo->commit();
                    $_SESSION['message'] = "Withdrawal marked as complete.";
                    $_SESSION['message_type'] = 'success';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['message'] = "Failed to process withdrawal: " . $e->getMessage();
                    $_SESSION['message_type'] = 'error';
                }
            }
        } else {
            $_SESSION['message'] = "This request has already been processed.";
            $_SESSION['message_type'] = 'error';
        }

    } elseif (isset($_POST['reject_withdrawal'])) {
        $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'Rejected' WHERE id = ?");
        if ($stmt->execute([$request_id])) {
            $_SESSION['message'] = "Withdrawal rejected.";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Failed to reject withdrawal.";
            $_SESSION['message_type'] = 'error';
        }
    }

    // --- THIS IS THE FIX ---
    // Redirect back to the same page to clear the POST data
    header("Location: withdrawal_requests.php");
    exit();
}

// Fetch pending withdrawal requests
$stmt_reqs = $pdo->query("SELECT w.id, w.amount, w.created_at, u.id as user_id, u.username, u.esewa_id FROM withdrawals w JOIN users u ON w.user_id = u.id WHERE w.status = 'Pending' ORDER BY w.created_at ASC");
$requests = $stmt_reqs->fetchAll();
?>

<h2 class="text-2xl font-bold mb-6 text-white">Pending Withdrawal Requests</h2>

<?php if ($message): ?>
<div class="p-3 mb-4 rounded-md text-center text-white <?php echo $message_type === 'success' ? 'bg-green-500' : 'bg-red-500'; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<div class="bg-gray-800 p-4 rounded-lg">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-700 text-gray-300">
                <tr>
                    <th class="p-3">User</th>
                    <th class="p-3">Amount</th>
                    <th class="p-3">eSewa ID</th>
                    <th class="p-3">Date</th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($requests) > 0): ?>
                <?php foreach($requests as $req): ?>
                <tr class="border-b border-gray-700">
                    <td class="p-3"><?php echo htmlspecialchars($req['username']); ?></td>
                    <td class="p-3 font-semibold">₨<?php echo number_format($req['amount'], 2); ?></td>
                    <td class="p-3 font-mono"><?php echo htmlspecialchars($req['esewa_id']); ?></td>
                    <td class="p-3"><?php echo date('d M Y, h:i A', strtotime($req['created_at'])); ?></td>
                    <td class="p-3 flex gap-2">
                        <form method="POST" action="withdrawal_requests.php">
                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $req['user_id']; ?>">
                            <input type="hidden" name="amount" value="<?php echo $req['amount']; ?>">
                            <button type="submit" name="approve_withdrawal" class="bg-green-600 hover:bg-green-700 px-3 py-1 rounded text-xs">Complete</button>
                            <button type="submit" name="reject_withdrawal" class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-xs">Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="p-3 text-center text-gray-400">No pending withdrawal requests.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>