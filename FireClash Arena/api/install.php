<?php
// install.php

// --- CONFIGURATION ---
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = ''; // CHANGED: Password is now empty. Use 'root' or your actual password if you have one.
$db_name = 'fireclash_arena';
$admin_user = 'admin';
$admin_pass = 'admin123';
// --------------------

$message = "";

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `$db_name`");
    $message .= "Database '$db_name' created or already exists.<br>";

    // --- Create Tables ---

 
    // users table
    $sql_users = "
    CREATE TABLE IF NOT EXISTS `users` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `username` VARCHAR(50) NOT NULL UNIQUE,
      `email` VARCHAR(100) NOT NULL UNIQUE,
      `password` VARCHAR(255) NOT NULL,
      `esewa_id` VARCHAR(20) NULL DEFAULT NULL,
      `wallet_balance` DECIMAL(10, 2) DEFAULT 0.00,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    ";


    // admin table
    $sql_admin = "
    CREATE TABLE IF NOT EXISTS `admin` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `username` VARCHAR(50) NOT NULL UNIQUE,
      `password` VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB;
    ";
    $pdo->exec($sql_admin);
    $message .= "Table 'admin' created successfully.<br>";
    
    // tournaments table
    $sql_tournaments = "
    CREATE TABLE IF NOT EXISTS `tournaments` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `title` VARCHAR(255) NOT NULL,
      `game_name` VARCHAR(100) NOT NULL,
      `entry_fee` DECIMAL(10, 2) NOT NULL,
      `prize_pool` DECIMAL(10, 2) NOT NULL,
      `match_time` DATETIME NOT NULL,
      `room_id` VARCHAR(100) DEFAULT NULL,
      `room_password` VARCHAR(100) DEFAULT NULL,
      `status` ENUM('Upcoming', 'Live', 'Completed', 'Cancelled') DEFAULT 'Upcoming',
      `winner_id` INT DEFAULT NULL,
      `commission_percentage` INT DEFAULT 10,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    ";
    $pdo->exec($sql_tournaments);
    $message .= "Table 'tournaments' created successfully.<br>";

    // participants table
    $sql_participants = "
    CREATE TABLE IF NOT EXISTS `participants` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `tournament_id` INT NOT NULL,
      `join_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ";
    $pdo->exec($sql_participants);
    $message .= "Table 'participants' created successfully.<br>";

    // transactions table
    $sql_transactions = "
    CREATE TABLE IF NOT EXISTS `transactions` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `amount` DECIMAL(10, 2) NOT NULL,
      `type` ENUM('credit', 'debit') NOT NULL,
      `description` VARCHAR(255) NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ";
    $pdo->exec($sql_transactions);
    $message .= "Table 'transactions' created successfully.<br>";

    // --- Insert Default Admin ---
    $stmt = $pdo->prepare("SELECT id FROM admin WHERE username = ?");
    $stmt->execute([$admin_user]);
    if ($stmt->rowCount() == 0) {
        $hashed_password = password_hash($admin_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
        $stmt->execute([$admin_user, $hashed_password]);
        $message .= "Default admin user created (admin/admin123).<br>";
    } else {
        $message .= "Admin user already exists.<br>";
    }

    $message .= "<br><strong style='color:green;'>Installation complete! Please delete this file.</strong><br>";
    $message .= "<a href='login.php' style='color:white; background-color:blue; padding:10px; text-decoration:none;'>Go to User Login</a>";


} catch (PDOException $e) {
    $message = "<strong style='color:red;'>Error: " . $e->getMessage() . "</strong>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FireClash Arena - Installation</title>
    <style>
        body { font-family: sans-serif; background-color: #111827; color: #d1d5db; line-height: 1.6; padding: 20px; }
        .container { max-width: 600px; margin: 50px auto; background: #1f2937; padding: 20px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>FireClash Arena Installer</h1>
        <div><?php echo $message; ?></div>
    </div>
</body>
</html>