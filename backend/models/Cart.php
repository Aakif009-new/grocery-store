*/

<?php
require_once __DIR__ . '/../config/database.php';

class Cart {
    private $conn;
    private $table = 'cart';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function addItem($productId, $quantity, $userId = null, $sessionId = null) {
        try {
            // Check if item already exists
            $checkQuery = "SELECT id, quantity FROM {$this->table} 
                          WHERE product_id = :product_id AND ";
            
            if ($userId) {
                $checkQuery .= "user_id = :user_id";
            } else {
                $checkQuery .= "session_id = :session_id";
            }
            
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':product_id', $productId);
            
            if ($userId) {
                $checkStmt->bindParam(':user_id', $userId);
            } else {
                $checkStmt->bindParam(':session_id', $sessionId);
            }
            
            $checkStmt->execute();
            $existing = $checkStmt->fetch();
            
            if ($existing) {
                // Update quantity
                $newQuantity = $existing['quantity'] + $quantity;
                $updateQuery = "UPDATE {$this->table} SET quantity = :quantity WHERE id = :id";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':quantity', $newQuantity);
                $updateStmt->bindParam(':id', $existing['id']);
                return $updateStmt->execute();
            } else {
                // Insert new item
                $insertQuery = "INSERT INTO {$this->table} (user_id, session_id, product_id, quantity) 
                               VALUES (:user_id, :session_id, :product_id, :quantity)";
                $insertStmt = $this->conn->prepare($insertQuery);
                $insertStmt->bindParam(':user_id', $userId);
                $insertStmt->bindParam(':session_id', $sessionId);
                $insertStmt->bindParam(':product_id', $productId);
                $insertStmt->bindParam(':quantity', $quantity);
                return $insertStmt->execute();
            }
        } catch(PDOException $e) {
            logError("Add to cart error: " . $e->getMessage());
            return false;
        }
    }

    public function getItems($userId = null, $sessionId = null) {
        try {
            $query = "SELECT c.*, p.name, p.slug, p.price, p.image, p.stock, p.unit
                      FROM {$this->table} c
                      INNER JOIN products p ON c.product_id = p.id
                      WHERE p.is_active = 1 AND ";
            
            if ($userId) {
                $query .= "c.user_id = :user_id";
            } else {
                $query .= "c.session_id = :session_id";
            }
            
            $stmt = $this->conn->prepare($query);
            
            if ($userId) {
                $stmt->bindParam(':user_id', $userId);
            } else {
                $stmt->bindParam(':session_id', $sessionId);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            logError("Get cart items error: " . $e->getMessage());
            return [];
        }
    }

    public function updateQuantity($cartId, $quantity, $userId = null, $sessionId = null) {
        try {
            $query = "UPDATE {$this->table} SET quantity = :quantity WHERE id = :id AND ";
            
            if ($userId) {
                $query .= "user_id = :user_id";
            } else {
                $query .= "session_id = :session_id";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':id', $cartId);
            
            if ($userId) {
                $stmt->bindParam(':user_id', $userId);
            } else {
                $stmt->bindParam(':session_id', $sessionId);
            }
            
            return $stmt->execute();
        } catch(PDOException $e) {
            logError("Update cart quantity error: " . $e->getMessage());
            return false;
        }
    }

    public function removeItem($cartId, $userId = null, $sessionId = null) {
        try {
            $query = "DELETE FROM {$this->table} WHERE id = :id AND ";
            
            if ($userId) {
                $query .= "user_id = :user_id";
            } else {
                $query .= "session_id = :session_id";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $cartId);
            
            if ($userId) {
                $stmt->bindParam(':user_id', $userId);
            } else {
                $stmt->bindParam(':session_id', $sessionId);
            }
            
            return $stmt->execute();
        } catch(PDOException $e) {
            logError("Remove cart item error: " . $e->getMessage());
            return false;
        }
    }

    public function clearCart($userId = null, $sessionId = null) {
        try {
            $query = "DELETE FROM {$this->table} WHERE ";
            
            if ($userId) {
                $query .= "user_id = :user_id";
            } else {
                $query .= "session_id = :session_id";
            }
            
            $stmt = $this->conn->prepare($query);
            
            if ($userId) {
                $stmt->bindParam(':user_id', $userId);
            } else {
                $stmt->bindParam(':session_id', $sessionId);
            }
            
            return $stmt->execute();
        } catch(PDOException $e) {
            logError("Clear cart error: " . $e->getMessage());
            return false;
        }
    }

    public function getCartTotal($userId = null, $sessionId = null) {
        try {
            $query = "SELECT SUM(c.quantity * p.price) as total
                      FROM {$this->table} c
                      INNER JOIN products p ON c.product_id = p.id
                      WHERE ";
            
            if ($userId) {
                $query .= "c.user_id = :user_id";
            } else {
                $query .= "c.session_id = :session_id";
            }
            
            $stmt = $this->conn->prepare($query);
            
            if ($userId) {
                $stmt->bindParam(':user_id', $userId);
            } else {
                $stmt->bindParam(':session_id', $sessionId);
            }
            
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch(PDOException $e) {
            logError("Get cart total error: " . $e->getMessage());
            return 0;
        }
    }

    public function getCartCount($userId = null, $sessionId = null) {
        try {
            $query = "SELECT SUM(quantity) as count FROM {$this->table} WHERE ";
            
            if ($userId) {
                $query .= "user_id = :user_id";
            } else {
                $query .= "session_id = :session_id";
            }
            
            $stmt = $this->conn->prepare($query);
            
            if ($userId) {
                $stmt->bindParam(':user_id', $userId);
            } else {
                $stmt->bindParam(':session_id', $sessionId);
            }
            
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch(PDOException $e) {
            logError("Get cart count error: " . $e->getMessage());
            return 0;
        }
    }

    public function mergeCart($sessionId, $userId) {
        try {
            $query = "UPDATE {$this->table} SET user_id = :user_id 
                      WHERE session_id = :session_id AND user_id IS NULL";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':session_id', $sessionId);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            logError("Merge cart error: " . $e->getMessage());
            return false;
        }
    }
}

/*