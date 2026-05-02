<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

$is_logged_in = isset($_SESSION['app_user_id']);
$user_id = $is_logged_in ? $_SESSION['app_user_id'] : null;

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: registration.php');
    exit();
}

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

function generate_unique_login($pdo) {
    do {
        $login = 'user_' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 8);
        $stmt = $pdo->prepare("SELECT id FROM application WHERE login = ?");
        $stmt->execute([$login]);
    } while ($stmt->fetch());
    return $login;
}

function generate_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $length);
}

// Нормализация телефона: приводим к формату +7XXXXXXXXXX (11 цифр)
function normalizePhone($phone) {
    $phone = preg_replace('/[^\d]/', '', $phone);
    if (strlen($phone) === 11 && ($phone[0] === '7' || $phone[0] === '8')) {
        $phone = '+7' . substr($phone, 1);
    } elseif (strlen($phone) === 10) {
        $phone = '+7' . $phone;
    } elseif (strlen($phone) === 12 && substr($phone,0,2) === '79') {
        // уже +7...
    } else {
        return $phone; // не подходит под нормализацию
    }
    return $phone;
}

$allowed_languages = [
    'Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python',
    'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'
];
$allowed_genders = ['male', 'female'];

// ====================== GET ======================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $messages = [];
    $errors = [];
    $values = [];
    $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'languages'];

    if (!$is_logged_in) {
        foreach ($fields as $field) {
            $errors[$field] = !empty($_COOKIE[$field . '_err']);
        }
        if ($errors['full_name']) $messages[] = '<div class="error-message">ФИО обязательно, не более 200 символов.</div>';
        if ($errors['phone']) $messages[] = '<div class="error-message">Телефон должен быть корректным (после нормализации).</div>';
        if ($errors['email']) $messages[] = '<div class="error-message">Введите корректный email.</div>';
        if ($errors['birth_date']) $messages[] = '<div class="error-message">Дата рождения: формат ГГГГ-ММ-ДД, не позже сегодня.</div>';
        if ($errors['gender']) $messages[] = '<div class="error-message">Выберите пол.</div>';
        if ($errors['biography']) $messages[] = '<div class="error-message">Биография обязательна, не более 10000 символов.</div>';
        if ($errors['contract_accepted']) $messages[] = '<div class="error-message">Необходимо подтвердить согласие.</div>';
        if ($errors['languages']) $messages[] = '<div class="error-message">Выберите хотя бы один язык.</div>';

        foreach ($fields as $field) {
            $values[$field] = empty($_COOKIE[$field . '_val']) ? '' : $_COOKIE[$field . '_val'];
        }
        if (!empty($_COOKIE['languages_val'])) {
            $values['languages'] = explode(',', $_COOKIE['languages_val']);
        } else {
            $values['languages'] = [];
        }
        $values['contract_accepted'] = !empty($_COOKIE['contract_accepted_val']);

        if (!empty($_COOKIE['save_ok'])) {
            setcookie('save_ok', '', 1);
            $messages[] = '<div class="success-message">Данные успешно сохранены!</div>';
        }
        if (!empty($_COOKIE['tmp_login'])) {
            $tmp_login = $_COOKIE['tmp_login'];
            $tmp_pass = $_COOKIE['tmp_pass'];
            setcookie('tmp_login', '', 1);
            setcookie('tmp_pass', '', 1);
            $messages[] = '<div class="credentials">
                <strong>Форма отправлена!</strong><br>
                Ваш логин: <strong>' . htmlspecialchars($tmp_login) . '</strong><br>
                Пароль: <strong>' . htmlspecialchars($tmp_pass) . '</strong><br>
                <small>Сохраните их!</small>
            </div>';
        }
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM application WHERE id = ?");
        $stmt->execute([$user_id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($userData) {
            $values['full_name'] = $userData['full_name'];
            $values['phone'] = $userData['phone'];
            $values['email'] = $userData['email'];
            $values['birth_date'] = $userData['birth_date'];
            $values['gender'] = $userData['gender'];
            $values['biography'] = $userData['biography'];
            $values['contract_accepted'] = (bool)$userData['contract_accepted'];

            $langStmt = $pdo->prepare("SELECT l.name FROM application_language al JOIN language l ON al.language_id = l.id WHERE al.application_id = ?");
            $langStmt->execute([$user_id]);
            $values['languages'] = $langStmt->fetchAll(PDO::FETCH_COLUMN);
            $messages[] = '<div class="success-message">Вы вошли как ' . htmlspecialchars($_SESSION['user_login']) . '. Редактируйте данные.</div>';
        } else {
            session_destroy();
            header('Location: registration.php');
            exit();
        }
    }

    $pdo = getDB();
    $languages_from_db = $pdo->query("SELECT name FROM language ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($languages_from_db)) $languages_from_db = $allowed_languages;

    include 'anketka.php';
    exit();
}

// ====================== POST ======================
else {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone_raw = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $biography = trim($_POST['biography'] ?? '');
    $contract_accepted = isset($_POST['contract_accepted']) ? 1 : 0;
    $languages = $_POST['languages'] ?? [];

    $has_error = false;

    // ФИО: только не пустое и длина <=200
    if (empty($full_name)) {
        setcookie('full_name_err', '1', time() + 86400);
        $has_error = true;
    } elseif (strlen($full_name) > 200) {
        setcookie('full_name_err', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('full_name_val', $full_name, time() + 2592000);

    // Телефон: нормализация и проверка
    $phone = normalizePhone($phone_raw);
    if (empty($phone_raw)) {
        setcookie('phone_err', '1', time() + 86400);
        $has_error = true;
    } elseif (!preg_match('/^\+7\d{10}$/', $phone)) {
        setcookie('phone_err', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('phone_val', $phone_raw, time() + 2592000); // сохраняем сырой ввод для отображения

    // Email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setcookie('email_err', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('email_val', $email, time() + 2592000);

    // Дата рождения
    if (empty($birth_date)) {
        setcookie('birth_date_err', '1', time() + 86400);
        $has_error = true;
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $birth_date);
        if (!$date || $date->format('Y-m-d') !== $birth_date || $date > new DateTime('today')) {
            setcookie('birth_date_err', '1', time() + 86400);
            $has_error = true;
        }
    }
    setcookie('birth_date_val', $birth_date, time() + 2592000);

    // Пол
    if (empty($gender) || !in_array($gender, $allowed_genders)) {
        setcookie('gender_err', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('gender_val', $gender, time() + 2592000);

    // Биография – обязательное поле
    if (empty($biography)) {
        setcookie('biography_err', '1', time() + 86400);
        $has_error = true;
    } elseif (strlen($biography) > 10000) {
        setcookie('biography_err', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('biography_val', $biography, time() + 2592000);

    // Чекбокс
    if (!$contract_accepted) {
        setcookie('contract_accepted_err', '1', time() + 86400);
        $has_error = true;
    }
    setcookie('contract_accepted_val', $contract_accepted ? '1' : '0', time() + 2592000);

    // Языки
    if (empty($languages)) {
        setcookie('languages_err', '1', time() + 86400);
        $has_error = true;
    } else {
        foreach ($languages as $lang) {
            if (!in_array($lang, $allowed_languages)) {
                setcookie('languages_err', '1', time() + 86400);
                $has_error = true;
                break;
            }
        }
    }
    setcookie('languages_val', implode(',', $languages), time() + 2592000);

    if ($has_error) {
        header('Location: index.php');
        exit();
    }

    // Сохранение
    try {
        $pdo = getDB();
        $pdo->beginTransaction();

        if ($is_logged_in) {
            $stmt = $pdo->prepare("
                UPDATE application 
                SET full_name = :fn, phone = :ph, email = :em, birth_date = :bd,
                    gender = :gd, biography = :bio, contract_accepted = :ca
                WHERE id = :id
            ");
            $stmt->execute([
                ':fn' => $full_name, ':ph' => $phone, ':em' => $email, ':bd' => $birth_date,
                ':gd' => $gender, ':bio' => $biography, ':ca' => $contract_accepted, ':id' => $user_id
            ]);
            $app_id = $user_id;
            $pdo->prepare("DELETE FROM application_language WHERE application_id = ?")->execute([$app_id]);
            setcookie('updated_ok', '1', time() + 86400);
        } else {
            $login = generate_unique_login($pdo);
            $plain_pass = generate_password();
            $pass_hash = password_hash($plain_pass, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO application 
                (full_name, phone, email, birth_date, gender, biography, contract_accepted, login, password_hash)
                VALUES (:fn, :ph, :em, :bd, :gd, :bio, :ca, :lg, :phash)
            ");
            $stmt->execute([
                ':fn' => $full_name, ':ph' => $phone, ':em' => $email, ':bd' => $birth_date,
                ':gd' => $gender, ':bio' => $biography, ':ca' => $contract_accepted,
                ':lg' => $login, ':phash' => $pass_hash
            ]);
            $app_id = $pdo->lastInsertId();

            setcookie('tmp_login', $login, time() + 3600);
            setcookie('tmp_pass', $plain_pass, time() + 3600);
            setcookie('save_ok', '1', time() + 86400);
        }

        // Языки
        $lang_map = [];
        $stmt = $pdo->query("SELECT id, name FROM language");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lang_map[$row['name']] = $row['id'];
        }
        $ins = $pdo->prepare("INSERT INTO application_language (application_id, language_id) VALUES (?, ?)");
        foreach ($languages as $lang_name) {
            if (isset($lang_map[$lang_name])) {
                $ins->execute([$app_id, $lang_map[$lang_name]]);
            }
        }

        $pdo->commit();

        $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'languages'];
        foreach ($fields as $f) {
            setcookie($f . '_err', '', 1);
        }

        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        setcookie('db_error', '1', time() + 86400);
        header('Location: index.php');
        exit();
    }
}