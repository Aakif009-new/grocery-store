<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Cart.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        handleAddToCart();
        break;
    case 'get':
        handleGetCart();
        break;
    case 'count':
        handleGetCartCount();
        break;
    case 'update_quantity':
        handleUpdateQuantity();
        break;
    case 'remove':
        handleRemoveItem();
        break;
    case 'clear':
        handleClearCart();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

function getCartContext(): array
{
    $userId = getUserId();
    $sessionId = getSessionId();

    return [$userId, $sessionId];
}

function handleAddToCart(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $productId = (int)($data['product_id'] ?? 0);
    $quantity = (int)($data['quantity'] ?? 1);

    if ($productId <= 0 || $quantity <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid product or quantity'], 422);
    }

    [$userId, $sessionId] = getCartContext();

    $cart = new Cart();
    $ok = $cart->addItem($productId, $quantity, $userId, $sessionId);

    if (!$ok) {
        jsonResponse(['success' => false, 'message' => 'Failed to add to cart'], 500);
    }

    $count = $cart->getCartCount($userId, $sessionId);

    jsonResponse([
        'success' => true,
        'message' => 'Added to cart',
        'count' => $count,
    ]);
}

function handleGetCart(): void
{
    [$userId, $sessionId] = getCartContext();
    $cart = new Cart();
    $items = $cart->getItems($userId, $sessionId);

    jsonResponse([
        'success' => true,
        'items' => $items,
    ]);
}

function handleGetCartCount(): void
{
    [$userId, $sessionId] = getCartContext();
    $cart = new Cart();
    $count = $cart->getCartCount($userId, $sessionId);

    jsonResponse([
        'success' => true,
        'count' => (int)$count,
    ]);
}

function handleUpdateQuantity(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $cartId = (int)($data['cart_id'] ?? 0);
    $quantity = (int)($data['quantity'] ?? 0);

    if ($cartId <= 0 || $quantity < 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid cart item or quantity'], 422);
    }

    [$userId, $sessionId] = getCartContext();
    $cart = new Cart();

    if ($quantity === 0) {
        $ok = $cart->removeItem($cartId, $userId, $sessionId);
    } else {
        $ok = $cart->updateQuantity($cartId, $quantity, $userId, $sessionId);
    }

    if (!$ok) {
        jsonResponse(['success' => false, 'message' => 'Failed to update cart'], 500);
    }

    jsonResponse(['success' => true]);
}

function handleRemoveItem(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $cartId = (int)($data['cart_id'] ?? 0);

    if ($cartId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid cart item'], 422);
    }

    [$userId, $sessionId] = getCartContext();
    $cart = new Cart();
    $ok = $cart->removeItem($cartId, $userId, $sessionId);

    if (!$ok) {
        jsonResponse(['success' => false, 'message' => 'Failed to remove item'], 500);
    }

    jsonResponse(['success' => true]);
}

function handleClearCart(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
    }

    [$userId, $sessionId] = getCartContext();
    $cart = new Cart();
    $ok = $cart->clearCart($userId, $sessionId);

    if (!$ok) {
        jsonResponse(['success' => false, 'message' => 'Failed to clear cart'], 500);
    }

    jsonResponse(['success' => true]);
}

