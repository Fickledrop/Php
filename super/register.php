<?php
    session_start();
    require_once 'db_connect.php';

    if($_SERVER['REQUEST_METHOD'] == 'POST'){
       $login = trim($_POST['login']);
        $password = trim($_POST['password']);
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
 
        $stmt = $conn->prepare("SELECT id FROM users WHERE login = ? OR email =?");
        $stmt->bind_param("ss",$login, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows>0) {
            $error ="Fail";
        }else{
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (login, password_hash, full_name,phone, email)
            VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssss",$login,$hash,$full_name,$phone,$email);

            if($stmt->execute()){
                $success = "Log in good <a href='login.php'>Log in</a>";
            }else{
                $error = "fail";
            }
        }
        $stmt->close();
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
    <h1>Register</h1>
    <form method="POST">
        <input type="text" name="login" minlength="6" placeholder="login"><br>
        <input type="password" name="password" minlength="8" placeholder="password"><br>
        <input type="text" name="full_name" placeholder="FIO"><br>
        <input type="text" name="phone" placeholder="phone"><br>
        <input type="email" name="email" placeholder="email"><br>

        <button type="submit">Sing in</button>
    </form>
    <p><a href="login.php">Уже есть аккаунт?</a></p>
</body>
</html>