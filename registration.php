<?php
session_start();

if (isset($_SESSION['app_user_id'])) {
    header('Location: index.php');
    exit();
}

$errors = [];
$login_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login'] ?? '');
    $password_input = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password_input)) {
        $errors[] = 'Заполните оба поля.';
    } else {
        function getDB() {
            static $pdo = null;
            if ($pdo === null) {
                $db_host = 'localhost';
                $db_user = 'u82323';
                $db_pass = '4417439';
                $db_name = 'u82323';
                try {
                    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch (PDOException $e) {
                    die("Ошибка БД: " . $e->getMessage());
                }
            }
            return $pdo;
        }

        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, login, password_hash FROM application WHERE login = ?");
        $stmt->execute([$login_input]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password_input, $user['password_hash'])) {
            $_SESSION['app_user_id'] = $user['id'];
            $_SESSION['user_login'] = $user['login'];
            // Удаляем старые куки формы
            $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'languages'];
            foreach ($fields as $f) {
                setcookie($f . '_err', '', 1);
                setcookie($f . '_val', '', 1);
            }
            setcookie('languages_val', '', 1);
            setcookie('contract_accepted_val', '', 1);
            setcookie('save_ok', '', 1);
            header('Location: index.php');
            exit();
        } else {
            $errors[] = 'Неверный логин или пароль.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход – Лабораторная №6</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Вход в систему</h1>
    <p style="text-align:center;">Введите логин и пароль, полученные при отправке анкеты</p>

    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $err): ?>
            <div class="errors"><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>Логин</label>
            <input type="text" name="login" value="<?= htmlspecialchars($login_input) ?>" required>
        </div>
        <div class="form-group">
            <label>Пароль</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Войти</button>
    </form>

    <div class="back-link">
        <a href="index.php">← Вернуться к анкете</a>
        <a href="prosmotr.php" style="margin-left:15px;">📊 Просмотр анкет</a>
    </div>
</div>
</body>
</html>