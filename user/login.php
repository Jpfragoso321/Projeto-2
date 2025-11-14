<?php
require_once("../includes/db.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $senha = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($senha, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];


        if ($user['role'] === 'admin') {
            header("Location: ../admin/listarUser.php");
        } else {
            header("Location: ../index.php");
        }
        exit;
    } else {
        $erro = "E-mail ou senha incorretos!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - E-commerce</title>
  <link rel="icon" type="image/png" href="../img/blog.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/styles.css?v=<?= time() ?>">
</head>
<body>
<div class="auth-container">
  <div class="auth-card">
    <h3>Login</h3>
    <?php if (!empty($erro)): ?>
      <div class="alert alert-danger"><?= $erro ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">E-mail</label>
        <input type="email" name="email" class="form-control" required placeholder="seu@email.com">
      </div>
      <div class="mb-3">
        <label class="form-label">Senha</label>
        <input type="password" name="password" class="form-control" required placeholder="Digite sua senha">
      </div> 
      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary">Entrar</button>
        <a href="../index.php" class="btn btn-secondary">Voltar</a>
      </div>
      <div class="text-center mt-3">
        <small class="text-muted">Não tem uma conta? <a href="register.php">Cadastre-se</a></small>
      </div>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
