<?php
require_once 'common/header.php';

// Fetch stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_tournaments = $pdo->query("SELECT COUNT(*) FROM tournaments")->fetchColumn();
$total_prize_distributed = $pdo->query("SELECT SUM(prize_pool) FROM tournaments WHERE status = 'Completed'")->fetchColumn() ?: 0;

// Calculate total revenue from commissions
$stmt = $pdo->query("SELECT prize_pool, commission_percentage FROM tournaments WHERE status = 'Completed'");
$tournaments = $stmt->fetchAll();
$total_revenue = 0;
foreach($tournaments as $t) {
    $total_revenue += ($t['prize_pool'] * $t['commission_percentage']) / 100;
}
?>


<h2 class="text-2xl font-bold mb-6 text-white">Dashboard</h2>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-gray-800 p-6 rounded-lg">
        <h3 class="text-gray-400 text-sm font-medium">Total Users</h3>
        <p class="text-3xl font-bold text-white"><?php echo $total_users; ?></p>
    </div>
    <div class="bg-gray-800 p-6 rounded-lg">
        <h3 class="text-gray-400 text-sm font-medium">Total Tournaments</h3>
        <p class="text-3xl font-bold text-white"><?php echo $total_tournaments; ?></p>
    </div>
    <div class="bg-gray-800 p-6 rounded-lg">
        <h3 class="text-gray-400 text-sm font-medium">Prize Distributed</h3>
        <p class="text-3xl font-bold text-white">₨<?php echo number_format($total_prize_distributed); ?></p>
    </div>
    <div class="bg-gray-800 p-6 rounded-lg">
        <h3 class="text-gray-400 text-sm font-medium">Total Revenue</h3>
        <p class="text-3xl font-bold text-white">₨<?php echo number_format($total_revenue); ?></p>
    </div>
</div>

<!-- Quick Action -->
<div>
    <a href="tournament.php" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 px-5 rounded-lg">
        <i class="fa-solid fa-plus mr-2"></i>Create New Tournament
    </a>
</div>

<?php require_once 'common/bottom.php'; ?>