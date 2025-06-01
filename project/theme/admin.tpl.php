<!DOCTYPE html>
<html>
<head>
    <title>Админ-панель</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-container">
        <h1>Админ-панель</h1>
        <a href="logout.php" class="button admin-logout">Выйти</a>
        <div class="stats">
            <h2>Статистика по языкам программирования</h2>
            <table>
                <tr><th>Язык</th><th>Количество выборов</th></tr>
                <?php foreach ($stats as $stat): ?>
                <tr>
                    <td><?= htmlspecialchars($stat['name']) ?></td>
                    <td><?= $stat['count'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <h2>Все заявки пользователей (всего: <?= count($processedApplications) ?>)</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Пользователь</th>
                <th>ФИО</th>
                <th>Email</th>
                <th>Телефон</th>
                <th>Дата рождения</th>
                <th>Пол</th>
                <th>Языки</th>
                <th>Биография</th>
                <th>Согласие</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($processedApplications as $app): ?>
            <tr>
                <td><?= $app['id'] ?></td>
                <td><?= htmlspecialchars($app['user_login'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($app['full_name']) ?></td>
                <td><?= htmlspecialchars($app['email']) ?></td>
                <td><?= htmlspecialchars($app['phone']) ?></td>
                <td><?= htmlspecialchars($app['birth_date']) ?></td>
                <td><?= $app['gender_short'] ?></td>
                <td><?= htmlspecialchars($app['languages']) ?></td>
                <td><?= htmlspecialchars(substr($app['biography'], 0, 50)) . (strlen($app['biography']) > 50 ? '...' : '') ?></td>
                <td><?= $app['agreement'] ? 'Да' : 'Нет' ?></td>
                <td>
                    <a href="edit.php?id=<?= $app['id'] ?>" class="button">Редактировать</a>
                    <a href="delete.php?id=<?= $app['id'] ?>" class="button" onclick="return confirm('Удалить эту заявку?')">Удалить</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>