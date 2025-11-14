<?php
require_once("../includes/db.php");
require_once("../includes/product.php");
require_once("../includes/authADM.php");
require_once("../includes/authProcess.php");

$database = new Database();
$db = $database->getConnection();

$auth = new Autenticar($db);
$auth->apenasAdmin();

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$product = new Product($db);
$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_product'])) {

  if (isset($_SESSION['last_product_submit']) && (time() - $_SESSION['last_product_submit']) < 2) {
    $msg = "⚠️ Aguarde alguns segundos antes de cadastrar novamente.";
  } else {
    $_SESSION['last_product_submit'] = time();

    $product->name = trim($_POST["name"]);
    $product->description = trim($_POST["description"]);
    $product->price = $_POST["price"];
    $product->category = isset($_POST["category"]) ? trim($_POST["category"]) : '';

    if (!empty($_FILES["image"]["name"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
      $targetDir = realpath(__DIR__ . "/../img");
      if (!$targetDir) {
        $targetDir = dirname(__DIR__) . "/img";
      }

      if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
      }

      $imageName = uniqid("prod_") . ".png";
      $targetFile = $targetDir . "/" . $imageName;

      $imageTmp = $_FILES["image"]["tmp_name"];
      $imageType = mime_content_type($imageTmp);

      switch ($imageType) {
        case "image/jpeg":
        case "image/jpg":
          $imageResource = imagecreatefromjpeg($imageTmp);
          break;
        case "image/png":
          $imageResource = imagecreatefrompng($imageTmp);
          break;
        case "image/webp":
          $imageResource = imagecreatefromwebp($imageTmp);
          break;
        case "image/gif":
          $imageResource = imagecreatefromgif($imageTmp);
          break;
        default:
          $msg = "❌ Tipo de imagem não suportado.";
          $imageResource = null;
          break;
      }

      if ($imageResource) {
        imagepng($imageResource, $targetFile);
        imagedestroy($imageResource);
        $product->image = $imageName;
      } else {
        $product->image = "placeholder.png";
      }
    } else {
      $product->image = "placeholder.png";
    }

    if (empty($msg)) {
      if ($product->create()) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit;
      } else {
        $checkQuery = "SELECT id FROM products WHERE name = :name LIMIT 1";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(":name", $product->name);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
          $msg = "❌ Já existe um produto com este nome. Use outro nome.";
        } else {
          $msg = "❌ Erro ao cadastrar produto. Verifique os dados e tente novamente.";
        }
      }
    }
  }
}

if (isset($_GET['success']) && $_GET['success'] == 1) {
  $msg = "✅ Produto cadastrado com sucesso!";
}

$produtos = $product->getAll();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Adicionar Produto</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/styles.css">
</head>

<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
        <span>Painel Admin</span>
      </a>
      <div class="d-flex">
        <a href="../index.php" class="btn btn-outline-light btn-sm me-2">Ver Site</a>
        <a href="../index.php" class="btn btn-outline-light btn-sm">Voltar</a>
      </div>
    </div>
  </nav>
  <div class="admin-header">
    <div class="container text-center">
      <h1 class="display-5 fw-bold mb-0">Adicionar Produto</h1>
      <p class="mb-0 mt-2">Gerencie seus produtos</p>
    </div>
  </div>

  <div class="container admin-container">
    <div class="form-card">
      <h2>Cadastrar Novo Produto</h2>
      <?php if ($msg): ?>
        <div class="alert alert-info text-center mb-4"><?= $msg ?></div>
      <?php endif; ?>
      <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label">Nome do Produto</label>
          <input type="text" name="name" class="form-control" required placeholder="Digite o nome do produto">
        </div>
        <div class="mb-3">
          <label class="form-label">Descrição</label>
          <textarea name="description" class="form-control" rows="4" required
            placeholder="Descreva o produto"></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Preço (R$)</label>
          <input type="number" step="0.01" name="price" class="form-control" required placeholder="0.00" min="0">
        </div>
        <div class="mb-3">
          <label class="form-label">Categoria</label>
          <select name="category" class="form-control">
            <option value="">Selecione uma categoria</option>
            <option value="Moda">👗 Moda</option>
            <option value="Esporte">⚽ Esporte</option>
            <option value="Tecnologia">💻 Tecnologia</option>
            <option value="Casa">🏠 Casa e Decoração</option>
            <option value="Beleza">💄 Beleza e Cuidados</option>
            <option value="Livros">📚 Livros</option>
            <option value="Brinquedos">🧸 Brinquedos</option>
            <option value="Automotivo">🚗 Automotivo</option>
            <option value="Alimentação">🍔 Alimentação</option>
            <option value="Saúde">💊 Saúde e Bem-estar</option>
          </select>
        </div>
        <div class="mb-4">
          <label class="form-label">Imagem do Produto</label>
          <input type="file" name="image" class="form-control" accept="image/*">
        </div>
        <button type="submit" name="submit_product" class="btn btn-submit">Cadastrar Produto</button>
      </form>
    </div>

    <div class="products-section">
      <h3>Produtos Cadastrados</h3>
      <div class="row">
        <?php if (!empty($produtos)): ?>
          <?php foreach ($produtos as $p): ?>
            <div class="col-md-4 mb-4">
              <div class="card h-100 shadow-sm">
                <img src="../img/<?= htmlspecialchars($p['image']) ?>" class="card-img-top"
                  alt="<?= htmlspecialchars($p['name']) ?>" style="height:220px; object-fit:cover;">
                <div class="card-body text-center">
                  <h5 class="card-title"><?= htmlspecialchars($p['name']) ?></h5>
                  <p class="price-badge mb-3">R$ <?= number_format($p['price'], 2, ',', '.') ?></p>
                  <a href="../produto.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-sm" target="_blank">
                    Ver no Site
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-12">
            <p class="text-center text-muted">Nenhum produto cadastrado ainda.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>