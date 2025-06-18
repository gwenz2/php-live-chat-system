<?php
session_start();
session_unset();
session_destroy();
header('Location: ../index.php?msg=' . urlencode('You have been logged out.'));
exit;
