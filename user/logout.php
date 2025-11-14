<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../includes/authProcess.php');
session_start();

$auth = new Autenticar();
$auth->fazerLogout();
?>
