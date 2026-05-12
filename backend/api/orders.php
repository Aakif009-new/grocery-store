*/

<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Cart.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        createOrder();
        break;
    case 'list':
        getUserOrders();
        break;
    case 'detail':
        getOrderDetail();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

function createOrder() {
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Please login to place order'], 401);
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Get cart items
    $cart = new Cart();
    $userId = getUserId();
    $sessionId = getSessionId();
    $cartItems = $cart->getItems($userId, $sessionId);
    
    if (empty($cartItems)) {
        jsonResponse(['success' => false, 'message' => 'Cart is empty'], 400);
    }
    
    // Calculate totals
    $totalAmount = 0;
    $items = [];
    
    foreach ($cartItems as $item) {
        $subtotal = $item['quantity'] * $item['price'];
        $totalAmount += $subtotal;
        
        $items[] = [
            'product_id' => $item['product_id'],
            'product_name' => $item['name'],
            'quantity' => $item['quantity'],
            'price' => $item['price']
        ];
    }
    
    $shippingFee = $totalAmount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_FEE;
    $taxAmount = $totalAmount * TAX_RATE;
    $discountAmount = 0; // TODO: Apply coupon discount
    $grandTotal = $totalAmount + $shippingFee + $taxAmount - $discountAmount;
    
    // Prepare order data
    $orderData = [
        'user_id' => $userId,
        'total_amount' => $totalAmount,
        'discount_amount' => $discountAmount,
        'shipping_fee' => $shippingFee,
        'tax_amount' => $taxAmount,
        'grand_total' => $grandTotal,
        'payment_method' => sanitize($data['payment_method']),
        'shipping_name' => sanitize($data['shipping_name']),
        'shipping_phone' => sanitize($data['shipping_phone']),
        'shipping_address' => sanitize($data['shipping_address']),
        'shipping_city' => sanitize($data['shipping_city']),
        'shipping_state' => sanitize($data['shipping_state']),
        'shipping_zip' => sanitize($data['shipping_zip']),
        'notes' => sanitize($data['notes'] ?? ''),
        'items' => $items
    ];
    
    $order = new Order();
    $result = $order->create($orderData);
    
    if ($result['success']) {
        // Clear cart after successful order
        $cart->clearCart($userId, $sessionId);
        
        jsonResponse([
            'success' => true,
            'message' => 'Order placed successfully',
            'order_number' => $result['order_number'],
            'order_id' => $result['order_id']
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => $result['message']], 500);
    }
}

function getUserOrders() {
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    $page = $_GET['page'] ?? 1;
    
    $order = new Order();
    $orders = $order->getUserOrders(getUserId(), $page);
    
    jsonResponse(['success' => true, 'orders' => $orders]);
}

function getOrderDetail() {
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    
    $orderId = $_GET['id'] ?? null;
    $orderNumber = $_GET['order_number'] ?? null;
    
    if (!$orderId && !$orderNumber) {
        jsonResponse(['success' => false, 'message' => 'Order ID or number required'], 400);
    }
    
    $order = new Order();
    
    if ($orderNumber) {
        $orderData = $order->getByOrderNumber($orderNumber, getUserId());
    } else {
        $orderData = $order->getById($orderId, getUserId());
    }
    
    if ($orderData) {
        $items = $order->getOrderItems($orderData['id']);
        
        jsonResponse([
            'success' => true,
            'order' => $orderData,
            'items' => $items
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Order not found'], 404);
    }
}

/*