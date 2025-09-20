<?php
require_once __DIR__ . '/../common/config.php';

if (is_admin_logged_in()) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username and Password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT id, password FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $username;
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-200">
<div class="min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-gray-800 p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold text-center text-orange-500 mb-6">Admin Login</h2>
        <?php if ($error): ?><div class="bg-red-500 text-white p-3 rounded-md mb-4 text-center"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST" action="login.php">
            <div class="mb-4">
                <label for="username" class="block mb-2 text-sm font-medium text-gray-400">Username</label>
                <input type="text" name="username" class="w-full bg-gray-700 border border-gray-600 p-2.5 rounded-lg text-white focus:ring-orange-500 focus:border-orange-500" required>
            </div>
            <div class="mb-6">
                <label for="password" class="block mb-2 text-sm font-medium text-gray-400">Password</label>
                <input type="password" name="password" class="w-full bg-gray-700 border border-gray-600 p-2.5 rounded-lg text-white focus:ring-orange-500 focus:border-orange-500" required>
            </div>
            <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-2.5 rounded-lg">Login</button>
        </form>
    </div>
</div>
</body>
</html>