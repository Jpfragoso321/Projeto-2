<?php
require_once("../includes/db.php");
require_once("../includes/authADM.php");
require_once("../includes/authProcess.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$db = $database->getConnection();

$auth = new Autenticar($db);
$auth->apenasAdmin();

if (isset($_GET['delete'])) {
    $userId = (int) $_GET['delete'];

    $stmt = $db->prepare("DELETE FROM cart_items WHERE user_id = :user_id");
    $stmt->bindParam(":user_id", $userId);
    $stmt->execute();

    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->bindParam(":id", $userId);
    $stmt->execute();

    $_SESSION['msg'] = "✅ Usuário removido com sucesso!";
    header("Location: listarUser.php");
    exit;
}

$query = $db->prepare("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
$query->execute();
$users = $query->fetchAll(PDO::FETCH_ASSOC);

$carts = [];
foreach ($users as $u) {
    $stmt = $db->prepare("
        SELECT c.*, p.name AS product_name, p.price
        FROM cart_items c
        LEFT JOIN products p ON c.product_id = p.id
        WHERE c.user_id = :uid
    ");
    $stmt->bindParam(":uid", $u['id']);
    $stmt->execute();
    $carts[$u['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Usuários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css?v=<?= time() ?>">
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
                <img src="../img/admin.png" alt="admin-icone" style="width:50px; height:50px; object-fit:contain;">
                <span>Painel Admin</span>
            </a>
            <div class="d-flex">
                <a href="../index.php" class="btn btn-outline-light btn-sm me-2">Ver Site</a>
                <a href="../user/logout.php" class="btn btn-outline-light btn-sm">Sair</a>
            </div>
        </div>
    </nav>
    <div class="admin-header text-center py-4">
        <div class="container">
            <h1 class="display-6 fw-bold">Gerenciar Usuários</h1>
            <p class="text-muted mb-0">Veja todos os usuários cadastrados e seus carrinhos</p>
        </div>
    </div>
    <div class="container my-5">
        <?php if (isset($_SESSION['msg'])): ?>
            <div class="alert alert-success text-center mb-4"><?= $_SESSION['msg'];
            unset($_SESSION['msg']); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="card-title mb-4"> Lista de Usuários</h3>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Função</th>
                                <th>Criado em</th>
                                <th>Carrinho</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="badge bg-primary">Administrador</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Usuário</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <?php if (!empty($carts[$user['id']])): ?>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="collapse"
                                                data-bs-target="#cart<?= $user['id'] ?>">Ver Itens</button>
                                            <div id="cart<?= $user['id'] ?>" class="collapse mt-2">
                                                <ul class="list-group">
                                                    <?php foreach ($carts[$user['id']] as $item): ?>
                                                        <li
                                                            class="list-group-item d-flex justify-content-between align-items-center">
                                                            <?= htmlspecialchars($item['product_name']) ?>
                                                            <span class="badge bg-primary">R$
                                                                <?= number_format($item['price'], 2, ',', '.') ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Carrinho vazio</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['role'] !== 'admin'): ?>
                                            <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Tem certeza que deseja excluir este usuário e seu carrinho?');">
                                                Excluir
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>