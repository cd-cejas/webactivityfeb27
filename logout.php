<?php
session_start();
session_unset();
session_destroy();

// Clear the username cookie
setcookie("web_system_user", "", time() - 3600, "/");

header("Location: homepage.html?auth=signin");
exit();
?>
