<?php
// Database configuration
$DB_HOST = '127.0.0.1';
$DB_NAME = 'moviestream';
$DB_USER = 'root';
$DB_PASS = '';

// Site configuration
$SITE_NAME = 'MovieStream';
$SITE_DESCRIPTION = 'Your favorite streaming platform';
$BASE_URL = 'http://localhost/moviestream';

// Start session
session_start();

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>