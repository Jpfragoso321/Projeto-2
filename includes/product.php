<?php
class Product {
    private $conn;
    private $table = "products";

    public $name;
    public $description;
    public $price;
    public $image;
    public $category;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $checkQuery = "SELECT id FROM " . $this->table . " WHERE name = :name LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":name", $this->name);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            return false;
        }
        
        try {
            $checkQuery = "SHOW COLUMNS FROM " . $this->table . " LIKE 'category'";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute();
            $hasCategory = $checkStmt->rowCount() > 0;
        } catch (Exception $e) {
            exit($e->getMessage());
        }

        if ($hasCategory) {
            $query = "INSERT INTO " . $this->table . " (name, description, price, image, category)
                      VALUES (:name, :description, :price, :image, :category)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":price", $this->price);
            $stmt->bindParam(":image", $this->image);
            $stmt->bindParam(":category", $this->category);
        } else {
            $query = "INSERT INTO " . $this->table . " (name, description, price, image)
                      VALUES (:name, :description, :price, :image)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":price", $this->price);
            $stmt->bindParam(":image", $this->image);
        }

        if (!$stmt->execute()) {
    $errorInfo = $stmt->errorInfo();
    echo "Erro ao inserir: " . $errorInfo[2];
    return false;
}
    return true;
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByCategory($category) {
        try {
            $checkQuery = "SHOW COLUMNS FROM " . $this->table . " LIKE 'category'";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute();
            $hasCategory = $checkStmt->rowCount() > 0;
        } catch (Exception $e) {
            $hasCategory = false;
        }

        if ($hasCategory) {
            $query = "SELECT * FROM " . $this->table . " WHERE category = :category ORDER BY id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":category", $category);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return $this->getAll();
        }
    }

    public function getCategories() {
        try {
            $checkQuery = "SHOW COLUMNS FROM " . $this->table . " LIKE 'category'";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute();
            $hasCategory = $checkStmt->rowCount() > 0;
        } catch (Exception $e) {
            $hasCategory = false;
        }

        if ($hasCategory) {
            $query = "SELECT DISTINCT category FROM " . $this->table . " WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $categories;
        }
        
        return [];
    }
}
?>
