<?php
require_once 'config.php';

if (isset($_GET['logout'])) {
    logoutUser();
}

if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'super_admin') {
        header('Location: admin.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (loginUser($username, $password)) {
        $user = getCurrentUser();
        if ($user['role'] === 'super_admin') {
            header('Location: admin.php');
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        $loginError = '账号或密码错误，请重试';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>登录 - 考勤管理系统</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Noto Serif SC', serif;
    background: #0f0f1a;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #e8e4d9;
  }
  .login-box {
    background: #16162a;
    border: 1px solid #2d2d4e;
    border-top: 3px solid #f0c040;
    padding: 48px 40px;
    width: 400px;
  }
  .login-title {
    font-size: 1.3rem;
    letter-spacing: 4px;
    color: #f0c040;
    margin-bottom: 6px;
    text-align: center;
  }
  .login-sub {
    font-size: 0.7rem;
    letter-spacing: 3px;
    color: #7a7a9a;
    text-align: center;
    margin-bottom: 36px;
    font-family: 'JetBrains Mono', monospace;
  }
  .form-group { margin-bottom: 20px; }
  .form-group label {
    display: block;
    font-size: 0.72rem;
    letter-spacing: 2px;
    color: #7a7a9a;
    margin-bottom: 8px;
    font-family: 'JetBrains Mono', monospace;
  }
  .form-group input {
    width: 100%;
    padding: 12px 16px;
    background: #1e1e35;
    border: 1px solid #2d2d4e;
    color: #e8e4d9;
    font-family: 'Noto Serif SC', serif;
    font-size: 1rem;
    outline: none;
    transition: border-color 0.2s;
  }
  .form-group input:focus { border-color: #f0c040; }
  .btn-login {
    width: 100%;
    padding: 14px;
    background: #f0c040;
    color: #0f0f1a;
    border: none;
    font-family: 'Noto Serif SC', serif;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 4px;
    cursor: pointer;
    margin-top: 8px;
    transition: background 0.2s;
  }
  .btn-login:hover { background: #ffd060; }
  .error-msg {
    color: #e05252;
    font-size: 0.85rem;
    text-align: center;
    margin-top: 14px;
    padding: 10px;
    background: rgba(224,82,82,0.1);
    border-left: 3px solid #e05252;
  }
  .role-hint {
    margin-top: 24px;
    padding: 14px;
    background: rgba(240,192,64,0.06);
    border: 1px solid rgba(240,192,64,0.15);
    font-size: 0.78rem;
    color: #7a7a9a;
    line-height: 1.8;
    letter-spacing: 0.5px;
  }
  .role-hint span { color: #f0c040; font-weight: 600; }
</style>
</head>
<body>
<div class="login-box">
  <div class="login-title">📋 考勤管理系统</div>
  <div class="login-sub">ATTENDANCE SYSTEM · LOGIN</div>
  <form method="POST">
    <div class="form-group">
      <label>账号</label>
      <input type="text" name="username" placeholder="请输入登录账号" autofocus required>
    </div>
    <div class="form-group">
      <label>密码</label>
      <input type="password" name="password" placeholder="请输入密码" required>
    </div>
    <button type="submit" name="login_submit" class="btn-login">登 录</button>
  </form>
  <?php if (!empty($loginError)): ?>
    <div class="error-msg"><?= htmlspecialchars($loginError) ?></div>
  <?php endif; ?>
  <div class="role-hint">
    <span>总管理者</span>：可查看所有班级，管理学期配置<br>
    <span>班级管理者</span>：仅管理本班考勤数据
  </div>
</div>
</body>
</html>
