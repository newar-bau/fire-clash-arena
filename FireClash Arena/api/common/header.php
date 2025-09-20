<?php
require_once 'config.php';
$current_user = get_user_data($pdo);

if (!is_user_logged_in() && basename($_SERVER['SCRIPT_NAME']) !== 'login.php') {
    header("Location: login.php");
    exit();
}

$notification_is_new = false;
if (is_user_logged_in()) {
    $stmt_notify_check = $pdo->query("SELECT id FROM notifications ORDER BY id DESC LIMIT 1");
    $latest_notification_id = $stmt_notify_check->fetchColumn();
    if ($latest_notification_id && (!isset($_SESSION['last_read_notification_id']) || $_SESSION['last_read_notification_id'] < $latest_notification_id)) {
        $notification_is_new = true;
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"><title>FireClash Arena</title><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<style>
    body { font-family: 'Inter', sans-serif; -webkit-user-select: none; user-select: none; }
    .bottom-nav { box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.5); }
    /* FIX: CSS to hide the scrollbar */
    main::-webkit-scrollbar { display: none; }
    main { -ms-overflow-style: none; scrollbar-width: none; }
</style>
</head><body class="bg-gray-900 text-gray-200 overflow-hidden"><div id="app" class="h-screen w-screen flex flex-col max-w-md mx-auto bg-gray-900"><header class="flex-shrink-0 bg-gray-800 p-4 flex justify-between items-center z-10"><h1 class="text-xl font-bold text-orange-500">FireClash Arena</h1><?php if ($current_user): ?><div class="flex items-center gap-4"><a href="notifications.php" class="relative text-gray-300 hover:text-white"><i class="fa-solid fa-bell fa-lg"></i><?php if ($notification_is_new): ?><span class="absolute -top-1 -right-1 flex h-3 w-3"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span><span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span></span><?php endif; ?></a><a href="wallet.php" class="bg-gray-700 text-white px-3 py-1 rounded-full text-sm"><i class="fa-solid fa-wallet"></i> ₨<?php echo number_format($current_user['wallet_balance'], 2); ?></a></div><?php endif; ?></header><main class="flex-grow overflow-y-auto p-4">