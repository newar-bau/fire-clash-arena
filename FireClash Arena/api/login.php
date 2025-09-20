<?php
require_once 'common/config.php';

if (is_user_logged_in()) {
    header("Location: index.php");
    exit();
}
$error = '';
$success = $_SESSION['signup_success'] ?? '';
unset($_SESSION['signup_success']);
$form_type = isset($_GET['form']) && $_GET['form'] === 'signup' ? 'signup' : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- LOGIN LOGIC ---
    if (isset($_POST['login'])) {
        // FIX: The login form uses 'identifier', so we check for that specifically.
        $identifier = trim($_POST['identifier']); 
        $password = $_POST['password'];

        if (empty($identifier) || empty($password)) {
            $error = "All fields are required.";
        } else {
            $stmt = $pdo->prepare("SELECT id, password, status FROM users WHERE username = :identifier OR phone = :identifier");
            $stmt->execute(['identifier' => $identifier]);
            $user = $stmt->fetch();
            if ($user && $user['status'] === 'blocked') {
                $error = "Your account has been temporarily suspended due to suspicious activity. For more information or to appeal this decision, please contact us at 9808815084.";
            } elseif ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid username/phone or password.";
            }
        }
    } 
    // --- SIGNUP LOGIC ---
    elseif (isset($_POST['signup'])) {
        // FIX: The signup form uses 'username' and 'phone', so we check for those.
        $username = trim($_POST['username']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];

        if (empty($username) || empty($phone) || empty($password)) {
            $error = "All fields are required.";
        } elseif (!preg_match('/^\d{10}$/', $phone) && !preg_match('/^\+977\d{10}$/', $phone)) {
            $error = "Please enter a valid 10-digit or +977 phone number.";
        } else {
            $stmt_check = $pdo->prepare("SELECT id FROM users WHERE phone = ? OR username = ?");
            $stmt_check->execute([$phone, $username]);
            if ($stmt_check->fetch()) {
                $error = "Phone number or username is already registered.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert = $pdo->prepare("INSERT INTO users (username, phone, password) VALUES (?, ?, ?)");
                if ($stmt_insert->execute([$username, $phone, $hashed_password])) {
                    $success = "Registration successful! Please login.";
                    $form_type = 'login';
                } else {
                    $error = "Something went wrong. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"><title>Login - FireClash Arena</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-900 text-gray-200"><div class="min-h-screen flex flex-col items-center justify-center max-w-md mx-auto p-4"><h1 class="text-3xl font-bold text-orange-500 mb-6">FireClash Arena</h1><div class="w-full bg-gray-800 rounded-lg p-2 mb-6"><div class="flex"><a href="?form=login" class="w-1/2 text-center py-2 rounded-md <?php echo $form_type === 'login' ? 'bg-orange-500 text-white' : 'text-gray-400'; ?>">Login</a><a href="?form=signup" class="w-1/2 text-center py-2 rounded-md <?php echo $form_type === 'signup' ? 'bg-orange-500 text-white' : 'text-gray-400'; ?>">Sign Up</a></div></div><?php if ($error): ?><div class="w-full bg-red-500 text-white p-3 rounded-md mb-4 text-center"><?php echo $error; ?></div><?php endif; ?><?php if ($success): ?><div class="w-full bg-green-500 text-white p-3 rounded-md mb-4 text-center"><?php echo $success; ?></div><?php endif; ?>
<div id="login-form" class="w-full <?php echo $form_type === 'login' ? '' : 'hidden'; ?>">
    <form method="POST" action="login.php?form=login">
        <div class="mb-4"><label for="login-identifier" class="block text-sm text-gray-400 mb-1">Username or Phone Number</label><input type="text" id="login-identifier" name="identifier" placeholder="Username or 98XXXXXXXX" class="w-full bg-gray-700 rounded-md px-3 py-2 text-white" required></div>
        <div class="mb-6"><label for="login-password" class="block text-sm text-gray-400 mb-1">Password</label><input type="password" id="login-password" name="password" class="w-full bg-gray-700 rounded-md px-3 py-2 text-white" required></div>
        <button type="submit" name="login" class="w-full bg-orange-600 text-white font-bold py-2 rounded-md">Login</button>
    </form>
</div>
<div id="signup-form" class="w-full <?php echo $form_type === 'signup' ? '' : 'hidden'; ?>">
    <form method="POST" action="login.php?form=signup">
        <div class="mb-4"><label for="signup-username" class="block text-sm text-gray-400 mb-1">Username</label><input type="text" id="signup-username" name="username" class="w-full bg-gray-700 rounded-md px-3 py-2 text-white" required></div>
        <div class="mb-4"><label for="signup-phone" class="block text-sm text-gray-400 mb-1">Phone Number</label><input type="text" id="signup-phone" name="phone" placeholder="98XXXXXXXX" class="w-full bg-gray-700 rounded-md px-3 py-2 text-white" required></div>
        <div class="mb-6"><label for="signup-password" class="block text-sm text-gray-400 mb-1">Password</label><input type="password" id="signup-password" name="password" class="w-full bg-gray-700 rounded-md px-3 py-2 text-white" required></div>
        <button type="submit" name="signup" class="w-full bg-orange-600 text-white font-bold py-2 rounded-md">Sign Up</button>
    </form>
</div>
</div></body></html>