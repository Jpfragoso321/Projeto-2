<?php
/**
 * Classe para gerenciar avaliações de produtos
 */
class Review {
    private $conn;
    private $table = "reviews";

    public $id;
    public $product_id;
    public $user_id;
    public $user_name;
    public $rating;
    public $comment;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Criar nova avaliação
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . " (product_id, user_id, user_name, rating, comment, created_at)
                  VALUES (:product_id, :user_id, :user_name, :rating, :comment, NOW())";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":product_id", $this->product_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":user_name", $this->user_name);
        $stmt->bindParam(":rating", $this->rating);
        $stmt->bindParam(":comment", $this->comment);

        return $stmt->execute();
    }

    /**
     * Buscar todas as avaliações de um produto
     */
    public function getByProductId($productId) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE product_id = :product_id 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":product_id", $productId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcular média de avaliações de um produto
     */
    public function getAverageRating($productId) {
        $query = "SELECT AVG(rating) as average, COUNT(*) as total 
                  FROM " . $this->table . " 
                  WHERE product_id = :product_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":product_id", $productId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'average' => $result['average'] ? round($result['average'], 1) : 0,
            'total' => (int)$result['total']
        ];
    }

    /**
     * Verificar se usuário já avaliou o produto
     */
    public function hasUserReviewed($productId, $userId) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                  WHERE product_id = :product_id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":product_id", $productId);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
}

