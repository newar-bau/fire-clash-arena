<?php
require_once 'common/header.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tournament'])) {
    $title = $_POST['title'];
    $game_name = $_POST['game_name'];
    $map = $_POST['map'];
    $gun_ammo = $_POST['gun_ammo'];
    $character_skill = $_POST['character_skill'];
    $entry_fee = $_POST['entry_fee'];
    $prize_pool = $_POST['prize_pool'];
    $prize_per_kill = $_POST['prize_per_kill'];
    $match_time = $_POST['match_time'];
    $commission = $_POST['commission_percentage'];

    $stmt = $pdo->prepare("INSERT INTO tournaments (title, game_name, map, gun_ammo, character_skill, entry_fee, prize_pool, prize_per_kill, match_time, commission_percentage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$title, $game_name, $map, $gun_ammo, $character_skill, $entry_fee, $prize_pool, $prize_per_kill, $match_time, $commission])) {
        $success = "Tournament created successfully!";
        $notification_title = "New Tournament: " . htmlspecialchars($title);
        $notification_message = "A new " . htmlspecialchars($game_name) . " tournament is available with a prize pool of ₨" . number_format($prize_pool) . ". Join now!";
        $stmt_notify = $pdo->prepare("INSERT INTO notifications (title, message, link) VALUES (?, ?, ?)");
        $stmt_notify->execute([$notification_title, $notification_message, 'index.php']);
    } else {
        $error = "Failed to create tournament.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_tournament'])) {
    $id_to_delete = $_POST['tournament_id'];
    $stmt = $pdo->prepare("DELETE FROM tournaments WHERE id = ?");
    if ($stmt->execute([$id_to_delete])) {
        $success = "Tournament deleted successfully!";
    } else {
        $error = "Failed to delete tournament.";
    }
}

$tournaments = $pdo->query("SELECT * FROM tournaments ORDER BY created_at DESC")->fetchAll();
?>

<h2 class="text-2xl font-bold mb-6 text-white">Manage Tournaments</h2>
<?php if ($error): ?><div class="bg-red-500 text-white p-3 rounded-md mb-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="bg-green-500 text-white p-3 rounded-md mb-4"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="bg-gray-800 p-6 rounded-lg mb-8">
    <h3 class="text-xl font-semibold mb-4 text-orange-400">Add New Tournament</h3>
    <form method="POST" action="tournament.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="text" name="title" placeholder="Tournament Title" class="bg-gray-700 p-2 rounded" required>
        <input type="text" name="game_name" placeholder="Game Name (e.g., FreeFire)" class="bg-gray-700 p-2 rounded" required>
        <select name="map" class="bg-gray-700 p-2 rounded" required><option value="Bermuda">Map: Bermuda</option><option value="Purgatory">Map: Purgatory</option><option value="Kalahari">Map: Kalahari</option></select>
        <select name="gun_ammo" class="bg-gray-700 p-2 rounded" required><option value="Unlimited">Gun Ammo: Unlimited</option><option value="Limited">Gun Ammo: Limited</option></select>
        <select name="character_skill" class="bg-gray-700 p-2 rounded" required><option value="Yes">Character Skill: Yes</option><option value="No">Character Skill: No</option></select>
        <input type="number" step="0.01" name="prize_per_kill" placeholder="Prize per Kill (₨)" class="bg-gray-700 p-2 rounded" required value="10">
        <input type="number" step="0.01" name="entry_fee" placeholder="Entry Fee (₨)" class="bg-gray-700 p-2 rounded" required>
        <input type="number" step="0.01" name="prize_pool" placeholder="Main Prize Pool (₨)" class="bg-gray-700 p-2 rounded" required>
        <input type="datetime-local" name="match_time" class="bg-gray-700 p-2 rounded" required>
        <input type="number" name="commission_percentage" placeholder="Commission %" class="bg-gray-700 p-2 rounded" value="20" required>
        <div class="md:col-span-2"><button type="submit" name="add_tournament" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 rounded">Create Tournament</button></div>
    </form>
</div>

<div class="bg-gray-800 p-6 rounded-lg">
    <h3 class="text-xl font-semibold mb-4">All Tournaments</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-700 text-gray-300">
                <tr><th class="p-3">Title</th><th class="p-3">Fee</th><th class="p-3">Prize</th><th class="p-3">Time</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach($tournaments as $t): ?>
                <tr class="border-b border-gray-700">
                    <td class="p-3"><?php echo htmlspecialchars($t['title']); ?></td>
                    <td class="p-3">₨<?php echo number_format($t['entry_fee']); ?></td>
                    <td class="p-3">₨<?php echo number_format($t['prize_pool']); ?></td>
                    <td class="p-3"><?php echo date('d M H:i', strtotime($t['match_time'])); ?></td>
                    <td class="p-3"><span class="px-2 py-1 text-xs rounded-full <?php 
                        switch($t['status']) {
                            case 'Upcoming': echo 'bg-blue-500'; break;
                            case 'Live': echo 'bg-red-500'; break;
                            case 'Completed': echo 'bg-green-500'; break;
                            case 'Cancelled': echo 'bg-yellow-500'; break;
                            default: echo 'bg-gray-500';
                        }
                    ?>"><?php echo $t['status']; ?></span></td>
                    <td class="p-3 flex gap-2">
                        <a href="manage_tournament.php?id=<?php echo $t['id']; ?>" class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded">Manage</a>
                        <form method="POST" action="tournament.php" onsubmit="return confirm('Are you sure you want to delete this tournament?');">
                            <input type="hidden" name="tournament_id" value="<?php echo $t['id']; ?>">
                            <button type="submit" name="delete_tournament" class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>