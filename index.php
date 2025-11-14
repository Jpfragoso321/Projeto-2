<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("includes/db.php");
require_once("includes/product.php");
require_once("includes/review.php");
require_once("includes/cart.php");

$database = new Database();
$db = $database->getConnection();

$product = new Product($db);
$review = new Review($db);

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
$userRole = $_SESSION['user_role'] ?? '';

$products = $product->getAll();

foreach ($products as &$p) {
    $ratingInfo = $review->getAverageRating($p['id']);
    $p['average_rating'] = $ratingInfo['average'];
    $p['total_reviews'] = $ratingInfo['total'];
}

$cartCount = 0;
$cartItems = [];

if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    $cartCount = Cart::getTotalItems($userId);
    $cartItems = Cart::getItems($userId);
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-commerce</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">E-commerce</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-auto">
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="carrinho.php">
                            🛒 Carrinho
                            <?php if ($cartCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $cartCount ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            👤 <?= htmlspecialchars($userName) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <?php if ($userRole === 'admin'): ?>
                                <li><a class="dropdown-item" href="admin/dashboard.php">Adicionar Produto</a></li>
                                <li><a class="dropdown-item" href="admin/listarUser.php">Painel Admin</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="user/logout.php">Sair</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="user/login.php">Entrar</a></li>
                    <li class="nav-item"><a class="nav-link" href="user/register.php">Cadastrar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<header class="bg-light text-center py-5 mb-5">
    <div class="container">
        <h1 class="display-5 fw-bold">Welcome!</h1>
        <p class="text-muted">Os melhores produtos com os melhores preços</p>
    </div>
</header>
<div class="container">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Navegar por Categorias</h5>
            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <a href="categorias.php?categoria=Moda" class="btn btn-outline-primary btn-sm">👗 Moda</a>
                <a href="categorias.php?categoria=Esporte" class="btn btn-outline-primary btn-sm">⚽ Esporte</a>
                <a href="categorias.php?categoria=Tecnologia" class="btn btn-outline-primary btn-sm">💻 Tecnologia</a>
                <a href="categorias.php?categoria=Casa" class="btn btn-outline-primary btn-sm">🏠 Casa e Decoração</a>
                <a href="categorias.php?categoria=Beleza" class="btn btn-outline-primary btn-sm">💄 Beleza</a>
                <a href="categorias.php?categoria=Livros" class="btn btn-outline-primary btn-sm">📚 Livros</a>
                <a href="categorias.php?categoria=Brinquedos" class="btn btn-outline-primary btn-sm">🧸 Brinquedos</a>
                <a href="categorias.php?categoria=Automotivo" class="btn btn-outline-primary btn-sm">🚗 Automotivo</a>
                <a href="categorias.php?categoria=Alimentação" class="btn btn-outline-primary btn-sm">🍔 Alimentação</a>
                <a href="categorias.php?categoria=Saúde" class="btn btn-outline-primary btn-sm">💊 Saúde</a>
                <a href="categorias.php" class="btn btn-primary btn-sm">Ver Todas</a>
            </div>
        </div>
    </div>
    <div class="row">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $p): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <img src="img/<?= htmlspecialchars($p['image'] ?? 'placeholder.png') ?>"
                             class="card-img-top" alt="<?= htmlspecialchars($p['name']) ?>">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?= htmlspecialchars($p['name']) ?></h5>
                            <?php if (!empty($p['category'])): ?>
                                <span class="badge bg-primary mb-2"><?= htmlspecialchars($p['category']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary mb-2">Sem categoria</span>
                            <?php endif; ?>
                            <div class="mb-2">
                                <?php if ($p['total_reviews'] > 0): ?>
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <span style="color: #ffc107; font-size: 1.1rem;">
                                            <?php
                                            $avg = $p['average_rating'];
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= floor($avg) ? '★' : '☆';
                                            }
                                            ?>
                                        </span>
                                        <span class="text-muted small">
                                            <?= number_format($avg, 1) ?> (<?= $p['total_reviews'] ?>)
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">Sem avaliações ainda</span>
                                <?php endif; ?>
                            </div>

                            <p class="price mb-3">R$ <?= number_format($p['price'], 2, ',', '.') ?></p>
                            <a href="produto.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-sm">Ver Detalhes</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center">Nenhum produto disponível.</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
