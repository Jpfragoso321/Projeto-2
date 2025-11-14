<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

    require_once('db.php');
    require_once('authProcess.php');

    function resolverConexao(): PDO {
    global $conn;
    if (isset($conn) && $conn instanceof PDO) {
        return $conn;
    }
    return Database::getInstancia()->getConexao();
}

    function fazerLogin($email, $senha) {
    $pdo = resolverConexao();
    $auth = new Autenticar();
    return $auth->fazerLogin($email, $senha, $pdo);
}

    function verificarLogin() {
    $auth = new Autenticar();
    $auth->garantirAutenticado();
}

    function fazerLogout() {
    $auth = new Autenticar();
    $auth->fazerLogout();
}