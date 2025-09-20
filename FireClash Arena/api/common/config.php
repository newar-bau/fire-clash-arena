<?php
// Start output buffering and session management
ob_start();
session_start();

// --- DATABASE CONFIGURATION ---
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', ''); // IMPORTANT: Ensure this is your correct MySQL password (often '' or 'root')
define('DB_NAME', 'fireclash_arena');

// --- DATABASE CONNECTION (PDO) ---
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If the database can't connect, stop everything.
    die("CRITICAL ERROR: Could not connect to the database. Please check your credentials in /common/config.php. " . $e->getMessage());
}

// --- GLOBAL FUNCTIONS ---

/**
 * Checks if a user is currently logged in.
 * @return bool
 */
function is_user_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Checks if an admin is currently logged in.
 * @return bool
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

/**
 * --- THIS IS THE CRITICAL FIX ---
 * Fetches the complete data for the currently logged-in user.
 * This function is now more robust and includes all necessary fields.
 * The previous version was missing fields, causing the "Trying to access array offset on value of type null" error.
 *
 * @param PDO $pdo The database connection object.
 * @return array|null The user's data as an associative array, or null if not logged in.
 */
function get_user_data($pdo) {
    if (!is_user_logged_in()) {
        return null;
    }
    try {
        // This query now selects EVERY field that the application needs.
        $stmt = $pdo->prepare(
            "SELECT id, username, phone, wallet_balance, esewa_id, in_game_name, game_uid, status 
             FROM users WHERE id = ?"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        // If for some reason the user ID in the session is invalid (e.g., user deleted), return null
        return $user ? $user : null;

    } catch (PDOException $e) {
        // In case of a database error, return null to prevent crashes
        return null;
    }
}
?>