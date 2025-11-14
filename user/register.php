<?php
require_once("../includes/db.php");
require_once("../includes/funcoes.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST['name']);
    $email = trim($_POST['email']);
    $senha = password_hash($_POST['password'], PASSWORD_DEFAULT);


    $checkSql = "SELECT id FROM users WHERE email = :email LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bindParam(":email", $email);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        $msg = " Já existe uma conta com este e-mail.";
    } else {
        $sql = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":name", $nome);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $senha);
        $stmt->execute();

        $user_id = $conn->lastInsertId();

        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $nome;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'user'; 

        header("Location: ../index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrar-se - E-commerce</title>
  <link rel="icon" type="image/png" href="../img/blog.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/styles.css?v=<?= time() ?>">
</head>
<body>
<div class="auth-container">
  <div class="auth-card">
    <h2>Registrar-se</h2>

    <?php if (!empty($msg)): ?>
      <div class="alert alert-danger text-center"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Nome</label>
        <input type="text" name="name" class="form-control" required placeholder="Seu nome completo">
      </div>
      <div class="mb-3">
        <label class="form-label">E-mail</label>
        <input type="email" name="email" class="form-control" required placeholder="seu@email.com">
      </div>
      <div class="mb-3">
        <label class="form-label">Senha</label>
        <input type="password" name="password" class="form-control" required placeholder="Mínimo 6 caracteres">
      </div>
      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary">Criar Conta</button>
        <a href="../index.php" class="btn btn-secondary">Cancelar</a>
      </div>
      <div class="text-center mt-3">
        <small class="text-muted">Já tem uma conta? <a href="login.php">Faça login</a></small>
      </div>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
