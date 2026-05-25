<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_status'])) {
    $request_id = intval($_POST['request_id']);
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $request_id);
    $stmt->execute();
    $stmt->close();
}

$requests = $conn->query("
    SELECT r.*, u.login, u.full_name, u.email, u.phone 
    FROM requests r 
    JOIN users u ON r.user_id = u.id 
    ORDER BY 
        CASE r.status 
            WHEN 'Новая' THEN 1 
            WHEN 'Мероприятие назначено' THEN 2 
            WHEN 'Мероприятие завершено' THEN 3 
        END, 
        r.created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Панель администратора</title>
    <link rel="stylesheet" href="styles2.css">
</head>
<body class="admin">
    <h1>Панель управления администратора</h1>
    <p>Добро пожаловать, <?= htmlspecialchars($_SESSION['login']) ?>!</p>
    <p><a href="logout.php">Выйти</a></p>
    
    <hr>
    
    <h2>Все заявки на конференции</h2>
    
    <?php if ($requests->num_rows > 0): ?>
        <table border="1" cellpadding="8" cellspacing="0">
            <tr>
                <th>ID</th>
                <th>Пользователь</th>
                <th>Контакты</th>
                <th>Помещение</th>
                <th>Дата</th>
                <th>Оплата</th>
                <th>Текущий статус</th>
                <th>Изменить статус</th>
                <th>Дата создания</th>
            </tr>
            <?php while ($req = $requests->fetch_assoc()): ?>
                <tr>
                    <td><?= $req['id'] ?></td>
                    <td>
                        <?= htmlspecialchars($req['full_name']) ?><br>
                        <small><?= htmlspecialchars($req['login']) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($req['email']) ?><br>
                        <?= htmlspecialchars($req['phone']) ?>
                    </td>
                    <td><?= htmlspecialchars($req['room']) ?></td>
                    <td><?= $req['event_date'] ?></td>
                    <td><?= htmlspecialchars($req['payment_method']) ?></td>
                    <td>
                        <strong style="color: 
                            <?= $req['status'] == 'Новая' ? 'orange' : ($req['status'] == 'Мероприятие назначено' ? 'green' : 'gray') ?>">
                            <?= $req['status'] ?>
                        </strong>
                    </td>
                    <td>
                        <form method="POST" style="margin:0">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            <select name="new_status" required>
                                <option value="Новая" <?= $req['status'] == 'Новая' ? 'selected' : '' ?>>Новая</option>
                                <option value="Мероприятие назначено" <?= $req['status'] == 'Мероприятие назначено' ? 'selected' : '' ?>>Мероприятие назначено</option>
                                <option value="Мероприятие завершено" <?= $req['status'] == 'Мероприятие завершено' ? 'selected' : '' ?>>Мероприятие завершено</option>
                            </select>
                            <button type="submit" name="change_status">Сохранить</button>
                        </form>
                    </td>
                    <td><?= $req['created_at'] ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>Пока нет ни одной заявки</p>
    <?php endif; ?>
    
    <hr>
    
    <h3>Статистика</h3>
    <?php
    $stats = $conn->query("
        SELECT 
            SUM(CASE WHEN status = 'Новая' THEN 1 ELSE 0 END) as new,
            SUM(CASE WHEN status = 'Мероприятие назначено' THEN 1 ELSE 0 END) as assigned,
            SUM(CASE WHEN status = 'Мероприятие завершено' THEN 1 ELSE 0 END) as completed
        FROM requests
    ")->fetch_assoc();
    ?>
    <ul>
        <li>Новых заявок: <?= $stats['new'] ?></li>
        <li>Назначено: <?= $stats['assigned'] ?></li>
        <li>Завершено: <?= $stats['completed'] ?></li>
    </ul>
</body>
</html>