<?php
require_once 'common/header.php';
$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header("Location: user.php");
    exit();
}

// Handle form submission to update balance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_balance'])) {
    $new_balance = $_POST['wallet_balance'];
    if (is_numeric($new_balance)) {
        $stmt = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
        $stmt->execute([$new_balance, $user_id]);
        
        // Log this manual adjustment for auditing
        $desc = "Admin manual balance adjustment. New balance: ₨" . $new_balance;
        $stmt_log = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, 0, 'credit', ?)");
        $stmt_log->execute([$user_id, $desc]);

        header("Location: user.php"); // Redirect back to the user list
        exit();
    }
}

// Fetch user data
$stmt = $pdo->prepare("SELECT id, username, wallet_balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<h2 class="text-2xl font-bold mb-6 text-white">Edit User: <?php echo htmlspecialchars($user['username']); ?></h2>

<div class="max-w-md bg-gray-800 p-6 rounded-lg">
    <form method="POST">
        <div class="mb-4">
            <label for="wallet_balance" class="block text-sm font-medium text-gray-400 mb-1">Wallet Balance (₨)</label>
            <input type="number" step="0.01" name="wallet_balance" value="<?php echo htmlspecialchars($user['wallet_balance']); ?>" class="w-full bg-gray-700 rounded p-2 text-white" required>
        </div>
        <button type="submit" name="update_balance" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 rounded-md">Update Balance</button>
    </form>
</div>

<?php require_once 'common/bottom.php'; ?>