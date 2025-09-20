<?php
require_once 'common/header.php';
$user_id = $current_user['id'];
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'upcoming';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
} else {
    $message = '';
    $message_type = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_screenshot'])) {
    $participant_id = $_POST['participant_id'];
    if (isset($_FILES['winner_screenshot']) && $_FILES['winner_screenshot']['error'] == 0) {
        $target_dir = __DIR__ . "/uploads/screenshots/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $file_ext = strtolower(pathinfo($_FILES['winner_screenshot']['name'], PATHINFO_EXTENSION));
        $new_filename = 'proof_' . $user_id . '_' . time() . '.' . $file_ext;
        $target_file = $target_dir . $new_filename;
        $allowed = ['jpg', 'jpeg', 'png'];
        if (in_array($file_ext, $allowed) && $_FILES['winner_screenshot']['size'] < 5000000) {
            if (move_uploaded_file($_FILES['winner_screenshot']['tmp_name'], $target_file)) {
                $stmt = $pdo->prepare("UPDATE participants SET winner_claim_screenshot = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$new_filename, $participant_id, $user_id]);
                $_SESSION['message'] = "Screenshot uploaded successfully! Admin will verify.";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Failed to upload screenshot.";
                $_SESSION['message_type'] = 'error';
            }
        } else {
            $_SESSION['message'] = "Invalid file type or size is too large (max 5MB).";
            $_SESSION['message_type'] = 'error';
        }
    }
    header("Location: my_tournaments.php?tab=" . $active_tab);
    exit();
}

$stmt_upcoming = $pdo->prepare("SELECT t.*, p.id as participant_id, p.winner_claim_screenshot FROM tournaments t JOIN participants p ON t.id = p.tournament_id WHERE p.user_id = ? AND (t.status = 'Upcoming' OR t.status = 'Live') ORDER BY t.match_time ASC");
$stmt_upcoming->execute([$user_id]);
$upcoming_tournaments = $stmt_upcoming->fetchAll();

$stmt_completed = $pdo->prepare("SELECT t.*, p.id as participant_id, p.winner_claim_screenshot FROM tournaments t JOIN participants p ON t.id = p.tournament_id WHERE p.user_id = ? AND t.status = 'Completed' ORDER BY t.match_time DESC");
$stmt_completed->execute([$user_id]);
$completed_tournaments = $stmt_completed->fetchAll();
?>

<?php if ($message): ?><div class="p-3 mb-4 rounded-md text-center text-white <?php echo $message_type === 'success' ? 'bg-green-500' : 'bg-red-500'; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<div class="w-full bg-gray-800 rounded-lg p-2 mb-6"><div class="flex"><a href="?tab=upcoming" class="w-1/2 text-center py-2 rounded-md <?php echo $active_tab === 'upcoming' ? 'bg-orange-500 text-white' : 'text-gray-400'; ?>">Upcoming/Live</a><a href="?tab=completed" class="w-1/2 text-center py-2 rounded-md <?php echo $active_tab === 'completed' ? 'bg-orange-500 text-white' : 'text-gray-400'; ?>">Completed</a></div></div>

<div id="upcoming-tab" class="<?php echo $active_tab === 'upcoming' ? '' : 'hidden'; ?>"><div class="space-y-4"><?php if(count($upcoming_tournaments) > 0): ?><?php foreach ($upcoming_tournaments as $t): ?><div class="bg-gray-800 rounded-lg shadow-lg p-4"><div class="flex justify-between items-start"><div><h3 class="text-lg font-bold text-orange-400"><?php echo htmlspecialchars($t['title']); ?></h3><p class="text-sm text-gray-400"><?php echo htmlspecialchars($t['game_name']); ?></p></div><span class="text-xs <?php echo $t['status'] === 'Live' ? 'bg-red-600' : 'bg-green-600'; ?> text-white font-semibold px-2 py-1 rounded-full"><?php echo $t['status']; ?></span></div><?php if ($t['status'] === 'Live' && !empty($t['room_id'])): ?><div class="mt-4 border-t border-gray-700 pt-3"><h4 class="text-sm font-semibold text-gray-300">Room Details</h4><p>ID: <strong class="text-white"><?php echo htmlspecialchars($t['room_id']); ?></strong></p><p>Password: <strong class="text-white"><?php echo htmlspecialchars($t['room_password']); ?></strong></p></div><?php else: ?><div class="mt-3 text-center text-xs text-yellow-400 bg-yellow-900/50 p-2 rounded-md">Room ID & Password will be available 10 minutes before the match starts.</div><?php endif; ?></div><?php endforeach; ?><?php else: ?><div class="text-center py-10"><i class="fa-solid fa-trophy text-4xl text-gray-600 mb-4"></i><p class="text-gray-400">You haven't joined any upcoming tournaments.</p><a href="index.php" class="mt-4 inline-block bg-orange-600 text-white font-bold py-2 px-4 rounded-md">Join a Match</a></div><?php endif; ?></div></div>

<div id="completed-tab" class="<?php echo $active_tab === 'completed' ? '' : 'hidden'; ?>"><div class="space-y-4"><?php if(count($completed_tournaments) > 0): ?><?php foreach ($completed_tournaments as $t): ?><div class="bg-gray-800 rounded-lg shadow-lg p-4"><div class="flex justify-between items-start"><div><h3 class="text-lg font-bold text-gray-300"><?php echo htmlspecialchars($t['title']); ?></h3><p class="text-sm text-gray-400">Result: <?php if ($t['winner_id'] == $user_id): ?><span class="text-green-400 font-bold">Winner</span><?php else: ?><span class="text-gray-400">Participated</span><?php endif; ?></p></div><?php if (empty($t['winner_claim_screenshot'])): ?><form method="POST" enctype="multipart/form-data"><input type="hidden" name="participant_id" value="<?php echo $t['participant_id']; ?>"><label class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-3 rounded-md cursor-pointer"><i class="fa-solid fa-upload"></i><input type="file" name="winner_screenshot" class="hidden" onchange="this.form.submit()"></label><button type="submit" name="upload_screenshot" class="hidden">Upload</button></form><?php else: ?><div class="flex items-center gap-2 text-green-500 text-xs"><i class="fa-solid fa-check-circle"></i><span>Uploaded</span></div><?php endif; ?></div></div><?php endforeach; ?><?php else: ?><div class="text-center py-10"><i class="fa-solid fa-clock-rotate-left text-4xl text-gray-600 mb-4"></i><p class="text-center text-gray-400">You have no completed tournament history.</p></div><?php endif; ?></div></div>

<?php require_once 'common/bottom.php'; ?>