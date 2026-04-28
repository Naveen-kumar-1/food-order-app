<?php

session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

$root = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')));
$root = $root === '/' || $root === '\\' ? '' : rtrim($root, '/');
$login = ($root !== '' ? $root : '') . '/index.php';

header('Location: ' . $login);
exit;
