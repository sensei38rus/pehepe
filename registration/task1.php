<?php
// Инициализация переменных
$name = $surname = $email = $phone = $topic = $payment = $newsletter = '';
$errors = [];
$success = false;

// Проверяем, была ли отправлена форма
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $topic = $_POST['topic'] ?? '';
    $payment = $_POST['payment'] ?? '';
    $newsletter = $_POST['newsletter'] ?? 'no';
    
    // Валидация данных
    if (empty($name)) {
        $errors['name'] = 'Введите имя';
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]{2,50}$/u', $name)) {
        $errors['name'] = 'Имя должно содержать только буквы (от 2 до 50 символов)';
    }
    
    if (empty($surname)) {
        $errors['surname'] = 'Введите фамилию';
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]{2,50}$/u', $surname)) {
        $errors['surname'] = 'Фамилия должна содержать только буквы (от 2 до 50 символов)';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Введите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email адрес';
    }
    
    if (empty($phone)) {
        $errors['phone'] = 'Введите телефон';
    } elseif (!preg_match('/^[\+\d\s\-\(\)]{10,20}$/', $phone)) {
        $errors['phone'] = 'Введите корректный номер телефона';
    }
    
    if (empty($topic)) {
        $errors['topic'] = 'Выберите тематику конференции';
    }
    
    if (empty($payment)) {
        $errors['payment'] = 'Выберите способ оплаты';
    }
    
    // Если нет ошибок, сохраняем данные
    if (empty($errors)) {
        // Создаем уникальное имя файла
        $timestamp = date('Y-m-d_H-i-s');
        $uniqueId = uniqid();
        $filename = "applications/application_{$timestamp}_{$uniqueId}.txt";
        
        // Создаем директорию, если её нет
        if (!is_dir('applications')) {
            mkdir('applications', 0777, true);
        }
        
        // Данные для сохранения
        $data = "========================================\n";
        $data .= "ЗАЯВКА НА УЧАСТИЕ В КОНФЕРЕНЦИИ\n";
        $data .= "========================================\n";
        $data .= "Дата и время отправки: " . date('Y-m-d H:i:s') . "\n";
        $data .= "----------------------------------------\n";
        $data .= "Имя: $name\n";
        $data .= "Фамилия: $surname\n";
        $data .= "Email: $email\n";
        $data .= "Телефон: $phone\n";
        $data .= "Тематика: $topic\n";
        $data .= "Способ оплаты: $payment\n";
        $data .= "Получать рассылку: " . ($newsletter === 'yes' ? 'Да' : 'Нет') . "\n";
        $data .= "========================================\n";
        $data .= "IP адрес: " . $_SERVER['REMOTE_ADDR'] . "\n";
        $data .= "User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n";
        $data .= "========================================\n\n";
        
        // Сохраняем файл
        if (file_put_contents($filename, $data, LOCK_EX)) {
            $success = true;
            
            // Очищаем переменные формы после успешной отправки
            $name = $surname = $email = $phone = $topic = $payment = '';
            $newsletter = 'no';
        } else {
            $errors['general'] = 'Ошибка при сохранении данных. Пожалуйста, попробуйте позже.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация на конференцию</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin-bottom: 10px;
        }
        
        .content {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .required::after {
            content: " *";
            color: red;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .error {
            color: red;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 5px;
        }
        
        .radio-group label {
            font-weight: normal;
            display: inline;
            margin-left: 5px;
        }
        
        .checkbox-group {
            margin-top: 10px;
        }
        
        .checkbox-group label {
            font-weight: normal;
            display: inline;
            margin-left: 5px;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Конференция 2026</h1>
            <p>Регистрация на участие</p>
        </div>
        
        <div class="content">
            <?php if ($success): ?>
                <div class="success-message">
                     Ваша заявка успешно принята!<br>
                    Мы свяжемся с вами в ближайшее время.<br>
                    Спасибо за регистрацию!
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="error-message">
                     <?php echo $errors['general']; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name" class="required">Имя</label>
                    <input type="text" id="name" name="name" 
                           value="<?php echo htmlspecialchars($name); ?>" 
                           placeholder="Введите ваше имя">
                    <?php if (isset($errors['name'])): ?>
                        <div class="error"><?php echo $errors['name']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="surname" class="required">Фамилия</label>
                    <input type="text" id="surname" name="surname" 
                           value="<?php echo htmlspecialchars($surname); ?>" 
                           placeholder="Введите вашу фамилию">
                    <?php if (isset($errors['surname'])): ?>
                        <div class="error"><?php echo $errors['surname']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email" class="required">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email); ?>" 
                           placeholder="example@mail.com">
                    <?php if (isset($errors['email'])): ?>
                        <div class="error"><?php echo $errors['email']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="phone" class="required">Телефон</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($phone); ?>" 
                           placeholder="+7 (999) 123-45-67">
                    <?php if (isset($errors['phone'])): ?>
                        <div class="error"><?php echo $errors['phone']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="topic" class="required">Интересующая тематика</label>
                    <select id="topic" name="topic">
                        <option value="">Выберите тематику</option>
                        <option value="Бизнес" <?php echo $topic == 'Бизнес' ? 'selected' : ''; ?>>Бизнес</option>
                        <option value="Технологии" <?php echo $topic == 'Технологии' ? 'selected' : ''; ?>>Технологии</option>
                        <option value="Реклама и Маркетинг" <?php echo $topic == 'Реклама и Маркетинг' ? 'selected' : ''; ?>>Реклама и Маркетинг</option>
                    </select>
                    <?php if (isset($errors['topic'])): ?>
                        <div class="error"><?php echo $errors['topic']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="required">Способ оплаты</label>
                    <div class="radio-group">
                        <div>
                            <input type="radio" id="webmoney" name="payment" value="WebMoney" 
                                   <?php echo $payment == 'WebMoney' ? 'checked' : ''; ?>>
                            <label for="webmoney">WebMoney</label>
                        </div>
                        <div>
                            <input type="radio" id="yandex" name="payment" value="Яндекс.Деньги" 
                                   <?php echo $payment == 'Яндекс.Деньги' ? 'checked' : ''; ?>>
                            <label for="yandex">Яндекс.Деньги</label>
                        </div>
                        <div>
                            <input type="radio" id="paypal" name="payment" value="PayPal" 
                                   <?php echo $payment == 'PayPal' ? 'checked' : ''; ?>>
                            <label for="paypal">PayPal</label>
                        </div>
                        <div>
                            <input type="radio" id="card" name="payment" value="кредитная карта" 
                                   <?php echo $payment == 'кредитная карта' ? 'checked' : ''; ?>>
                            <label for="card">Кредитная карта</label>
                        </div>
                    </div>
                    <?php if (isset($errors['payment'])): ?>
                        <div class="error"><?php echo $errors['payment']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="newsletter" name="newsletter" value="yes" 
                               <?php echo $newsletter == 'yes' ? 'checked' : ''; ?>>
                        <label for="newsletter">Получать рассылку о конференции</label>
                    </div>
                </div>
                
                <button type="submit">Отправить заявку</button>

                <!-- Добавьте в конец формы, после кнопки submit -->
                <div style="margin-top: 20px; text-align: center;">
                    <hr>
                    <a href="task2.php" style="color: #667eea; text-decoration: none;">🔐 Админ-панель</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>