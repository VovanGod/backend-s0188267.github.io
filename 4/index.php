<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма регистрации</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="form-container">
        <h2>Форма регистрации</h2>
        <?php
        if (isset($_COOKIE['errors'])) {
            $errors = unserialize($_COOKIE['errors']);
            echo "<div class='errors'>";
            foreach ($errors as $error) {
                echo "<p style='color: red;'>$error</p>";
            }
            echo "</div>";
        }
        ?>
        <form action="submit.php" method="post">
            <div class="form-group">
                <label for="fullname">ФИО:</label>
                <input type="text" id="fullname" name="fullname" required maxlength="150" value="<?php echo isset($_COOKIE['fullname']) ? htmlspecialchars($_COOKIE['fullname']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="phone">Телефон:</label>
                <input type="tel" id="phone" name="phone" required value="<?php echo isset($_COOKIE['phone']) ? htmlspecialchars($_COOKIE['phone']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required value="<?php echo isset($_COOKIE['email']) ? htmlspecialchars($_COOKIE['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="dob">Дата рождения:</label>
                <input type="date" id="dob" name="dob" required value="<?php echo isset($_COOKIE['dob']) ? htmlspecialchars($_COOKIE['dob']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Пол:</label>
                <div>
                    <input type="radio" id="male" name="gender" value="male" required <?php echo (isset($_COOKIE['gender']) && $_COOKIE['gender'] == 'male') ? 'checked' : ''; ?>>
                    <label for="male">Мужской</label>
                    <input type="radio" id="female" name="gender" value="female" required <?php echo (isset($_COOKIE['gender']) && $_COOKIE['gender'] == 'female') ? 'checked' : ''; ?>>
                    <label for="female">Женский</label>
                </div>
            </div>
            <div class="form-group">
                <label for="languages">Любимый язык программирования:</label>
                <select id="languages" name="languages[]" multiple="multiple" required>
                    <?php
                    $languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
                    foreach ($languages as $language) {
                        $selected = (isset($_COOKIE['languages']) && in_array($language, unserialize($_COOKIE['languages']))) ? 'selected' : '';
                        echo "<option value='$language' $selected>$language</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="bio">Биография:</label>
                <textarea id="bio" name="bio" rows="5" required><?php echo isset($_COOKIE['bio']) ? htmlspecialchars($_COOKIE['bio']) : ''; ?></textarea>
            </div>
            <div class="form-group">
                <input type="checkbox" id="contract" name="contract" required <?php echo isset($_COOKIE['contract']) ? 'checked' : ''; ?>>
                <label for="contract">С контрактом ознакомлен(а)</label>
            </div>
            <div class="form-group">
                <button type="submit">Сохранить</button>
            </div>
        </form>
    </div>
</body>
</html>