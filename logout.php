<?php
session_start();
session_unset();  // optional pero recommended
session_destroy();
header("Location: loggin.php"); // make sure tama ang filename
exit();
?>
