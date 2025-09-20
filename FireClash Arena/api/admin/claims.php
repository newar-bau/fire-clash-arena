<?php
require_once 'common/header.php';

// FIX: This query is more reliable for fetching all valid claims.
$stmt = $pdo->query("
    SELECT 
        p.winner_claim_screenshot,
        u.username,
        t.title AS tournament_title
    FROM participants p
    JOIN users u ON p.user_id = u.id
    JOIN tournaments t ON p.tournament_id = t.id
    WHERE p.winner_claim_screenshot IS NOT NULL AND p.winner_claim_screenshot != ''
    ORDER BY p.join_time DESC
");
$claims = $stmt->fetchAll();
?>

<h2 class="text-2xl font-bold mb-6 text-white">Winner Screenshot Claims</h2>
<div class="bg-gray-800 p-4 rounded-lg">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-700 text-gray-300">
                <tr><th class="p-3">Username</th><th class="p-3">Tournament</th><th class="p-3">Proof</th></tr>
            </thead>
            <tbody>
            <?php if (count($claims) > 0): foreach($claims as $claim): ?>
                <tr class="border-b border-gray-700">
                    <td class="p-3"><?php echo htmlspecialchars($claim['username']); ?></td>
                    <td class="p-3"><?php echo htmlspecialchars($claim['tournament_title']); ?></td>
                    <td class="p-3">
                        <a href="../uploads/screenshots/<?php echo htmlspecialchars($claim['winner_claim_screenshot']); ?>" target="_blank" class="text-blue-400 hover:underline">
                            View Screenshot
                        </a>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="3" class="p-3 text-center text-gray-400">No winner claims have been submitted yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'common/bottom.php'; ?>