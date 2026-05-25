<?php
session_start();
require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, login, password, role FROM users WHERE login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login'] = $user['login'];
        $_SESSION['role'] = $user['role'];
        
        if ($user['role'] == 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    } else {
        $error = "Неверный логин или пароль.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Вход</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Вход в систему</h1>
    
    <?php if ($error): ?>
        <div style="color: red; border: 1px solid red; padding: 10px; margin: 10px 0;">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <label>Логин:</label><br>
        <input type="text" name="login" required><br><br>
        
        <label>Пароль:</label><br>
        <input type="password" name="password" required><br><br>
        
        <button type="submit">Войти</button>
    </form>
    
    <p><a href="register.php">Еще не зарегистрированы? Регистрация</a></p>
</body>
</html>