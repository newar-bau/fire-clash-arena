<?php
require_once 'common/config.php';
session_destroy();
header("Location: login.php");
exit();
?>