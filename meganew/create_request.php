<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room = trim($_POST['room']);
    $event_date = $_POST['event_date'];
    $payment_method = $_POST['payment_method'];
    $user_id = $_SESSION['user_id'];
    
    // Валидация
    if (empty($room) || empty($event_date) || empty($payment_method)) {
        $error = "Заполните все поля";
    } elseif (strtotime($event_date) < strtotime(date('Y-m-d'))) {
        $error = "Дата не может быть в прошлом.";
    } else {
        $stmt = $conn->prepare("INSERT INTO requests (user_id, room, event_date, payment_method, status) VALUES (?, ?, ?, ?, 'Новая')");
        $stmt->bind_param("isss", $user_id, $room, $event_date, $payment_method);
        
        if ($stmt->execute()) {
            $success = "Заявка успешно создана и отправлена на согласование сисьадмину";
        } else {
            $error = "Ошибка при создании заявки: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Создание заявки</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Оформление заявки на конференцию</h1>
    
    <p><a href="dashboard.php">← Вернуться в личный кабинет</a></p>
    
    <?php if ($success): ?>
        <div style="color: green; border: 1px solid green; padding: 10px; margin: 10px 0;">
            <?= $success ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div style="color: red; border: 1px solid red; padding: 10px; margin: 10px 0;">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <label>Помещение:</label><br>
        <select name="room" required>
            <option value="">Выберите помещение</option>
            <option value="Конференц-зал А">Конференц-зал А (50 мест)</option>
            <option value="Конференц-зал Б">Конференц-зал Б (30 мест)</option>
            <option value="Переговорная В">Переговорная В (10 мест)</option>
            <option value="Актовый зал">Актовый зал (200 мест)</option>
        </select><br><br>
        
        <label>Дата начала конференции:</label><br>
        <input type="date" name="event_date" required min="<?= date('Y-m-d') ?>"><br><br>
        
        <label>Способ оплаты:</label><br>
        <select name="payment_method" required>
            <option value="">Выберите способ</option>
            <option value="Наличный расчет">Наличный расчет</option>
            <option value="Безналичный расчет">Безналичный расчет</option>
            <option value="Банковская карта онлайн">Банковская карта онлайн</option>
        </select><br><br>
        
        <button type="submit">Отправить заявку</button>
    </form>
</body>
</html>