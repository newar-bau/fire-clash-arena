<?php
require_once 'common/header.php';

$message = '';
$message_type = '';

$stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('esewa_qr_code', 'esewa_id')");
$settings_raw = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
$esewa_qr_code = $settings_raw['esewa_qr_code'] ?? '';
$esewa_id = $settings_raw['esewa_id'] ?? 'Not Set';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_deposit'])) {
    $amount = $_POST['amount'];
    $transaction_id = trim($_POST['transaction_id']);
    if (empty($amount) || empty($transaction_id) || !is_numeric($amount) || $amount <= 0) {
        $message = 'Invalid amount or transaction ID.';
        $message_type = 'error';
    } else {
        $stmt = $pdo->prepare("INSERT INTO deposits (user_id, amount, transaction_id) VALUES (?, ?, ?)");
        if ($stmt->execute([$current_user['id'], $amount, $transaction_id])) {
            $message = 'Deposit request submitted successfully! Please wait for admin approval.';
            $message_type = 'success';
        } else {
            $message = 'Failed to submit request. Please try again.';
            $message_type = 'error';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_withdrawal'])) {
    $amount = $_POST['amount'];
    $current_esewa_id = $current_user['esewa_id'] ?? null;
    if (empty($current_esewa_id)) {
        $message = 'Please add your eSewa ID in your profile before withdrawing.';
        $message_type = 'error';
    } elseif ($amount < 100) {
        $message = 'Minimum withdrawal amount is ₨100.';
        $message_type = 'error';
    } elseif ($amount > $current_user['wallet_balance']) {
        $message = 'Withdrawal amount cannot exceed your wallet balance.';
        $message_type = 'error';
    } else {
        $stmt = $pdo->prepare("INSERT INTO withdrawals (user_id, amount) VALUES (?, ?)");
        if ($stmt->execute([$current_user['id'], $amount])) {
            $message = 'Withdrawal request submitted! It will be processed shortly.';
            $message_type = 'success';
        } else {
            $message = 'Failed to submit withdrawal request.';
            $message_type = 'error';
        }
    }
}

$stmt_tx = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt_tx->execute([$current_user['id']]);
$transactions = $stmt_tx->fetchAll();
?>

<?php if ($message): ?><div class="p-3 mb-4 rounded-md text-center text-white <?php echo $message_type === 'success' ? 'bg-green-500' : 'bg-red-500'; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<div class="text-center mb-8"><div class="bg-gradient-to-br from-orange-500 to-orange-700 rounded-lg p-6 w-full max-w-sm mx-auto shadow-lg"><p class="text-lg text-orange-200">Current Balance</p><h2 class="text-5xl font-bold text-white tracking-tight">₨<?php echo number_format($current_user['wallet_balance'], 2); ?></h2></div></div>
<div class="flex justify-center gap-4 mb-8"><button id="addMoneyBtn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg w-full"><i class="fa-solid fa-plus mr-2"></i>Add Money</button><button id="withdrawBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg w-full"><i class="fa-solid fa-arrow-up-from-bracket mr-2"></i>Withdraw</button></div>
<h3 class="text-xl font-bold mb-4 text-white">Transaction History</h3><div class="space-y-3"><?php if (count($transactions) > 0): foreach ($transactions as $tx): ?><div class="bg-gray-800 p-3 rounded-lg flex justify-between items-center"><div><p class="font-semibold text-gray-200"><?php echo htmlspecialchars($tx['description']); ?></p><p class="text-xs text-gray-500"><?php echo date('d M Y, h:i A', strtotime($tx['created_at'])); ?></p></div><p class="font-bold text-lg <?php echo $tx['type'] === 'credit' ? 'text-green-500' : 'text-red-500'; ?>"><?php echo $tx['type'] === 'credit' ? '+' : '-'; ?>₨<?php echo number_format($tx['amount'], 2); ?></p></div><?php endforeach; else: ?><p class="text-center text-gray-400">No transactions yet.</p><?php endif; ?></div>
<!-- Add Money Modal --><div id="addMoneyModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">...</div>
<!-- Withdraw Modal --><div id="withdrawModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">...</div>
<script>const addMoneyBtn = document.getElementById('addMoneyBtn');
    const addMoneyModal = document.getElementById('addMoneyModal');
    const closeAddMoneyModal = document.getElementById('closeAddMoneyModal');

    const withdrawBtn = document.getElementById('withdrawBtn');
    const withdrawModal = document.getElementById('withdrawModal');
    const closeWithdrawModal = document.getElementById('closeWithdrawModal');

    addMoneyBtn.addEventListener('click', () => addMoneyModal.classList.remove('hidden'));
    closeAddMoneyModal.addEventListener('click', () => addMoneyModal.classList.add('hidden'));

    withdrawBtn.addEventListener('click', () => withdrawModal.classList.remove('hidden'));
    closeWithdrawModal.addEventListener('click', () => withdrawModal.classList.add('hidden'));</script>
<?php require_once 'common/bottom.php'; ?>