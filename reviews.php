<?php
require_once ("includes/db.php");
require_once ("includes/product.php");
require_once ("includes/review.php");

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);
$review = new Review($db);


$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$produto = $product->getById($productId);


if (!$produto) {
    header("Location: index.php");
    exit;
}


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$isLoggedIn = isset($_SESSION['user_id']);
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';


$reviews = $review->getByProductId($productId);
$ratingInfo = $review->getAverageRating($productId);


$msg = "";
$msgType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_review'])) {
    $review->product_id = $productId;
    $review->user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $review->user_name = isset($_POST['user_name']) ? trim($_POST['user_name']) : 'Anônimo';
    $review->rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $review->comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';


    if (empty($review->user_name)) {
        $review->user_name = 'Anônimo';
    }

    if ($review->rating < 1 || $review->rating > 5) {
        $msg = "Por favor, selecione uma avaliação de 1 a 5 estrelas.";
        $msgType = "danger";
    } else {
        if ($review->create()) {
            $msg = "Avaliação enviada com sucesso!";
            $msgType = "success";
            $reviews = $review->getByProductId($productId);
            $ratingInfo = $review->getAverageRating($productId);
        } else {
            $msg = "Erro ao enviar avaliação.";
            $msgType = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliações - <?= htmlspecialchars($produto['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">E-commerce</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Início</a></li>
                <li class="nav-item"><a class="nav-link" href="produto.php?id=<?= $productId ?>">Voltar ao Produto</a></li>
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            👤 <?= htmlspecialchars($userName) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <?php if ($userRole === 'admin'): ?>
                                <li><a class="dropdown-item" href="admin/dashboard.php">Painel Admin</a></li>
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

<div class="container" style="padding: 2rem 0;">
    <div class="mb-4">
        <a href="produto.php?id=<?= $productId ?>" class="btn-voltar">
            ← Voltar para o produto
        </a>
    </div>

    <h1 class="mb-4">Avaliações - <?= htmlspecialchars($produto['name']) ?></h1>

    <!-- Média de Avaliações -->
    <div class="average-rating">
        <h3 class="mb-3">Avaliação Média</h3>
        <p class="average-rating-number"><?= number_format($ratingInfo['average'], 1) ?></p>
        <div class="rating-stars mb-2">
            <?php
            $avgRating = $ratingInfo['average'];
            for ($i = 1; $i <= 5; $i++) {
                if ($i <= floor($avgRating)) {
                    echo '★';
                } elseif ($i - 0.5 <= $avgRating) {
                    echo '☆';
                } else {
                    echo '☆';
                }
            }
            ?>
        </div>
        <p class="mb-0">Baseado em <?= $ratingInfo['total'] ?> avaliação(ões)</p>
    </div>

    <!-- Formulário de Avaliação -->
    <div class="review-form">
        <h3 class="mb-4">Deixe sua Avaliação</h3>
        
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Seu Nome</label>
                <input type="text" name="user_name" class="form-control" 
                       value="<?= isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : '' ?>" 
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Avaliação</label>
                <div class="rating-stars" id="ratingInput">
                    <input type="radio" name="rating" value="5" id="star5" required>
                    <label for="star5">★</label>
                    <input type="radio" name="rating" value="4" id="star4">
                    <label for="star4">★</label>
                    <input type="radio" name="rating" value="3" id="star3">
                    <label for="star3">★</label>
                    <input type="radio" name="rating" value="2" id="star2">
                    <label for="star2">★</label>
                    <input type="radio" name="rating" value="1" id="star1">
                    <label for="star1">★</label>
                </div>
                <small class="text-muted">Clique nas estrelas para avaliar</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Comentário</label>
                <textarea name="comment" class="form-control" rows="4" 
                          placeholder="Compartilhe sua experiência com este produto..." required></textarea>
            </div>

            <button type="submit" name="submit_review" class="btn btn-primary">Enviar Avaliação</button>
        </form>
    </div>

    <!-- Lista de Avaliações -->
    <h3 class="mb-4">Avaliações dos Clientes</h3>
    
    <?php if (!empty($reviews)): ?>
        <?php foreach ($reviews as $r): ?>
            <div class="review-card">
                <div class="review-header">
                    <div>
                        <span class="review-author"><?= htmlspecialchars($r['user_name']) ?></span>
                    </div>
                    <div>
                        <div class="rating-stars" style="font-size: 1rem;">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $r['rating'] ? '★' : '☆';
                            }
                            ?>
                        </div>
                        <span class="review-date"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></span>
                    </div>
                </div>
                <p class="mb-0"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <p class="mb-0">Ainda não há avaliações para este produto. Seja o primeiro a avaliar!</p>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Melhorar interatividade das estrelas
    document.querySelectorAll('#ratingInput input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const rating = parseInt(this.value);
            const labels = document.querySelectorAll('#ratingInput label');
            labels.forEach((label, index) => {
                if (index < rating) {
                    label.style.color = '#ffc107';
                } else {
                    label.style.color = '#ddd';
                }
            });
        });
    });
</script>
</body>
</html>

