<?php
require_once 'common/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_id'])) {
        $user_id = $_POST['user_id'];
        if (isset($_POST['block_user'])) {
            $stmt = $pdo->prepare("UPDATE users SET status = 'blocked' WHERE id = ?");
            $stmt->execute([$user_id]);
        }
        if (isset($_POST['unblock_user'])) {
            $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $stmt->execute([$user_id]);
        }
    }
    header("Location: user.php");
    exit();
}

// The query to fetch all user data including the phone number
$users = $pdo->query("SELECT id, username, phone, in_game_name, game_uid, wallet_balance, status FROM users ORDER BY created_at DESC")->fetchAll();
?>

<h2 class="text-2xl font-bold mb-6 text-white">User Management</h2>
<div class="bg-gray-800 p-4 rounded-lg">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-700 text-gray-300">
                <tr>
                    <th class="p-3">Username</th>
                    <th class="p-3">In-Game Name / UID</th>
                    <th class="p-3">Phone</th>
                    <th class="p-3">Wallet</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($users as $user): ?>
                <tr class="border-b border-gray-700">
                    <td class="p-3 font-medium text-white"><?php echo htmlspecialchars($user['username']); ?></td>
                    <td class="p-3 text-xs">
                        <?php echo htmlspecialchars($user['in_game_name'] ?? 'N/A'); ?><br>
                        <span class="text-gray-400"><?php echo htmlspecialchars($user['game_uid'] ?? 'N/A'); ?></span>
                    </td>
                    <td class="p-3"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                    <td class="p-3 font-semibold">₨<?php echo number_format($user['wallet_balance'], 2); ?></td>
                    <td class="p-3"><span class="px-2 py-1 text-xs rounded-full <?php echo $user['status'] === 'active' ? 'bg-green-500' : 'bg-red-500'; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                    <td class="p-3 flex items-center gap-2">
                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-xs">Edit</a>
                        <form method="POST" class="m-0"><input type="hidden" name="user_id" value="<?php echo $user['id']; ?>"><?php if ($user['status'] === 'active'): ?><button type="submit" name="block_user" class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-xs">Block</button><?php else: ?><button type="submit" name="unblock_user" class="bg-yellow-500 hover:bg-yellow-600 px-3 py-1 rounded text-xs">Unblock</button><?php endif; ?></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'common/bottom.php'; ?>