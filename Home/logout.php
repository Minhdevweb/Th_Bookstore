<?php
session_start();
session_destroy();
// Redirect back to homepage and trigger login modal
header('Location: index.php?login=1');
exit;
?>