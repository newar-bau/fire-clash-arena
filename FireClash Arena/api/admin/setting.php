<?php
require_once 'common/header.php';
$error = '';
$success = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_esewa'])) {
        $esewa_id = $_POST['esewa_id'];
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'esewa_id'");
        $stmt->execute([$esewa_id]);
        $success = "eSewa ID updated successfully."; // Set success message initially

        // Handle QR Code Upload only if a file is actually submitted
        if (isset($_FILES['esewa_qr_code']) && $_FILES['esewa_qr_code']['error'] == 0) {
            $target_dir = __DIR__ . "/../uploads/";
            
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            if (!is_writable($target_dir)) {
                $error = "Error: The server does not have permission to write to the 'uploads' directory.";
                $success = ''; 
            } else {
                $file_ext = strtolower(pathinfo($_FILES['esewa_qr_code']['name'], PATHINFO_EXTENSION));
                $new_filename = 'esewa_qr_' . time() . '.' . $file_ext;
                $target_file = $target_dir . $new_filename;

                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($file_ext, $allowed_types)) {
                    if (move_uploaded_file($_FILES['esewa_qr_code']['tmp_name'], $target_file)) {
                        $stmt_qr = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'esewa_qr_code'");
                        $stmt_qr->execute([$new_filename]);
                        $success = "eSewa settings and QR code updated successfully."; 
                    } else {
                        $error = "Failed to upload QR code. Check server logs.";
                        $success = ''; 
                    }
                } else {
                    $error = "Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.";
                    $success = ''; 
                }
            }
        }
    }

    // Handle Password Change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        $stmt = $pdo->prepare("SELECT password FROM admin WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if (!password_verify($current_password, $admin['password'])) {
            $error = "Current admin password is incorrect.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_update = $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?");
            if ($stmt_update->execute([$hashed_password, $_SESSION['admin_id']])) {
                $success = "Admin password updated successfully!";
            } else {
                $error = "Failed to update password.";
            }
        }
    }
}

// Fetch current settings
// --- THIS IS THE FIX --- The query now selects exactly 2 columns.
$stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('esewa_qr_code', 'esewa_id')");
$settings_raw = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
$esewa_qr_code = $settings_raw['esewa_qr_code'] ?? '';
$esewa_id = $settings_raw['esewa_id'] ?? '';
?>

<h2 class="text-2xl font-bold mb-6 text-white">Settings</h2>

<?php if ($error): ?><div class="bg-red-500 text-white p-3 rounded-md mb-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="bg-green-500 text-white p-3 rounded-md mb-4"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- eSewa Settings -->
    <div class="bg-gray-800 p-6 rounded-lg">
        <h3 class="text-xl font-semibold mb-4 text-orange-400">eSewa Payment Settings</h3>
        <form method="POST" action="setting.php" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="block mb-1 text-sm">eSewa ID (+977 Phone Number)</label>
                <input type="text" name="esewa_id" value="<?php echo htmlspecialchars($esewa_id); ?>" class="w-full bg-gray-700 p-2 rounded" required>
            </div>
            <div class="mb-4">
                <label class="block mb-1 text-sm">Upload New eSewa QR Code</label>
                <input type="file" name="esewa_qr_code" class="w-full bg-gray-700 p-2 rounded file:mr-4 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                <?php if ($esewa_qr_code): ?>
                <div class="mt-2">
                    <p class="text-xs text-gray-400 mb-1">Current QR:</p>
                    <img src="../uploads/<?php echo htmlspecialchars($esewa_qr_code); ?>" alt="Current QR" class="w-24 h-24 rounded">
                </div>
                <?php endif; ?>
            </div>
            <button type="submit" name="update_esewa" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 rounded">Save eSewa Settings</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="bg-gray-800 p-6 rounded-lg">
        <h3 class="text-xl font-semibold mb-4 text-orange-400">Change Admin Password</h3>
        <form method="POST" action="setting.php">
            <div class="mb-4">
                <input type="password" name="current_password" placeholder="Current Password" class="w-full bg-gray-700 p-2 rounded" required>
            </div>
            <div class="mb-4">
                <input type="password" name="new_password" placeholder="New Password" class="w-full bg-gray-700 p-2 rounded" required>
            </div>
            <div class="mb-4">
                <input type="password" name="confirm_password" placeholder="Confirm New Password" class="w-full bg-gray-700 p-2 rounded" required>
            </div>
            <button type="submit" name="change_password" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded">Update Password</button>
        </form>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>