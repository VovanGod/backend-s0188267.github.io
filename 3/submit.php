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
}

if (empty($_POST['email'])) {
    $errors[] = "Поле E-mail обязательно для заполнения.";
} elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Некорректный формат E-mail.";
}

if (empty($_POST['dob'])) {
    $errors[] = "Поле Дата рождения обязательно для заполнения.";
}

if (empty($_POST['gender'])) {
    $errors[] = "Поле Пол обязательно для заполнения.";
}

if (empty($_POST['languages'])) {
    $errors[] = "Выберите хотя бы один язык программирования.";
}

if (empty($_POST['bio'])) {
    $errors[] = "Поле Биография обязательно для заполнения.";
}

if (!isset($_POST['contract'])) {
    $errors[] = "Необходимо подтвердить ознакомление с контрактом.";
}

// Если есть ошибки, выводим их
if (count($errors) > 0) {
    foreach ($errors as $error) {
        echo "<p style='color: red;'>$error</p>";
    }
    exit;
}

// Вставка данных в таблицу application
try {
    $stmt = $db->prepare("INSERT INTO application (fullname, phone, email, dob, gender, bio) VALUES (:fullname, :phone, :email, :dob, :gender, :bio)");
    $stmt->execute([
        ':fullname' => $_POST['fullname'],
        ':phone' => $_POST['phone'],
        ':email' => $_POST['email'],
        ':dob' => $_POST['dob'],
        ':gender' => $_POST['gender'],
        ':bio' => $_POST['bio']
    ]);

    // Получаем ID последней вставленной записи
    $applicationId = $db->lastInsertId();

    // Вставка данных в таблицу application_languages
    foreach ($_POST['languages'] as $language) {
        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (:application_id, (SELECT id FROM languages WHERE name = :language))");
        $stmt->execute([
            ':application_id' => $applicationId,
            ':language' => $language
        ]);
    }

    echo "<p style='color: green;'>Данные успешно сохранены!</p>";
} catch (PDOException $e) {
    die("Ошибка при сохранении данных: " . $e->getMessage());
}

// Вывод списка заявок
try {
    $stmt = $db->query("SELECT a.id, a.fullname, a.email, GROUP_CONCAT(l.name SEPARATOR ', ') AS languages 
                        FROM application a 
                        LEFT JOIN application_languages al ON a.id = al.application_id 
                        LEFT JOIN languages l ON al.language_id = l.id 
                        GROUP BY a.id");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($applications) > 0) {
        echo "<h2>Список заявок</h2>";
        echo "<table border='1' cellpadding='10' cellspacing='0'>";
        echo "<tr><th>ID</th><th>ФИО</th><th>Email</th><th>Языки</th></tr>";
        foreach ($applications as $app) {
            echo "<tr>";
            echo "<td>{$app['id']}</td>";
            echo "<td>{$app['fullname']}</td>";
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