<?php
require_once __DIR__ . '/../common/config.php';
session_destroy();
header("Location: login.php");
exit();
?>