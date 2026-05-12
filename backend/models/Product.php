<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class Product {

    private PDO $conn;
    private string $table = 'products';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /* ================================
       GET ALL PRODUCTS (WITH FILTERS)
    ================================= */
    public function getAll(array $filters = []): array {
        try {
            $query = "
                SELECT 
                    p.*,
                    c.name AS category_name,
                    COALESCE((
                        SELECT AVG(r.rating) 
                        FROM reviews r 
                        WHERE r.product_id = p.id
                    ), 0) AS avg_rating,
                    (
                        SELECT COUNT(*) 
                        FROM reviews r 
                        WHERE r.product_id = p.id
                    ) AS review_count
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = 1
            ";

            $params = [];

            /* Category filter */
            if (!empty($filters['category_id'])) {
                $query .= " AND p.category_id = :category_id";
                $params[':category_id'] = (int)$filters['category_id'];
            }

            /* Search filter */
            if (!empty($filters['search'])) {
                $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            /* Price filters */
            if (isset($filters['min_price'])) {
                $query .= " AND p.price >= :min_price";
                $params[':min_price'] = (float)$filters['min_price'];
            }

            if (isset($filters['max_price'])) {
                $query .= " AND p.price <= :max_price";
                $params[':max_price'] = (float)$filters['max_price'];
            }

            /* Featured */
            if (!empty($filters['featured'])) {
                $query .= " AND p.is_featured = 1";
            }

            /* Sorting */
            switch ($filters['sort'] ?? 'newest') {
                case 'price_low':
                    $query .= " ORDER BY p.price ASC";
                    break;
                case 'price_high':
                    $query .= " ORDER BY p.price DESC";
                    break;
                case 'popular':
                    $query .= " ORDER BY p.views DESC";
                    break;
                case 'rating':
                    $query .= " ORDER BY avg_rating DESC";
                    break;
                default:
                    $query .= " ORDER BY p.created_at DESC";
            }

            /* Pagination (SAFE for MySQL) */
            $page  = max(1, (int)($filters['page'] ?? 1));
            $limit = (int)($filters['limit'] ?? PRODUCTS_PER_PAGE);
            $offset = ($page - 1) * $limit;

            $query .= " LIMIT $limit OFFSET $offset";

            $stmt = $this->conn->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        }
    }

    /* ================================
       GET PRODUCT BY ID
    ================================= */
    public function getById(int $id): ?array {
        if ($id <= 0) {
            return null;
        }

        try {
            $query = "
                SELECT 
                    p.*,
                    c.name AS category_name,
                    COALESCE((
                        SELECT AVG(r.rating) 
                        FROM reviews r 
                        WHERE r.product_id = p.id
                    ), 0) AS avg_rating,
                    (
                        SELECT COUNT(*) 
                        FROM reviews r 
                        WHERE r.product_id = p.id
                    ) AS review_count
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = :id AND p.is_active = 1
                LIMIT 1
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                $update = $this->conn->prepare(
                    "UPDATE {$this->table} SET views = views + 1 WHERE id = :id"
                );
                $update->bindValue(':id', $id, PDO::PARAM_INT);
                $update->execute();
            }

            return $product ?: null;

        } catch (PDOException $e) {
            return null;
        }
    }

    /* ================================
       GET PRODUCT BY SLUG
    ================================= */
    public function getBySlug(string $slug): ?array {
        if (empty($slug)) {
            return null;
        }

        try {
            $query = "
                SELECT 
                    p.*,
                    c.name AS category_name
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.slug = :slug AND p.is_active = 1
                LIMIT 1
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
            $stmt->execute();

            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                $this->conn->prepare(
                    "UPDATE {$this->table} SET views = views + 1 WHERE id = :id"
                )->execute([':id' => $product['id']]);
            }

            return $product ?: null;

        } catch (PDOException $e) {
            return null;
        }
    }

    /* ================================
       TOTAL COUNT (FOR PAGINATION)
    ================================= */
    public function getTotalCount(array $filters = []): int {
        try {
            $query = "SELECT COUNT(*) FROM {$this->table} WHERE is_active = 1";
            $params = [];

            if (!empty($filters['category_id'])) {
                $query .= " AND category_id = :category_id";
                $params[':category_id'] = (int)$filters['category_id'];
            }

            if (!empty($filters['search'])) {
                $query .= " AND (name LIKE :search OR description LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            $stmt = $this->conn->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            return (int)$stmt->fetchColumn();

        } catch (PDOException $e) {
            return 0;
        }
    }

    /* ================================
       CREATE PRODUCT
    ================================= */
    public function create(array $data): bool {
        try {
            $query = "
                INSERT INTO {$this->table}
                (name, slug, description, price, original_price, category_id,
                 brand, image, stock, unit, is_featured, is_active, created_at)
                VALUES
                (:name, :slug, :description, :price, :original_price, :category_id,
                 :brand, :image, :stock, :unit, :is_featured, 1, NOW())
            ";

            $stmt = $this->conn->prepare($query);

            return $stmt->execute([
                ':name' => $data['name'],
                ':slug' => $data['slug'],
                ':description' => $data['description'],
                ':price' => $data['price'],
                ':original_price' => $data['original_price'],
                ':category_id' => $data['category_id'],
                ':brand' => $data['brand'],
                ':image' => $data['image'],
                ':stock' => $data['stock'],
                ':unit' => $data['unit'],
                ':is_featured' => (int)$data['is_featured']
            ]);

        } catch (PDOException $e) {
            return false;
        }
    }
}
