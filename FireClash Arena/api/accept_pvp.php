<?php
require_once 'common/config.php';

if (!is_user_logged_in()) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_challenge'])) {
    $pvp_match_id = $_POST['pvp_match_id'];
    $entry_fee = $_POST['entry_fee'];
    $current_user_id = $current_user['id'];
    
    // --- THIS IS THE CRITICAL FIX ---
    // The code now correctly compares the user's wallet balance with the entry fee.
    if ($current_user['wallet_balance'] < $entry_fee) {
        $_SESSION['message'] = 'Insufficient balance to accept this challenge.';
        $_SESSION['message_type'] = 'error';
        header("Location: pvp.php");
        exit();
    }

    try {
        $pdo->beginTransaction();

        $stmt_check = $pdo->prepare("SELECT * FROM pvp_matches WHERE id = ? AND status = 'Open' FOR UPDATE");
        $stmt_check->execute([$pvp_match_id]);
        $match = $stmt_check->fetch();

        if (!$match) {
            $pdo->rollBack();
            $_SESSION['message'] = 'This challenge is no longer available.';
            $_SESSION['message_type'] = 'error';
            header("Location: pvp.php");
            exit();
        }

        $stmt_wallet = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
        $stmt_wallet->execute([$entry_fee, $current_user_id]);

        $desc = "Entry fee for accepting PvP match #" . $pvp_match_id;
        $stmt_log = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'debit', ?)");
        $stmt_log->execute([$current_user_id, $entry_fee, $desc]);

        $stmt_update_pvp = $pdo->prepare("UPDATE pvp_matches SET opponent_user_id = ?, status = 'Active' WHERE id = ?");
        $stmt_update_pvp->execute([$current_user_id, $pvp_match_id]);

        $pdo->commit();
        $_SESSION['message'] = 'Challenge accepted! The match is now active.';
        $_SESSION['message_type'] = 'success';

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['message'] = 'An error occurred. Please try again.';
        $_SESSION['message_type'] = 'error';
    }
}

header("Location: pvp.php");
exit();
?>