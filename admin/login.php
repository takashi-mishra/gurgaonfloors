<?php
session_start();
require __DIR__ . '/config.php';
if (!empty($_SESSION['admin_logged_in'])) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = (string)($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    if (hash_equals(ADMIN_USERNAME, $username) && password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: index.php'); exit;
    }
    $error = 'Incorrect username or password.';
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Login | Floors Gurgaon</title><link rel="stylesheet" href="admin.css"><style>body{min-height:100vh;display:grid;place-items:center;background:#f3f5f9}.login-card{width:min(420px,92vw);background:#fff;padding:32px;border-radius:18px;box-shadow:0 18px 60px #13203a18}.login-card h1{margin:0 0 8px}.login-card p{color:#667085}.login-card label{display:block;margin:18px 0 7px;font-weight:600}.login-card input{width:100%;padding:12px;border:1px solid #d7dce5;border-radius:9px}.login-card button{width:100%;margin-top:22px;padding:13px;border:0;border-radius:9px;background:#17233c;color:white;font-weight:700;cursor:pointer}.error{padding:10px;background:#fff0f0;color:#b42318;border-radius:8px}.brand{color:#b68b3d;font-weight:800;letter-spacing:1px;margin-bottom:24px}</style></head><body><form class="login-card" method="post"><div class="brand">FLOORS GURGAON · CMS</div><h1>Admin login</h1><p>Sign in to manage your website content.</p><?php if($error): ?><div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?><label for="username">Username</label><input id="username" name="username" autocomplete="username" required><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required><button type="submit">Sign in</button></form></body></html>
