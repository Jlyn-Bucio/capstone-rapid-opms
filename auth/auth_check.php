<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: /rapid-opms/auth/loggin.php");
    exit();
}
?>
