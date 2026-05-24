<?php
   session_start();
    require_once 'db_connect.php';
    
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        $login = $_POST['login'];
        $password = $_POST['password'];
        $stm = $conn->prepare("SELECT id, login, password_hash FROM users WHERE login = ?");
        $stm->bind_param("s",$login);
        $stm->execute();
        $result = $stm->get_result();
        $user = $result->fetch_assoc();
        if ($login === 'Admin' && $password === 'KorokNET') {
            $_SESSION['role'] = 'admin';
            $_SESSION['login'] = 'Admin';
            header("Location: admin.php");
            exit;

        } elseif ($user && password_verify($password, $user['password_hash'])) {

            $_SESSION['id'] = $user['id'];
            $_SESSION['login'] = $user['login'];
            $_SESSION['role'] = 'user';
            header("Location: index.php");
            exit;

        } else {
            $error = "Неверный логин или пароль";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Document</title>
</head>
<body>
    <h1>Авторизация</h1>
    <form method="POST">
        <input type="text" name="login" placeholder="login" required><br>
        <input type="password" name="password" placeholder="password" required><br>
        <button type="submit">Log in</button>
    </form>
</body>
</html>