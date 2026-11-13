<?php
session_start();
unset($_SESSION['user']);
session_destroy();
header("Location: index.php?Message=" . urlencode("Successfully logged out!"));
exit();
?>
