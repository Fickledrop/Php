<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$requests = $conn->query("SELECT * FROM requests WHERE user_id = $user_id ORDER BY created_at DESC");

$reviews = $conn->query("
    SELECT r.*, req.room, req.event_date 
    FROM reviews r 
    JOIN requests req ON r.request_id = req.id 
    WHERE r.user_id = $user_id 
    ORDER BY r.created_at DESC
");

$review_msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_review'])) {
    $request_id = intval($_POST['request_id']);
    $review_text = trim($_POST['review_text']);
    
    if (!empty($review_text)) {
        $stmt = $conn->prepare("INSERT INTO reviews (user_id, request_id, review_text) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $request_id, $review_text);
        if ($stmt->execute()) {
            $review_msg = "Отзыв добавлен!";
            header("Refresh:0");
        } else {
            $review_msg = "Ошибка при добавлении отзыва.";
        }
        $stmt->close();
    } else {
        $review_msg = "Текст отзыва не может быть пустым.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Личный кабинет</title>
    <link rel="stylesheet" href="styles2.css">
</head>
<body class="user">
    <h1>Личный кабинет</h1>
    <p>Добро пожаловать, <?= htmlspecialchars($_SESSION['login']) ?>!</p>
    
    <p>
        <a href="create_request.php">Создать новую заявку</a> |
        <a href="logout.php">Выйти</a>
    </p>
    
    <hr>
    
    <h2>Мои заявки</h2>
    <?php if ($requests->num_rows > 0): ?>
        <table border="1" cellpadding="8">
            <tr>
                <th>ID</th>
                <th>Помещение</th>
                <th>Дата конференции</th>
                <th>Способ оплаты</th>
                <th>Статус</th>
                <th>Дата создания</th>
                <th>Действие</th>
            </tr>
            <?php while ($req = $requests->fetch_assoc()): ?>
                <tr>
                    <td><?= $req['id'] ?></td>
                    <td><?= htmlspecialchars($req['room']) ?></td>
                    <td><?= $req['event_date'] ?></td>
                    <td><?= htmlspecialchars($req['payment_method']) ?></td>
                    <td>
                        <strong style="color: 
                            <?= $req['status'] == 'Новая' ? 'orange' : ($req['status'] == 'Мероприятие назначено' ? 'green' : 'gray') ?>">
                            <?= $req['status'] ?>
                        </strong>
                    </td>
                    <td><?= $req['created_at'] ?></td>
                    <td>
                        <?php if ($req['status'] != 'Новая'): ?>
                            <form method="POST" style="margin:0">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <button type="submit" name="show_review_form" value="yes">Оставить отзыв</button>
                            </form>
                        <?php else: ?>
                            <span style="color:gray">(отзыв после назначения)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>Ты пока беззаявочный <a href="create_request.php">Шуруй создавать заявочку</a></p>
    <?php endif; ?>
    
    <hr>
    
    <h2>Мои отзывы</h2>
    <?php if ($reviews->num_rows > 0): ?>
        <?php while ($rev = $reviews->fetch_assoc()): ?>
            <div style="border:1px solid #ccc; margin:10px 0; padding:10px;">
                <strong>Заявка №<?= $rev['request_id'] ?>:</strong> <?= htmlspecialchars($rev['room']) ?> (<?= $rev['event_date'] ?>)<br>
                <strong>Отзыв:</strong> <?= htmlspecialchars($rev['review_text']) ?><br>
                <small>Добавлен: <?= $rev['created_at'] ?></small>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Ты пока безотзывный</p>
    <?php endif; ?>
    
    <!-- Форма добавления отзыва -->
    <?php if (isset($_POST['show_review_form'])): 
        $selected_request_id = intval($_POST['request_id']);
    ?>
        <hr>
        <h3>Добавить отзыв к заявке №<?= $selected_request_id ?></h3>
        <?php if ($review_msg): ?>
            <div style="color:green"><?= $review_msg ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="request_id" value="<?= $selected_request_id ?>">
            <textarea name="review_text" rows="4" cols="50" required placeholder="Твой отзыв"></textarea><br>
            <button type="submit" name="add_review">Отправить отзыв</button>
        </form>
    <?php endif; ?>
</body>
</html>