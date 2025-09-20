<?php
require_once 'common/header.php';

// Fetch all notifications from the database, newest first
$stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 30");
$notifications = $stmt->fetchAll();

// Mark notifications as "read" by updating the session variable with the ID of the latest notification shown on this page.
if (!empty($notifications)) {
    $latest_id_on_page = $notifications[0]['id'];
    $_SESSION['last_read_notification_id'] = $latest_id_on_page;
}
?>

<h2 class="text-2xl font-bold mb-6 text-white">Notifications</h2>

<div class="space-y-4">
    <?php if (count($notifications) > 0): ?>
        <?php foreach ($notifications as $notification): ?>
        
        <?php
        // Logic to make cards clickable
        $is_clickable = !empty($notification['link']);
        $tag = $is_clickable ? 'a' : 'div';
        $href = $is_clickable ? 'href="' . htmlspecialchars($notification['link']) . '"' : '';
        $hover_class = $is_clickable ? 'hover:bg-gray-700 transition-colors' : '';
        ?>

        <<?php echo $tag; ?> <?php echo $href; ?> class="block bg-gray-800 p-4 rounded-lg shadow <?php echo $hover_class; ?>">
            <div class="flex items-start gap-3">
                <div class="text-orange-400 mt-1"><i class="fa-solid fa-trophy"></i></div>
                <div>
                    <h3 class="font-bold text-gray-200"><?php echo htmlspecialchars($notification['title']); ?></h3>
                    <p class="text-sm text-gray-400 mt-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                    <p class="text-xs text-gray-500 mt-2"><?php echo date('d M Y, h:i A', strtotime($notification['created_at'])); ?></p>
                </div>
            </div>
        </<?php echo $tag; ?>>

        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center text-gray-400">You have no notifications.</p>
    <?php endif; ?>
</div>

<?php require_once 'common/bottom.php'; ?>