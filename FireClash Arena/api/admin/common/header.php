<?php
require_once __DIR__ . '/../../common/config.php';

if (!is_admin_logged_in() && basename($_SERVER['SCRIPT_NAME']) !== 'login.php') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - FireClash Arena</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
</head>
<body class="bg-gray-900 text-gray-200">
<div class="min-h-screen flex">
    <aside class="w-56 bg-gray-800 flex flex-col p-4">
        <h1 class="text-xl font-bold text-orange-500 mb-8">Admin Panel</h1>
        <nav class="flex flex-col space-y-2">
            <a href="index.php" class="flex items-center p-2 rounded-md text-gray-300 hover:bg-gray-700"><i class="fa-solid fa-chart-line w-6"></i>Dashboard</a>
            <a href="tournament.php" class="flex items-center p-2 rounded-md text-gray-300 hover:bg-gray-700"><i class="fa-solid fa-trophy w-6"></i>Tournaments</a>
            <a href="claims.php" class="flex items-center p-2 rounded-md text-gray-300 hover:bg-gray-700"><i class="fa-solid fa-image w-6"></i>Winner Claims</a>
            <!-- FIX: Icon added to this link -->
            <a href="deposit_requests.php" class="flex items-center p-2 rounded-md text-gray-300 hover:bg-gray-700"><i class="fa-solid fa-arrow-down-to-bracket w-6"></i>Deposit Requests</a>
            <a href="withdrawal_requests.php" class="flex items-center p-2 rounded-md text-gray-300 hover:bg-gray-700"><i class="fa-solid fa-arrow-up-from-bracket w-6"></i>Withdrawal Requests</a>
            <a href="user.php" class="flex items-center p-2 rounded-md text-gray-300 hover:bg-gray-700"><i class="fa-solid fa-users w-6"></i>Users</a>
            <a href="setting.php" class="flex items-center p-2 rounded-md text-gray-300 hover:bg-gray-700"><i class="fa-solid fa-gear w-6"></i>Settings</a>
            <a href="logout.php" class="flex items-center p-2 rounded-md text-gray-300 hover:bg-gray-700 mt-auto"><i class="fa-solid fa-right-from-bracket w-6"></i>Logout</a>
        </nav>
    </aside>
    <main class="flex-grow p-6">