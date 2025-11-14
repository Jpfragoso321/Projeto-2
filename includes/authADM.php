<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /ecommerce/login.php");
    exit;
}


function apenasAdmin() {
    if ($_SESSION['user_role'] !== 'admin') {
        echo "<h3>Acesso negado!</h3>";
        exit;
    }
}
?>
