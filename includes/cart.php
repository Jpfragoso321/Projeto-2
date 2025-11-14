<?php
require_once("db.php");

class Cart {
    private static $db;

    public static function init() {
        if (!isset(self::$db)) {
            $database = new Database();
            self::$db = $database->getConnection();
        }
    }

    public static function add($userId, $productId, $quantity = 1) {
        self::init();
        $stmt = self::$db->prepare("SELECT quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id");
        $stmt->execute([
            ':user_id' => $userId,
            ':product_id' => $productId
        ]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item) {
            $newQty = $item['quantity'] + $quantity;
            $stmt = self::$db->prepare("UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
            return $stmt->execute([
                ':quantity' => $newQty,
                ':user_id' => $userId,
                ':product_id' => $productId
            ]);
        } else {

            $stmt = self::$db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
            return $stmt->execute([
                ':user_id' => $userId,
                ':product_id' => $productId,
                ':quantity' => $quantity
            ]);
        }
    }

    public static function update($userId, $productId, $quantity) {
        self::init();
        if ($quantity <= 0) {
            return self::remove($userId, $productId);
        }
        $stmt = self::$db->prepare("UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
        return $stmt->execute([
            ':quantity' => $quantity,
            ':user_id' => $userId,
            ':product_id' => $productId
        ]);
    }

    public static function remove($userId, $productId) {
        self::init();
        $stmt = self::$db->prepare("DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id");
        return $stmt->execute([
            ':user_id' => $userId,
            ':product_id' => $productId
        ]);
    }

    public static function clear($userId) {
        self::init();
        $stmt = self::$db->prepare("DELETE FROM cart WHERE user_id = :user_id");
        return $stmt->execute([':user_id' => $userId]);
    }

    public static function getItems($userId) {
        self::init();
        $stmt = self::$db->prepare("SELECT product_id, quantity FROM cart WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[$row['product_id']] = ['quantity' => $row['quantity']];
        }
        return $items;
    }

    public static function getTotalItems($userId) {
        self::init();
        $stmt = self::$db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public static function getTotal($userId, $productObj = null) {
        self::init();
        $items = self::getItems($userId);
        $total = 0;
        if ($productObj) {
            foreach ($items as $productId => $item) {
                $data = $productObj->getById($productId);
                if ($data) {
                    $total += $data['price'] * $item['quantity'];
                }
            }
        }
        return $total;
    }

    public static function isEmpty($userId) {
        return self::getTotalItems($userId) === 0;
    }
}
?>
