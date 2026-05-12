*/

<?php
require_once __DIR__ . '/../config/database.php';

class Order {
    private $conn;
    private $table = 'orders';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function create($data) {
        try {
            $this->conn->beginTransaction();
            
            // Insert order
            $orderQuery = "INSERT INTO {$this->table} 
                          (order_number, user_id, total_amount, discount_amount, shipping_fee, 
                           tax_amount, grand_total, payment_method, shipping_name, shipping_phone,
                           shipping_address, shipping_city, shipping_state, shipping_zip, notes)
                          VALUES (:order_number, :user_id, :total_amount, :discount_amount, :shipping_fee,
                                  :tax_amount, :grand_total, :payment_method, :shipping_name, :shipping_phone,
                                  :shipping_address, :shipping_city, :shipping_state, :shipping_zip, :notes)";
            
            $orderStmt = $this->conn->prepare($orderQuery);
            
            $orderNumber = generateOrderNumber();
            
            $orderStmt->bindParam(':order_number', $orderNumber);
            $orderStmt->bindParam(':user_id', $data['user_id']);
            $orderStmt->bindParam(':total_amount', $data['total_amount']);
            $orderStmt->bindParam(':discount_amount', $data['discount_amount']);
            $orderStmt->bindParam(':shipping_fee', $data['shipping_fee']);
            $orderStmt->bindParam(':tax_amount', $data['tax_amount']);
            $orderStmt->bindParam(':grand_total', $data['grand_total']);
            $orderStmt->bindParam(':payment_method', $data['payment_method']);
            $orderStmt->bindParam(':shipping_name', $data['shipping_name']);
            $orderStmt->bindParam(':shipping_phone', $data['shipping_phone']);
            $orderStmt->bindParam(':shipping_address', $data['shipping_address']);
            $orderStmt->bindParam(':shipping_city', $data['shipping_city']);
            $orderStmt->bindParam(':shipping_state', $data['shipping_state']);
            $orderStmt->bindParam(':shipping_zip', $data['shipping_zip']);
            $orderStmt->bindParam(':notes', $data['notes']);
            
            $orderStmt->execute();
            $orderId = $this->conn->lastInsertId();
            
            // Insert order items
            $itemQuery = "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal)
                         VALUES (:order_id, :product_id, :product_name, :quantity, :price, :subtotal)";
            
            $itemStmt = $this->conn->prepare($itemQuery);
            
            foreach ($data['items'] as $item) {
                $subtotal = $item['quantity'] * $item['price'];
                
                $itemStmt->bindParam(':order_id', $orderId);
                $itemStmt->bindParam(':product_id', $item['product_id']);
                $itemStmt->bindParam(':product_name', $item['product_name']);
                $itemStmt->bindParam(':quantity', $item['quantity']);
                $itemStmt->bindParam(':price', $item['price']);
                $itemStmt->bindParam(':subtotal', $subtotal);
                
                $itemStmt->execute();
                
                // Update product stock
                $stockQuery = "UPDATE products SET stock = stock - :quantity WHERE id = :id";
                $stockStmt = $this->conn->prepare($stockQuery);
                $stockStmt->bindParam(':quantity', $item['quantity']);
                $stockStmt->bindParam(':id', $item['product_id']);
                $stockStmt->execute();
            }
            
            $this->conn->commit();
            
            return ['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber];
        } catch(PDOException $e) {
            $this->conn->rollBack();
            logError("Create order error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Order creation failed'];
        }
    }

    public function getById($id, $userId = null) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE id = :id";
            
            if ($userId) {
                $query .= " AND user_id = :user_id";
            }
            
            $query .= " LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($userId) {
                $stmt->bindParam(':user_id', $userId);
            }
            
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            logError("Get order error: " . $e->getMessage());
            return false;
        }
    }

    public function getByOrderNumber($orderNumber, $userId = null) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE order_number = :order_number";
            
            if ($userId) {
                $query .= " AND user_id = :user_id";
            }
            
            $query .= " LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_number', $orderNumber);
            
            if ($userId) {
                $stmt->bindParam(':user_id', $userId);
            }
            
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            logError("Get order by number error: " . $e->getMessage());
            return false;
        }
    }

    public function getOrderItems($orderId) {
        try {
            $query = "SELECT oi.*, p.image, p.slug 
                      FROM order_items oi
                      LEFT JOIN products p ON oi.product_id = p.id
                      WHERE oi.order_id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $orderId);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            logError("Get order items error: " . $e->getMessage());
            return [];
        }
    }

    public function getUserOrders($userId, $page = 1, $limit = ORDERS_PER_PAGE) {
        try {
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT * FROM {$this->table} 
                      WHERE user_id = :user_id 
                      ORDER BY created_at DESC 
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            logError("Get user orders error: " . $e->getMessage());
            return [];
        }
    }

    public function getAllOrders($filters = [], $page = 1, $limit = ORDERS_PER_PAGE) {
        try {
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT o.*, u.full_name, u.email 
                      FROM {$this->table} o
                      LEFT JOIN users u ON o.user_id = u.id
                      WHERE 1=1";
            
            $params = [];
            
            if (!empty($filters['status'])) {
                $query .= " AND o.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['payment_status'])) {
                $query .= " AND o.payment_status = :payment_status";
                $params[':payment_status'] = $filters['payment_status'];
            }
            
            if (!empty($filters['search'])) {
                $query .= " AND (o.order_number LIKE :search OR u.full_name LIKE :search OR u.email LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            $query .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            logError("Get all orders error: " . $e->getMessage());
            return [];
        }
    }

    public function updateStatus($id, $status) {
        try {
            $query = "UPDATE {$this->table} SET status = :status WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            logError("Update order status error: " . $e->getMessage());
            return false;
        }
    }

    public function updatePaymentStatus($id, $paymentStatus) {
        try {
            $query = "UPDATE {$this->table} SET payment_status = :payment_status WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':payment_status', $paymentStatus);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            logError("Update payment status error: " . $e->getMessage());
            return false;
        }
    }
}

/*