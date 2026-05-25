<?php
session_start();
require_once 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    
    $valid = true;

    if (!preg_match('/^[a-zA-Z0-9]{6,}$/', $login)) {
        $error .= "Логин должен содержать только латинские буквы и цифры, минимум 6 символов.<br>";
        $valid = false;
    }

    if (strlen($password) < 8) {
        $error .= "Пароль должен быть не менее 8 символов.<br>";
        $valid = false;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error .= "Некорректный email.<br>";
        $valid = false;
    }

    if (empty($phone)) {
        $error .= "Телефон обязателен.<br>";
        $valid = false;
    }

    if (empty($full_name)) {
        $error .= "ФИО обязательно.<br>";
        $valid = false;
    }
    
    if ($valid) {
        $check = $conn->prepare("SELECT id FROM users WHERE login = ?");
        $check->bind_param("s", $login);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error = "Пользователь с таким логином уже существует.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (login, password, full_name, phone, email, role) VALUES (?, ?, ?, ?, ?, 'user')");
            $stmt->bind_param("sssss", $login, $hashed, $full_name, $phone, $email);
            
            if ($stmt->execute()) {
                $success = "Регистрация успешна <a href='login.php'>Войти</a>";
            } else {
                $error = "Ошибка при регистрации: " . $conn->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Регистрация</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Регистрация нового пользователя</h1>
    
    <?php if ($error): ?>
        <div style="color: red; border: 1px solid red; padding: 10px; margin: 10px 0;">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div style="color: green; border: 1px solid green; padding: 10px; margin: 10px 0;">
            <?= $success ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <label>Логин:</label><br>
        <input type="text" name="login" required placeholder="Длина минимум 6 символов"><br><br>
        
        <label>Пароль:</label><br>
        <input type="password" name="password" required placeholder="Длина минимум 8 символов"><br><br>
        
        <label>ФИО:</label><br>
        <input type="text" name="full_name" required placeholder="Иван Иванович Иванов"><br><br>
        
        <label>Контактный телефон:</label><br>
        <input type="tel" name="phone" required placeholder="89009559999"><br><br>
        
        <label>E-mail:</label><br>
        <input type="email" name="email" required placeholder="a@..."><br><br>
        
        <button type="submit">Зарегистрироваться</button>
    </form>
    
    <p><a href="login.php">Уже есть аккаунт? Войти</a></p>
</body>
</html>