<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

if (!empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}

$user = 'u68609';
$pass = '1793514';

try {
    $db = new PDO('mysql:host=localhost;dbname=u68609', $user, $pass, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    die('Ошибка подключения к базе данных. Пожалуйста, попробуйте позже.');
}

$messages = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim(htmlspecialchars($_POST['login'] ?? ''));
    $password = trim(htmlspecialchars($_POST['pass'] ?? ''));

    try {
        $stmt = $db->prepare("SELECT id, login, password_hash FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['login'] = $user['login'];
            $_SESSION['uid'] = $user['id'];
            header('Location: index.php');
            exit();
        } else {
            $messages[] = 'Неверный логин или пароль';
        }
    } catch (PDOException $e) {
        error_log('Login error: ' . $e->getMessage());
        $messages[] = 'Ошибка при входе в систему';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Вход в систему</title>
</head>
<body>
    <div class="login-form">
        <h1>Вход в систему</h1>
        
        <?php if (!empty($messages)): ?>
            <div class="error-message">
                <?php foreach ($messages as $message): ?>
                    <p><?php echo htmlspecialchars($message); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="login">Логин:</label>
                <input type="text" id="login" name="login" required>
            </div>
            
            <div class="form-group">
                <label for="pass">Пароль:</label>
                <input type="password" id="pass" name="pass" required>
            </div>
            
            <div class="form-actions">
                <input type="submit" value="Войти">
            </div>
        </form>
        
        <p class="register-link">Нет аккаунта? <a href="index.php">Заполните форму</a></p>
    </div>
</body>
</html>