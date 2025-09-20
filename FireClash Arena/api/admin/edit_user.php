<?php
require_once 'common/header.php';

// Use session for messaging
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
} else {
    $message = '';
    $message_type = '';
}

$user_id = $_GET['id'] ?? null;
if (!$user_id || !is_numeric($user_id)) {
    header("Location: user.php");
    exit();
}

// Handle form submission to update balance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_balance'])) {
    $new_balance = $_POST['wallet_balance'];
    if (is_numeric($new_balance) && $new_balance >= 0) {
        try {
            $pdo->beginTransaction();

            // 1. Update the user's wallet
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $stmt->execute([$new_balance, $user_id]);
            
            // 2. Log this manual adjustment for auditing purposes
            $desc = "Admin manual balance adjustment. New balance: ₨" . number_format($new_balance, 2);
            $stmt_log = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, 0.00, 'credit', ?)");
            $stmt_log->execute([$user_id, $desc]);

            $pdo->commit();
            $_SESSION['message'] = "User balance updated successfully.";
            $_SESSION['message_type'] = 'success';
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['message'] = "Failed to update balance.";
            $_SESSION['message_type'] = 'error';
        }
        
    } else {
        $_SESSION['message'] = "Invalid balance amount provided.";
        $_SESSION['message_type'] = 'error';
    }
    header("Location: user.php"); // Redirect back to the user list to show the message
    exit();
}

// Fetch user data for display
$stmt = $pdo->prepare("SELECT id, username, wallet_balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// If user not found, redirect back
if (!$user) {
    header("Location: user.php");
    exit();
}
?>

<h2 class="text-2xl font-bold mb-6 text-white">Edit User: <?php echo htmlspecialchars($user['username']); ?></h2>

<div class="max-w-md bg-gray-800 p-6 rounded-lg">
    <form method="POST" action="edit_user.php?id=<?php echo $user['id']; ?>">
        <div class="mb-4">
            <label for="wallet_balance" class="block text-sm font-medium text-gray-400 mb-1">Wallet Balance (₨)</label>
            <input 
                type="number" 
                step="0.01" 
                name="wallet_balance" 
                id="wallet_balance"
                value="<?php echo htmlspecialchars($user['wallet_balance']); ?>" 
                class="w-full bg-gray-700 rounded p-2 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-orange-500" 
                required
            >
        </div>
        <div class="flex items-center gap-4">
             <a href="user.php" class="w-full text-center bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 rounded-md">Cancel</a>
            <button type="submit" name="update_balance" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 rounded-md">Update Balance</button>
        </div>
    </form>
</div>

<?php require_once 'common/bottom.php'; ?>