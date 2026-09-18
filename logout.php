<?php
require 'config.php';
$_SESSION = [];
session_destroy();
session_start();
$_SESSION['flash'] = ['type' => 'success', 'message' => 'You have been logged out.'];
header('Location: index.php');
exit;
