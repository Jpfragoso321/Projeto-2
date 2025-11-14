<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once("includes/db.php");
require_once("includes/product.php");
require_once("includes/cart.php");

Cart::init();

$userId = $_SESSION['user_id'] ?? 0;
$msg = "";

if (isset($_POST['action'])) {
    $productId = (int) ($_POST['product_id'] ?? 0);
    switch ($_POST['action']) {
        case 'remove':
            Cart::remove($userId, $productId);
            $msg = "Produto removido do carrinho!";
            break;
        case 'update':
            $quantity = (int) ($_POST['quantity'] ?? 1);
            Cart::update($userId, $productId, $quantity);
            $msg = "Quantidade atualizada!";
            break;
        case 'clear':
            Cart::clear($userId);
            $msg = "Carrinho limpo!";
            break;
    }
}

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

$cartItems = Cart::getItems($userId);
$cartTotal = Cart::getTotal($userId, $product);
$cartCount = array_sum(array_column($cartItems, 'quantity'));
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
$userRole = $_SESSION['user_role'] ?? '';

// CORREÇÃO: Criar um novo array para os itens processados
$processedItems = [];
foreach ($cartItems as $productId => $item) {
    $data = $product->getById($productId);
    if ($data) {
        $processedItems[] = [
            'product_id' => $productId,
            'name' => $data['name'],
            'price' => $data['price'],
            'image' => $data['image'],
            'quantity' => $item['quantity']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho de Compras - E-commerce</title>
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
                        <a class="nav-link position-relative active" href="carrinho.php">
                            🛒 Carrinho
                            <?php if ($cartCount > 0): ?>
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $cartCount ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
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
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
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
            <a href="index.php" class="btn-voltar">← Continuar Comprando</a>
        </div>

        <h1 class="mb-4"> Meu Carrinho</h1>
        <?php if ($msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (Cart::isEmpty($userId)): ?>
            <div class="text-center py-5">
                <div class="mb-4" style="font-size: 5rem;">🛒</div>
                <h3 class="text-muted">Seu carrinho está vazio</h3>
                <p class="text-muted mb-4">Adicione produtos ao carrinho para continuar comprando</p>
                <a href="index.php" class="btn btn-primary btn-lg">Ver Produtos</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="mb-0">Itens no Carrinho (<?= $cartCount ?>)</h4>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="clear">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Tem certeza que deseja limpar o carrinho?')">
                                        Limpar Carrinho
                                    </button>
                                </form>
                            </div>
                            <?php foreach ($processedItems as $item): ?>
                                <div class="cart-item border-bottom pb-4 mb-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <img src="img/<?= htmlspecialchars($item['image'] ?? 'placeholder.png') ?>"
                                                class="img-fluid rounded" alt="<?= htmlspecialchars($item['name']) ?>"
                                                style="max-height: 100px; object-fit: cover;">
                                        </div>
                                        <div class="col-md-4">
                                            <h5 class="mb-1"><?= htmlspecialchars($item['name']) ?></h5>
                                            <p class="text-muted mb-0">R$ <?= number_format($item['price'], 2, ',', '.') ?></p>
                                        </div>
                                        <div class="col-md-3">
                                            <form method="POST" class="d-flex align-items-center gap-2">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                                <label class="form-label mb-0">Qtd:</label>
                                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1"
                                                    class="form-control" style="max-width: 80px;" onchange="this.form.submit()">
                                            </form>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <strong>R$
                                                <?= number_format($item['price'] * $item['quantity'], 2, ',', '.') ?></strong>
                                        </div>
                                        <div class="col-md-1 text-end">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Remover este item?')">✕</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card shadow-sm sticky-top" style="top: 20px;">
                        <div class="card-body">
                            <h4 class="mb-4">Resumo do Pedido</h4>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal (<?= $cartCount ?> itens):</span>
                                <strong>R$ <?= number_format($cartTotal, 2, ',', '.') ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Frete:</span>
                                <span class="text-success">Grátis</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4">
                                <h5>Total:</h5>
                                <h4 class="text-primary">R$ <?= number_format($cartTotal, 2, ',', '.') ?></h4>
                            </div>
                            <button class="btn btn-primary btn-lg w-100 mb-2"
                                onclick="alert('Funcionalidade de checkout em desenvolvimento!')">
                                Finalizar Compra
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary w-100">
                                Continuar Comprando
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>