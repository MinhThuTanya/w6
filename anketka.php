<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анкета – Лабораторная №6</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .task {
    background: #1e1e2a;
    border: 1px solid #f5b042;
    border-radius: 20px;
    padding: 20px;
    margin-top: 20px;
}
.screenshot {
    text-align: center;
}
.screenshot img {
    max-width: 100%;
    border-radius: 16px;
    border: 1px solid #f5b042;
}
.caption {
    font-style: italic;
    color: #ccc;
    margin-top: 8px;
}
    </style>
    
</head>
<body>
<div class="container">
    <h1>📋 Анкета</h1>

    <?php if ($is_logged_in): ?>
        <div class="logged-in">
            ✅ Вы авторизованы (ID: <?= htmlspecialchars($user_id) ?>)
            <a href="index.php?logout=1" style="color:#f5b042; margin-left:15px;">Выйти</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $msg): ?>
            <?= $msg ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="form-wrapper">
        <div class="form-column">
            <form method="post" action="index.php">
                <div class="form-group">
                    <label for="full_name">ФИО *</label>
                    <input type="text" id="full_name" name="full_name"
                           value="<?= htmlspecialchars($values['full_name'] ?? '') ?>"
                           <?= !empty($errors['full_name']) ? 'class="error"' : '' ?>>
                    <?php if (!empty($errors['full_name'])): ?>
                        <span class="field-error">ФИО обязательно (до 200 символов).</span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="phone">Телефон *</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?= htmlspecialchars($values['phone'] ?? '') ?>"
                           <?= !empty($errors['phone']) ? 'class="error"' : '' ?>>
                    <?php if (!empty($errors['phone'])): ?>
                        <span class="field-error">Номер должен быть действительным (приводится к +7...).</span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">E-mail *</label>
                    <input type="email" id="email" name="email"
                           value="<?= htmlspecialchars($values['email'] ?? '') ?>"
                           <?= !empty($errors['email']) ? 'class="error"' : '' ?>>
                    <?php if (!empty($errors['email'])): ?>
                        <span class="field-error">Введите корректный email.</span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="birth_date">Дата рождения *</label>
                    <input type="date" id="birth_date" name="birth_date"
                           value="<?= htmlspecialchars($values['birth_date'] ?? '') ?>"
                           <?= !empty($errors['birth_date']) ? 'class="error"' : '' ?>>
                    <?php if (!empty($errors['birth_date'])): ?>
                        <span class="field-error">Формат ГГГГ-ММ-ДД, не позже сегодня.</span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Пол *</label>
                    <div class="radio-group">
                        <label><input type="radio" name="gender" value="male" <?= ($values['gender'] ?? '') === 'male' ? 'checked' : '' ?> <?= !empty($errors['gender']) ? 'class="error"' : '' ?>> Мужской</label>
                        <label><input type="radio" name="gender" value="female" <?= ($values['gender'] ?? '') === 'female' ? 'checked' : '' ?> <?= !empty($errors['gender']) ? 'class="error"' : '' ?>> Женский</label>
                    </div>
                    <?php if (!empty($errors['gender'])): ?>
                        <span class="field-error">Выберите пол.</span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="languages">Любимые языки программирования * (можно несколько)</label>
                    <select id="languages" name="languages[]" multiple size="6"
                            <?= !empty($errors['languages']) ? 'class="error"' : '' ?>>
                        <?php foreach ($languages_from_db as $lang): ?>
                            <option value="<?= htmlspecialchars($lang) ?>" <?= in_array($lang, $values['languages'] ?? []) ? 'selected' : '' ?>><?= htmlspecialchars($lang) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['languages'])): ?>
                        <span class="field-error">Выберите хотя бы один язык.</span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="biography">Биография *</label>
                    <textarea id="biography" name="biography" rows="5"
                              <?= !empty($errors['biography']) ? 'class="error"' : '' ?>><?= htmlspecialchars($values['biography'] ?? '') ?></textarea>
                    <?php if (!empty($errors['biography'])): ?>
                        <span class="field-error">Биография обязательна, не более 10000 символов.</span>
                    <?php endif; ?>
                </div>

                <div class="form-group checkbox">
                    <label>
                        <input type="checkbox" name="contract_accepted" value="1" <?= !empty($values['contract_accepted']) ? 'checked' : '' ?> <?= !empty($errors['contract_accepted']) ? 'class="error"' : '' ?>>
                        Я ознакомлен(а) с контрактом *
                    </label>
                    <?php if (!empty($errors['contract_accepted'])): ?>
                        <span class="field-error">Необходимо подтвердить согласие.</span>
                    <?php endif; ?>
                </div>

                <button type="submit"><?= $is_logged_in ? 'Сохранить изменения' : 'Отправить анкету' ?></button>
            </form>
        </div>

        <div class="image-column">
            
            <img src="kart.jpg" alt="Profile decoration" class="profile-img">
        </div>
    </div>

    <div class="back-link">
        <a href="registration.php">🔐 Войти (если уже есть логин/пароль)</a>
        <a href="prosmotr.php">📊 Просмотреть анкеты</a>
        <a href="admin.php"> АДМИН</a>
    </div>
    <?php if (!$is_logged_in): ?>
        <div class="auth-hint" style="text-align:center; margin-top:20px;">
            <small>Для редактирования данных нужна авторизация</small>
        </div>
    <?php endif; ?>
</div>

<div class="container">
        <!-- Блок: изменение структуры БД для 6-й лабораторной -->
        <div class="task" style="margin-top: 30px;">
            <h2>Изменение структуры базы данных</h2>
            <div class="screenshot">
                <img src="1.png" alt="ALTER TABLE admin">
                <div class="caption">Скриншот: добавление колонок login и password_hash в новую таблицу admin</div>
            </div>

            <div class="screenshot">
                <img src="2.png" alt="INSERT TABLE admin">
                <div class="caption">Скриншот: вставка логина и пароля в  колонки login и password_hash (в новой таблице admin)</div>
            </div>
        </div>
        

</div>
</body>
</html>