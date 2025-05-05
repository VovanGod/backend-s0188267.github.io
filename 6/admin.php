<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// Подключение к базе данных
$user = 'u68609';
$pass = '1793514';

try {
    $db = new PDO('mysql:host=localhost;dbname=u68609', $user, $pass, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die('Ошибка подключения: ' . $e->getMessage());
}

// Проверка авторизации
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<h1>Требуется авторизация</h1>';
    echo '<p>Используйте логин: <strong>admin</strong>, пароль: <strong>12345</strong></p>';
    exit();
}

$login = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];

// Упрощенная проверка (можно заменить на проверку из БД)
if ($login !== 'admin' || $password !== '12345') {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<h1>Ошибка авторизации</h1>';
    echo '<p>Неверный логин или пароль. Попробуйте снова.</p>';
    echo '<p>Логин: <strong>admin</strong>, пароль: <strong>12345</strong></p>';
    exit();
}

$_SESSION['admin_auth'] = true;

// Выход из админки
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Функции для работы с данными
function getAllApplications($db) {
    $stmt = $db->query("
        SELECT a.id, a.full_name, a.phone, a.email, a.birth_date, 
               a.gender, a.biography, a.agreement, u.login
        FROM applications a
        JOIN user_applications ua ON a.id = ua.application_id
        JOIN users u ON ua.user_id = u.id
        ORDER BY a.id
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getApplicationLanguages($db, $application_id) {
    $stmt = $db->prepare("
        SELECT pl.id, pl.name 
        FROM application_languages al
        JOIN programming_languages pl ON al.language_id = pl.id
        WHERE al.application_id = ?
    ");
    $stmt->execute([$application_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLanguagesStatistics($db) {
    $stmt = $db->query("
        SELECT pl.id, pl.name, COUNT(al.application_id) as user_count
        FROM programming_languages pl
        LEFT JOIN application_languages al ON pl.id = al.language_id
        GROUP BY pl.id, pl.name
        ORDER BY user_count DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllLanguages($db) {
    $stmt = $db->query("SELECT id, name FROM programming_languages ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Обработка действий
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        $db->beginTransaction();
        $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $db->prepare("DELETE FROM user_applications WHERE application_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$id]);
        $db->commit();
        
        header('Location: admin.php');
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        die('Ошибка при удалении: ' . $e->getMessage());
    }
}

// Редактирование заявки
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_application'])) {
    $id = $_POST['id'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $birth_date = $_POST['birth_date'];
    $gender = $_POST['gender'];
    $biography = trim($_POST['biography']);
    $agreement = isset($_POST['agreement']) ? 1 : 0;
    $languages = isset($_POST['languages']) ? $_POST['languages'] : [];
    
    try {
        $db->beginTransaction();
        $stmt = $db->prepare("
            UPDATE applications 
            SET full_name = ?, phone = ?, email = ?, birth_date = ?, 
                gender = ?, biography = ?, agreement = ?
            WHERE id = ?
        ");
        $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $biography, $agreement, $id]);
        
        $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($languages as $language_id) {
            $stmt->execute([$id, $language_id]);
        }
        
        $db->commit();
        header("Location: admin.php");
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        die('Ошибка при обновлении: ' . $e->getMessage());
    }
}

// Получение данных для редактирования
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $db->prepare("
        SELECT a.id, a.full_name, a.phone, a.email, a.birth_date, 
               a.gender, a.biography, a.agreement
        FROM applications a
        WHERE a.id = ?
    ");
    $stmt->execute([$id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($edit_data) {
        $edit_data['languages'] = array_column(getApplicationLanguages($db, $id), 'id');
    }
}

$applications = getAllApplications($db);
$statistics = getLanguagesStatistics($db);
$all_languages = getAllLanguages($db);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Административная панель</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="container">
        <div class="admin-header">
            <h1>Административная панель</h1>
            <a href="admin.php?logout=1" class="btn btn-logout">Выйти</a>
        </div>
        
        <?php if ($edit_data): ?>
            <div class="edit-form">
                <h2>Редактирование заявки #<?= htmlspecialchars($edit_data['id']) ?></h2>
                <form method="post">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id']) ?>">
                    <input type="hidden" name="edit_application" value="1">
                    
                    <div class="form-group">
                        <label>ФИО:</label>
                        <input type="text" name="full_name" required value="<?= htmlspecialchars($edit_data['full_name']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Телефон:</label>
                        <input type="tel" name="phone" required value="<?= htmlspecialchars($edit_data['phone']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($edit_data['email']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Дата рождения:</label>
                        <input type="date" name="birth_date" required value="<?= htmlspecialchars($edit_data['birth_date']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Пол:</label>
                        <label><input type="radio" name="gender" value="male" <?= $edit_data['gender'] == 'male' ? 'checked' : '' ?>> Мужской</label>
                        <label><input type="radio" name="gender" value="female" <?= $edit_data['gender'] == 'female' ? 'checked' : '' ?>> Женский</label>
                    </div>
                    
                    <div class="form-group">
                        <label>Биография:</label>
                        <textarea name="biography"><?= htmlspecialchars($edit_data['biography']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Любимые языки программирования:</label>
                        <div class="language-options">
                            <?php foreach ($all_languages as $lang): ?>
                                <div class="language-option">
                                    <input type="checkbox" name="languages[]" value="<?= $lang['id'] ?>"
                                        <?= in_array($lang['id'], $edit_data['languages']) ? 'checked' : '' ?>>
                                    <label><?= htmlspecialchars($lang['name']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><input type="checkbox" name="agreement" <?= $edit_data['agreement'] ? 'checked' : '' ?>> Согласие</label>
                    </div>
                    
                    <button type="submit">Сохранить</button>
                    <a href="admin.php" class="btn-cancel">Отмена</a>
                </form>
            </div>
        <?php endif; ?>
        
        <h2>Все заявки</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Логин</th>
                    <th>ФИО</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Дата рождения</th>
                    <th>Пол</th>
                    <th>Языки</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                    <tr>
                        <td><?= htmlspecialchars($app['id']) ?></td>
                        <td><?= htmlspecialchars($app['login']) ?></td>
                        <td><?= htmlspecialchars($app['full_name']) ?></td>
                        <td><?= htmlspecialchars($app['phone']) ?></td>
                        <td><?= htmlspecialchars($app['email']) ?></td>
                        <td><?= htmlspecialchars($app['birth_date']) ?></td>
                        <td><?= $app['gender'] == 'male' ? 'Мужской' : 'Женский' ?></td>
                        <td>
                            <?= htmlspecialchars(implode(', ', array_column(getApplicationLanguages($db, $app['id']), 'name'))) ?>
                        </td>
                        <td class="actions">
                            <a href="admin.php?edit=<?= $app['id'] ?>">Редактировать</a>
                            <a href="admin.php?delete=<?= $app['id'] ?>" onclick="return confirm('Удалить заявку?')">Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>Статистика по языкам программирования</h2>
        <table>
            <thead>
                <tr>
                    <th>Язык</th>
                    <th>Количество пользователей</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statistics as $stat): ?>
                    <tr>
                        <td><?= htmlspecialchars($stat['name']) ?></td>
                        <td><?= htmlspecialchars($stat['user_count']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>