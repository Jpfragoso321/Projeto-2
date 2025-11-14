<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("includes/db.php");
require_once("includes/product.php");
require_once("includes/cart.php");

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

$msg = "";
if (isset($_POST['add_to_cart'])) {
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($productId > 0) {
        $produtoData = $product->getById($productId);
        if ($produtoData) {
            if (isset($_SESSION['user_id']) && Cart::add($_SESSION['user_id'], $productId, $quantity)) {
                $msg = "success";
            } else {
                $msg = "error";
            }
        }
    }
}

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$produto = $product->getById($productId);

if (!$produto) {
    header("Location: index.php");
    exit;
}

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? '';
$userRole = $_SESSION['user_role'] ?? '';

Cart::init();
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    $cartCount = Cart::getTotalItems($_SESSION['user_id']);
}


$msgReview = "";
$reviewSuccess = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_review"])) {
    $rating = (int)$_POST["rating"];
    $comment = trim($_POST["comment"]);

    if ($userId && $rating >= 1 && $rating <= 5 && $comment) {
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE product_id = :product_id AND user_id = :user_id");
        $checkStmt->execute([
            ':product_id' => $productId,
            ':user_id' => $userId
        ]);

        if ($checkStmt->fetchColumn() > 0) {
            $msgReview = "⚠️ Você já avaliou este produto.";
        } else {
            $sql = "INSERT INTO reviews (product_id, user_id, rating, comment)
                    VALUES (:product_id, :user_id, :rating, :comment)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(":product_id", $productId);
            $stmt->bindParam(":user_id", $userId);
            $stmt->bindParam(":rating", $rating);
            $stmt->bindParam(":comment", $comment);

            if ($stmt->execute()) {
                $msgReview = "✅ Avaliação enviada com sucesso!";
                $reviewSuccess = true;
            } else {
                $msgReview = "❌ Erro ao enviar avaliação.";
            }
        }
    } else {
        $msgReview = "⚠️ Você precisa estar logado e preencher todos os campos.";
    }
}
$stmt = $db->prepare("
    SELECT r.*, u.name AS user_name 
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = :id 
    ORDER BY r.created_at DESC
");
$stmt->bindParam(":id", $productId);
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($produto['name']) ?> - E-commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css?v=<?= time() ?>">
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
                <li class="nav-item">
                        <?php if ($cartCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $cartCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
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
<div class="container product-detail-container">
    <a href="index.php" class="btn-voltar mb-4">← Voltar para produtos</a>
    
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="product-image-container">
                <img src="img/<?= htmlspecialchars($produto['image'] ?? 'placeholder.png') ?>" 
                     class="product-main-image" 
                     alt="<?= htmlspecialchars($produto['name']) ?>"
                     id="mainImage">
                <div class="product-thumbnails">
                    <img src="img/<?= htmlspecialchars($produto['image'] ?? 'placeholder.png') ?>" 
                         class="product-thumbnail active" 
                         alt="Imagem 1"
                         onclick="changeImage(this.src)">
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="product-info">
                <h1 class="product-title"><?= htmlspecialchars($produto['name']) ?></h1>
                <div class="product-price-container">
                    <div class="product-price-label">Preço</div>
                    <h2 class="product-price-value">R$ <?= number_format($produto['price'], 2, ',', '.') ?></h2>
                </div>                
                <div class="product-description">
                    <h4 class="mb-3">Descrição do Produto</h4>
                    <p><?= nl2br(htmlspecialchars($produto['description'])) ?></p>
                </div>       
                <?php if ($msg === "success"): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Produto adicionado ao carrinho!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <form method="POST" class="mt-4">
                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                    <div class="mb-3">
                        <label class="form-label">Quantidade</label>
                        <input type="number" name="quantity" value="1" min="1" class="form-control" style="max-width: 100px;" required>
                    </div>
                    <button type="submit" name="add_to_cart" class="btn btn-comprar w-100">🛒 Adicionar ao Carrinho</button>
                </form>
                
                <button class="btn btn-outline-primary mt-2 w-100" onclick="comprarProduto()">Comprar Agora</button>
            </div>
        </div>
    </div>
    <hr class="my-5">
    <div class="mt-4">
        <h3 class="mb-4">💬 Avaliações e Comentários</h3>

        <?php if ($msgReview): ?>
            <div class="alert <?= $reviewSuccess ? 'alert-success' : 'alert-warning' ?> text-center">
                <?= htmlspecialchars($msgReview) ?>
            </div>
        <?php endif; ?>

        <?php if ($isLoggedIn): ?>
        <form method="POST" class="mb-5">
            <div class="mb-3">
                <label class="form-label d-block">Sua nota:</label>
                <div class="star-rating" style="font-size: 1.8rem; cursor: pointer;">
                    <input type="hidden" name="rating" id="ratingInput" required>
                    <span onclick="setRating(1)">★</span>
                    <span onclick="setRating(2)">★</span>
                    <span onclick="setRating(3)">★</span>
                    <span onclick="setRating(4)">★</span>
                    <span onclick="setRating(5)">★</span>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Comentário</label>
                <textarea name="comment" class="form-control" rows="4" required></textarea>
            </div>

            <button type="submit" name="submit_review" class="btn btn-primary">Enviar Avaliação</button>
        </form>
        <?php else: ?>
            <p class="text-muted">⚠️ Faça <a href="user/login.php">login</a> para avaliar este produto.</p>
        <?php endif; ?>

        <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $rev): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-1"><?= htmlspecialchars($rev['user_name'] ?? 'Usuário Anônimo') ?></h5>
                        <div class="text-warning mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?= $i <= $rev['rating'] ? '★' : '☆' ?>
                            <?php endfor; ?>
                        </div>
                        <p class="mb-1"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($rev['created_at'])) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">Nenhuma avaliação ainda. Seja o primeiro a comentar!</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function changeImage(src) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.product-thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
        if (thumb.src === src) thumb.classList.add('active');
    });
}
function setRating(value) {
    document.getElementById('ratingInput').value = value;
    document.querySelectorAll('.star-rating span').forEach((s, i) => {
        s.style.color = i < value ? 'gold' : '#ccc';
    });
}
</script>
</body>
</html>
