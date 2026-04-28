<?php

session_start();

require_once __DIR__ . '/../Model/Config.php';
require_once __DIR__ . '/../Helpers/LoginAndRegister.php';

header('Content-Type: application/json; charset=UTF-8');

/** Web root path of this app (e.g. /food-order-app) for redirects */
function app_web_root(): string
{
    $root = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')));
    return $root === '/' || $root === '\\' ? '' : rtrim($root, '/');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$conn = Config::getConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'register') {

    $shopTitle   = trim($_POST['shopTitle'] ?? '');
    $regEmail    = trim($_POST['regEmail'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $siteUrl     = trim($_POST['siteUrl'] ?? '');
    $regPassword = $_POST['regPassword'] ?? '';

    if ($shopTitle === '' || $regEmail === '' || $regPassword === '') {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        $conn->close();
        exit;
    }

    if (!filter_var($regEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        $conn->close();
        exit;
    }

    if ($siteUrl !== '' && !filter_var($siteUrl, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid website URL']);
        $conn->close();
        exit;
    }

    if (strlen($regPassword) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        $conn->close();
        exit;
    }

    $hashedPassword = password_hash($regPassword, PASSWORD_DEFAULT);

    if (!LoginAndRegister::createSignUpTable($conn)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create table']);
        $conn->close();
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO users (shop_title, email, description, website_url, password) VALUES (?, ?, ?, ?, ?)');

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        $conn->close();
        exit;
    }

    $stmt->bind_param('sssss', $shopTitle, $regEmail, $description, $siteUrl, $hashedPassword);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Shop created successfully']);
    } else {
        if ($stmt->errno === 1062) {
            echo json_encode(['success' => false, 'message' => 'An account with this email already exists']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $stmt->error]);
        }
    }

    $stmt->close();
    $conn->close();
    exit;
}

if ($action === 'login') {
    $email = trim($_POST['loginEmail'] ?? '');
    $pass  = $_POST['loginPassword'] ?? '';

    if ($email === '' || $pass === '') {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        $conn->close();
        exit;
    }

    if (!LoginAndRegister::createSignUpTable($conn)) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        $conn->close();
        exit;
    }

    $stmt = $conn->prepare('SELECT id, password FROM users WHERE email = ? LIMIT 1');
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Login unavailable']);
        $conn->close();
        exit;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($uid, $hash);
    $row = null;
    if ($stmt->fetch()) {
        $row = ['id' => $uid, 'password' => $hash];
    }
    $stmt->close();

    if ($row && password_verify($pass, $row['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $row['id'];

        $base = app_web_root();
        $redirect = ($base !== '' ? $base : '') . '/App/View/Dashboard.php';

        echo json_encode([
            'success'  => true,
            'message'  => 'Signed in successfully',
            'redirect' => $redirect,
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }

    $conn->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
$conn->close();
