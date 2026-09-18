<?php
/*
  Database connection settings.
  Update these to match your local XAMPP / WAMP / server MySQL setup.
*/
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'stationary_shop');

// Base URL of your site (no trailing slash) - used for eSewa redirect URLs
define('SITE_URL', 'http://localhost/stationary-shop');

/* -------- eSewa TEST/sandbox credentials --------
   These are eSewa's official published test credentials for the
   rc-epay (sandbox) environment. Replace with your real merchant
   code + secret key when you go live with eSewa. */


$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

session_start();
