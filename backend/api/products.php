
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Product.php';

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        getProducts();
        break;
    case 'detail':
        getProductDetail();
        break;
    case 'search':
        searchProducts();
        break;
    case 'featured':
        getFeaturedProducts();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

function getProducts() {
    $filters = [
        'category_id' => $_GET['category'] ?? null,
        'min_price' => $_GET['min_price'] ?? null,
        'max_price' => $_GET['max_price'] ?? null,
        'sort' => $_GET['sort'] ?? 'newest',
        'page' => $_GET['page'] ?? 1,
        'limit' => $_GET['limit'] ?? PRODUCTS_PER_PAGE
    ];
    
    $product = new Product();
    $products = $product->getAll($filters);
    $total = $product->getTotalCount($filters);
    
    jsonResponse([
        'success' => true,
        'products' => $products,
        'pagination' => [
            'page' => (int)$filters['page'],
            'limit' => (int)$filters['limit'],
            'total' => $total,
            'pages' => ceil($total / $filters['limit'])
        ]
    ]);
}

function getProductDetail() {
    $id = $_GET['id'] ?? null;
    $slug = $_GET['slug'] ?? null;
    
    if (!$id && !$slug) {
        jsonResponse(['success' => false, 'message' => 'Product ID or slug required'], 400);
    }
    
    $product = new Product();
    
    if ($slug) {
        $result = $product->getBySlug($slug);
    } else {
        $result = $product->getById($id);
    }
    
    if ($result) {
        jsonResponse(['success' => true, 'product' => $result]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Product not found'], 404);
    }
}

function searchProducts() {
    $query = $_GET['q'] ?? '';
    
    if (empty($query)) {
        jsonResponse(['success' => false, 'message' => 'Search query required'], 400);
    }
    
    $filters = [
        'search' => $query,
        'page' => $_GET['page'] ?? 1,
        'limit' => $_GET['limit'] ?? 20
    ];
    
    $product = new Product();
    $products = $product->getAll($filters);
    
    jsonResponse([
        'success' => true,
        'products' => $products,
        'query' => $query
    ]);
}

function getFeaturedProducts() {
    $limit = $_GET['limit'] ?? 8;
    
    $product = new Product();
    $products = $product->getAll(['featured' => true, 'limit' => $limit]);
    
    jsonResponse(['success' => true, 'products' => $products]);
}
