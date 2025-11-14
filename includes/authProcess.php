<?php

class Autenticar
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function fazerLogin(string $email, string $senha, PDO $conexao)
    {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conexao->prepare($sql);
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($senha, $usuario['password'])) {
            return $usuario;
        }

        return false;
    }


    public function garantirAutenticado(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: user/login.php");
            exit;
        }
    }

    
    function apenasAdmin() {
    if ($_SESSION['user_role'] !== 'admin') {
        echo "<h3>Acesso negado!</h3>";
        exit;
        }

    }


    public function fazerLogout(): void
    {
        session_destroy();
        header("Location: ../index.php");
        exit;
    }
}


