<?php
require_once 'common/header.php';
$error = '';
$success = '';

// Handle updating user profile details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']); // <-- FIX: Added phone to be saved
    $esewa_id = trim($_POST['esewa_id']);
    $in_game_name = trim($_POST['in_game_name']);
    $game_uid = trim($_POST['game_uid']);
    
    if (empty($in_game_name) || empty($game_uid)) {
        $error = "In Game Name and Game UID are required to participate in tournaments.";
    } else {
        // FIX: Updated the SQL query to include the phone number
        $stmt = $pdo->prepare("UPDATE users SET username = ?, phone = ?, esewa_id = ?, in_game_name = ?, game_uid = ? WHERE id = ?");
        try {
            if ($stmt->execute([$username, $phone, $esewa_id, $in_game_name, $game_uid, $current_user['id']])) {
                $success = "Profile updated successfully!";
                $current_user = get_user_data($pdo); 
            }
        } catch (PDOException $e) {
            $error = "That username or phone number is already taken.";
        }
    }
}

// (Password change logic is unchanged)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $stmt_pass = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt_pass->execute([$current_user['id']]);
    $user_pass = $stmt_pass->fetch();
    if (!password_verify($current_password, $user_pass['password'])) {
        $error = "Your current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "The new passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_update_pass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt_update_pass->execute([$hashed_password, $current_user['id']])) {
            $success = "Password changed successfully!";
        } else {
            $error = "Failed to change password.";
        }
    }
}
?>

<h2 class="text-2xl font-bold mb-6 text-white">My Profile</h2>

<?php if ($error): ?><div class="w-full bg-red-500 text-white p-3 rounded-md mb-4 text-center"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="w-full bg-green-500 text-white p-3 rounded-md mb-4 text-center"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<form method="POST" action="profile.php" class="bg-gray-800 p-4 rounded-lg mb-6">
    <h3 class="text-lg font-semibold mb-4 text-orange-400">Edit Details</h3>
    <div class="mb-4"><label class="block text-sm text-gray-400 mb-1">Username</label><input type="text" name="username" value="<?php echo htmlspecialchars($current_user['username']); ?>" class="w-full bg-gray-700 rounded p-2 text-white"></div>
    <div class="mb-4"><label class="block text-sm text-gray-400 mb-1">Phone Number</label><input type="text" name="phone" value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>" class="w-full bg-gray-700 rounded p-2 text-white"></div>
    <div class="mb-4"><label class="block text-sm text-gray-400 mb-1">eSewa ID</label><input type="text" name="esewa_id" value="<?php echo htmlspecialchars($current_user['esewa_id'] ?? ''); ?>" class="w-full bg-gray-700 rounded p-2 text-white"></div>
    <div class="mb-4"><label class="block text-sm text-gray-400 mb-1">In Game Name (IGN) <span class="text-red-400">*</span></label><input type="text" name="in_game_name" value="<?php echo htmlspecialchars($current_user['in_game_name'] ?? ''); ?>" class="w-full bg-gray-700 rounded p-2 text-white" required></div>
    <div class="mb-4"><label class="block text-sm text-gray-400 mb-1">Game UID <span class="text-red-400">*</span></label><input type="text" name="game_uid" value="<?php echo htmlspecialchars($current_user['game_uid'] ?? ''); ?>" class="w-full bg-gray-700 rounded p-2 text-white" required></div>
    <p class="text-xs text-yellow-400 mb-4">You must fill in your IGN and Game UID to join tournaments.</p>
    <button type="submit" name="update_profile" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 rounded-md">Save Changes</button>
</form>

<form method="POST" action="profile.php" class="bg-gray-800 p-4 rounded-lg mb-6">
    <h3 class="text-lg font-semibold mb-4 text-orange-400">Change Password</h3>
    <div class="mb-4"><input type="password" name="current_password" placeholder="Current Password" class="w-full bg-gray-700 rounded p-2 text-white" required></div>
    <div class="mb-4"><input type="password" name="new_password" placeholder="New Password" class="w-full bg-gray-700 rounded p-2 text-white" required></div>
    <div class="mb-4"><input type="password" name="confirm_password" placeholder="Confirm New Password" class="w-full bg-gray-700 rounded p-2 text-white" required></div>
    <button type="submit" name="change_password" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-md">Update Password</button>
</form>

<div class="mt-8 text-center"><a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg"><i class="fa-solid fa-right-from-bracket mr-2"></i>Logout</a></div>

<?php require_once 'common/bottom.php'; ?>