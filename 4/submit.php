<?php
// Подключение к базе данных
$user = 'u68609';
$pass = '1793514';

try {
    $db = new PDO('mysql:host=localhost;dbname=u68609', $user, $pass, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Валидация данных
$errors = [];

if (empty($_POST['fullname'])) {
    $errors[] = "Поле ФИО обязательно для заполнения.";
} elseif (!preg_match("/^[a-zA-Zа-яА-Я\s]{1,150}$/u", $_POST['fullname'])) {
    $errors[] = "Поле ФИО должно содержать только буквы и пробелы и быть не длиннее 150 символов.";
}

if (empty($_POST['phone'])) {
    $errors[] = "Поле Телефон обязательно для заполнения.";
} elseif (!preg_match("/^\+7\d{10}$/", $_POST['phone'])) {
    $errors[] = "Поле Телефон должно быть в формате +7XXXXXXXXXX.";
}

if (empty($_POST['email'])) {
    $errors[] = "Поле E-mail обязательно для заполнения.";
} elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Поле E-mail должно быть корректным email адресом.";
}

if (empty($_POST['dob'])) {
    $errors[] = "Поле Дата рождения обязательно для заполнения.";
} elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $_POST['dob'])) {
    $errors[] = "Поле Дата рождения должно быть в формате YYYY-MM-DD.";
}

if (empty($_POST['gender'])) {
    $errors[] = "Поле Пол обязательно для заполнения.";
}

if (empty($_POST['languages'])) {
    $errors[] = "Поле Любимый язык программирования обязательно для заполнения.";
}

if (empty($_POST['bio'])) {
    $errors[] = "Поле Биография обязательно для заполнения.";
}

if (!isset($_POST['contract'])) {
    $errors[] = "Необходимо ознакомиться с контрактом.";
}

// Если есть ошибки, сохраняем их в Cookies и перенаправляем на форму
if (count($errors) > 0) {
    setcookie('errors', serialize($errors), time() + 3600, '/');
    setcookie('fullname', $_POST['fullname'], time() + 3600, '/');
    setcookie('phone', $_POST['phone'], time() + 3600, '/');
    setcookie('email', $_POST['email'], time() + 3600, '/');
    setcookie('dob', $_POST['dob'], time() + 3600, '/');
    setcookie('gender', $_POST['gender'], time() + 3600, '/');
    setcookie('languages', serialize($_POST['languages']), time() + 3600, '/');
    setcookie('bio', $_POST['bio'], time() + 3600, '/');
    setcookie('contract', isset($_POST['contract']) ? 'on' : '', time() + 3600, '/');
    header('Location: index.html');
    exit;
}

// Если ошибок нет, сохраняем данные в базу и в Cookies на год
try {
    $stmt = $db->prepare("INSERT INTO application (first_name, last_name, patronymic, phone, email, dob, gender, bio) 
                          VALUES (:first_name, :last_name, :patronymic, :phone, :email, :dob, :gender, :bio)");
    $stmt->execute([
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':patronymic' => $patronymic,
        ':phone' => $_POST['phone'],
        ':email' => $_POST['email'],
        ':dob' => $_POST['dob'],
        ':gender' => $_POST['gender'],
        ':bio' => $_POST['bio']
    ]);

    $applicationId = $db->lastInsertId();

    foreach ($_POST['languages'] as $language) {
        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) 
                              VALUES (:application_id, (SELECT id FROM languages WHERE name = :language))");
        $stmt->execute([
            ':application_id' => $applicationId,
            ':language' => $language
        ]);
    }

    // Сохраняем данные в Cookies на год
    setcookie('fullname', $_POST['fullname'], time() + 31536000, '/');
    setcookie('phone', $_POST['phone'], time() + 31536000, '/');
    setcookie('email', $_POST['email'], time() + 31536000, '/');
    setcookie('dob', $_POST['dob'], time() + 31536000, '/');
    setcookie('gender', $_POST['gender'], time() + 31536000, '/');
    setcookie('languages', serialize($_POST['languages']), time() + 31536000, '/');
    setcookie('bio', $_POST['bio'], time() + 31536000, '/');
    setcookie('contract', 'on', time() + 31536000, '/');

    echo "<p style='color: green;'>Данные успешно сохранены!</p>";
} catch (PDOException $e) {
    die("Ошибка при сохранении данных: " . $e->getMessage());
}

// Вывод списка заявок
try {
    $stmt = $db->query("SELECT a.id, a.first_name, a.last_name, a.patronymic, a.email, GROUP_CONCAT(l.name SEPARATOR ', ') AS languages 
                        FROM application a 
                        LEFT JOIN application_languages al ON a.id = al.application_id 
                        LEFT JOIN languages l ON al.language_id = l.id 
                        GROUP BY a.id");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($applications) > 0) {
        echo "<h2>Список заявок</h2>";
        echo "<table border='1' cellpadding='10' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Фамилия</th><th>Имя</th><th>Отчество</th><th>Email</th><th>Языки</th></tr>";
        foreach ($applications as $app) {
            echo "<tr>";
            echo "<td>{$app['id']}</td>";
            echo "<td>{$app['last_name']}</td>";
            echo "<td>{$app['first_name']}</td>";
            echo "<td>{$app['patronymic']}</td>";
            echo "<td>{$app['email']}</td>";
            echo "<td>{$app['languages']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Заявок пока нет.</p>";
    }
} catch (PDOException $e) {
    die("Ошибка при получении данных: " . $e->getMessage());
}
?>

// Очистка Cookies после использования
if (isset($_COOKIE['errors'])) {
    setcookie('errors', '', time() - 3600, '/');
}
if (isset($_COOKIE['fullname'])) {
    setcookie('fullname', '', time() - 3600, '/');
}
if (isset($_COOKIE['phone'])) {
    setcookie('phone', '', time() - 3600, '/');
}
if (isset($_COOKIE['email'])) {
    setcookie('email', '', time() - 3600, '/');
}
if (isset($_COOKIE['dob'])) {
    setcookie('dob', '', time() - 3600, '/');
}
if (isset($_COOKIE['gender'])) {
    setcookie('gender', '', time() - 3600, '/');
}
if (isset($_COOKIE['languages'])) {
    setcookie('languages', '', time() - 3600, '/');
}
if (isset($_COOKIE['bio'])) {
    setcookie('bio', '', time() - 3600, '/');
}
if (isset($_COOKIE['contract'])) {
    setcookie('contract', '', time() - 3600, '/');
}