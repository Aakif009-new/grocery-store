<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/User.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'check':
        handleCheck();
        break;
    case 'profile':
        handleProfile();
        break;
    case 'update_profile':
        handleUpdateProfile();
        break;
    case 'logout':
        handleLogout();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

function handleLogin(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Email and password are required'], 422);
    }

    if (!validateEmail($email)) {
        jsonResponse(['success' => false, 'message' => 'Invalid email address'], 422);
    }

    $userModel = new User();
    $user = $userModel->getByEmail($email);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['is_admin'] = (int)($user['is_admin'] ?? 0);

    jsonResponse([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'is_admin' => (int)($user['is_admin'] ?? 0),
        ],
    ]);
}

function handleCheck(): void
{
    jsonResponse([
        'success' => true,
        'logged_in' => isLoggedIn(),
    ]);
}

function handleProfile(): void
{
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $userModel = new User();
    $profile = $userModel->getById(getUserId());

    if (!$profile) {
        jsonResponse(['success' => false, 'message' => 'User not found'], 404);
    }

    jsonResponse([
        'success' => true,
        'profile' => $profile,
    ]);
}

function handleUpdateProfile(): void
{
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $profileData = [
        'full_name' => sanitize($data['full_name'] ?? ''),
        'phone' => sanitize($data['phone'] ?? ''),
        'address' => sanitize($data['address'] ?? ''),
        'city' => sanitize($data['city'] ?? ''),
        'state' => sanitize($data['state'] ?? ''),
        'zip_code' => sanitize($data['zip_code'] ?? ''),
    ];

    if ($profileData['full_name'] === '') {
        jsonResponse(['success' => false, 'message' => 'Full name is required'], 422);
    }

    $userModel = new User();
    $updated = $userModel->updateProfile(getUserId(), $profileData);

    if (!$updated) {
        jsonResponse(['success' => false, 'message' => 'Failed to update profile'], 500);
    }

    jsonResponse(['success' => true, 'message' => 'Profile updated successfully']);
}

    function handleLogout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            session_destroy();
        }

        jsonResponse(['success' => true]);
    }

