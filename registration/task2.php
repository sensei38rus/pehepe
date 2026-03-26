<?php
session_start();

// Директория с заявками
$applicationsDir = 'applications/';

// Обработка удаления заявок
$deleteMessage = '';
$deleteError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_applications'])) {
    if (isset($_POST['selected_applications']) && is_array($_POST['selected_applications'])) {
        $selectedApps = $_POST['selected_applications'];
        $deletedCount = 0;
        
        foreach ($selectedApps as $filename) {
            // Безопасность: проверяем что файл существует и находится в правильной директории
            $filepath = $applicationsDir . basename($filename);
            if (file_exists($filepath) && strpos($filepath, $applicationsDir) === 0) {
                if (unlink($filepath)) {
                    $deletedCount++;
                }
            }
        }
        
        if ($deletedCount > 0) {
            $deleteMessage = "✓ Удалено заявок: $deletedCount";
        } else {
            $deleteError = "✗ Не удалось удалить выбранные заявки";
        }
    } else {
        $deleteError = "✗ Не выбрано ни одной заявки для удаления";
    }
}

// Получаем список всех заявок
$applications = [];
if (is_dir($applicationsDir)) {
    $files = scandir($applicationsDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
            $filepath = $applicationsDir . $file;
            $content = file_get_contents($filepath);
            
            // Парсим содержимое файла
            $appData = [];
            $lines = explode("\n", $content);
            
            foreach ($lines as $line) {
                if (strpos($line, 'Дата и время отправки:') !== false) {
                    $appData['date'] = trim(str_replace('Дата и время отправки:', '', $line));
                } elseif (strpos($line, 'Имя:') !== false) {
                    $appData['name'] = trim(str_replace('Имя:', '', $line));
                } elseif (strpos($line, 'Фамилия:') !== false) {
                    $appData['surname'] = trim(str_replace('Фамилия:', '', $line));
                } elseif (strpos($line, 'Email:') !== false) {
                    $appData['email'] = trim(str_replace('Email:', '', $line));
                } elseif (strpos($line, 'Телефон:') !== false) {
                    $appData['phone'] = trim(str_replace('Телефон:', '', $line));
                } elseif (strpos($line, 'Тематика:') !== false) {
                    $appData['topic'] = trim(str_replace('Тематика:', '', $line));
                } elseif (strpos($line, 'Способ оплаты:') !== false) {
                    $appData['payment'] = trim(str_replace('Способ оплаты:', '', $line));
                } elseif (strpos($line, 'Получать рассылку:') !== false) {
                    $appData['newsletter'] = trim(str_replace('Получать рассылку:', '', $line));
                }
            }
            
            $applications[] = [
                'filename' => $file,
                'filepath' => $filepath,
                'data' => $appData,
                'content' => $content
            ];
        }
    }
    
    // Сортируем заявки по дате (новые сверху)
    usort($applications, function($a, $b) {
        return strtotime($b['data']['date'] ?? '0') - strtotime($a['data']['date'] ?? '0');
    });
}

// Статистика
$totalApplications = count($applications);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - Заявки на конференцию</title>
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
            max-width: 1400px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            margin-bottom: 5px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .stats {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stats .number {
            font-size: 28px;
            font-weight: bold;
        }
        
        .content {
            padding: 30px;
        }
        
        .toolbar {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            align-items: center;
        }
        
        .select-all {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .select-all input {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .delete-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .applications-table {
            width: 100%;
            border-collapse: collapse;
            overflow-x: auto;
            display: block;
        }
        
        .applications-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .applications-table th,
        .applications-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .applications-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
            position: sticky;
            top: 0;
        }
        
        .applications-table tr:hover {
            background: #f8f9fa;
        }
        
        .applications-table tr.selected {
            background: #e3f2fd;
        }
        
        .checkbox-col {
            width: 40px;
            text-align: center;
        }
        
        .checkbox-col input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .application-detail {
            max-width: 300px;
        }
        
        .view-link {
            color: #667eea;
            text-decoration: none;
            cursor: pointer;
            border-bottom: 1px dashed #667eea;
        }
        
        .view-link:hover {
            color: #764ba2;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .modal-header h3 {
            color: #333;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #999;
        }
        
        .close:hover {
            color: #333;
        }
        
        .app-detail {
            margin-bottom: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .app-detail strong {
            display: inline-block;
            width: 150px;
            color: #667eea;
        }
        
        .no-applications {
            text-align: center;
            padding: 60px;
            color: #999;
        }
        
        .no-applications p {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .applications-table {
                overflow-x: scroll;
            }
            
            .applications-table table {
                min-width: 800px;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Админ-панель</h1>
                <p>Управление заявками на конференцию</p>
            </div>
            <div class="stats">
                <div class="number"><?php echo $totalApplications; ?></div>
                <div>всего заявок</div>
            </div>
        </div>
        
        <div class="content">
            <?php if ($deleteMessage): ?>
                <div class="message success">
                    <?php echo $deleteMessage; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($deleteError): ?>
                <div class="message error">
                    <?php echo $deleteError; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($totalApplications > 0): ?>
                <form method="POST" action="" id="applications-form">
                    <div class="toolbar">
                        <div class="select-all">
                            <input type="checkbox" id="select-all-checkbox">
                            <label for="select-all-checkbox">Выбрать все</label>
                        </div>
                        <button type="submit" name="delete_applications" class="delete-btn" 
                                onclick="return confirm('Вы уверены, что хотите удалить выбранные заявки? Это действие нельзя отменить.');">
                             Удалить выбранные
                        </button>
                    </div>
                    
                    <div class="applications-table">
                        <table>
                            <thead>
                                <tr>
                                    <th class="checkbox-col">
                                        <input type="checkbox" id="select-all-checkbox-table">
                                    </th>
                                    <th>Дата отправки</th>
                                    <th>Имя</th>
                                    <th>Фамилия</th>
                                    <th>Email</th>
                                    <th>Телефон</th>
                                    <th>Тематика</th>
                                    <th>Способ оплаты</th>
                                    <th>Рассылка</th>
                                    <th>Детали</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                    <tr data-filename="<?php echo htmlspecialchars($app['filename']); ?>">
                                        <td class="checkbox-col">
                                            <input type="checkbox" name="selected_applications[]" 
                                                   value="<?php echo htmlspecialchars($app['filename']); ?>"
                                                   class="app-checkbox">
                                        </td>
                                        <td><?php echo htmlspecialchars($app['data']['date'] ?? 'Не указано'); ?></td>
                                        <td><?php echo htmlspecialchars($app['data']['name'] ?? 'Не указано'); ?></td>
                                        <td><?php echo htmlspecialchars($app['data']['surname'] ?? 'Не указано'); ?></td>
                                        <td><?php echo htmlspecialchars($app['data']['email'] ?? 'Не указано'); ?></td>
                                        <td><?php echo htmlspecialchars($app['data']['phone'] ?? 'Не указано'); ?></td>
                                        <td><?php echo htmlspecialchars($app['data']['topic'] ?? 'Не указано'); ?></td>
                                        <td><?php echo htmlspecialchars($app['data']['payment'] ?? 'Не указано'); ?></td>
                                        <td><?php echo ($app['data']['newsletter'] ?? 'Нет') === 'Да' ? '✓ Да' : '✗ Нет'; ?></td>
                                        <td>
                                            <a href="javascript:void(0)" class="view-link" 
                                               onclick="showDetails('<?php echo htmlspecialchars(addslashes($app['content'])); ?>')">
                                                Просмотр
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php else: ?>
                <div class="no-applications">
                    <p>📭 Пока нет ни одной заявки</p>
                    <p>Заявки появятся здесь после того, как пользователи заполнят форму регистрации</p>
                </div>
            <?php endif; ?>
            
            <a href="index.php" class="back-link">← Вернуться на главную страницу</a>
        </div>
    </div>
    
    <!-- Модальное окно для просмотра деталей -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Детали заявки</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div id="modalContent">
                <!-- Сюда будет загружено содержимое заявки -->
            </div>
        </div>
    </div>
    
    <script>
        // Выделение строки при выборе чекбокса
        const checkboxes = document.querySelectorAll('.app-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const row = this.closest('tr');
                if (this.checked) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            });
        });
        
        // Выделение всех заявок
        const selectAllTable = document.getElementById('select-all-checkbox-table');
        const selectAllDiv = document.getElementById('select-all-checkbox');
        
        function selectAll(checkbox) {
            const isChecked = checkbox.checked;
            checkboxes.forEach(cb => {
                cb.checked = isChecked;
                const row = cb.closest('tr');
                if (isChecked) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            });
        }
        
        if (selectAllTable) {
            selectAllTable.addEventListener('change', function() {
                selectAll(this);
                if (selectAllDiv) selectAllDiv.checked = this.checked;
            });
        }
        
        if (selectAllDiv) {
            selectAllDiv.addEventListener('change', function() {
                selectAll(this);
                if (selectAllTable) selectAllTable.checked = this.checked;
            });
        }
        
        // Показать детали заявки
        function showDetails(content) {
            const modal = document.getElementById('detailsModal');
            const modalContent = document.getElementById('modalContent');
            
            // Форматируем содержимое для отображения
            let formattedContent = '<pre style="white-space: pre-wrap; font-family: monospace; font-size: 12px;">';
            formattedContent += content.replace(/</g, '&lt;').replace(/>/g, '&gt;');
            formattedContent += '</pre>';
            
            modalContent.innerHTML = formattedContent;
            modal.style.display = 'flex';
        }
        
        // Закрыть модальное окно
        function closeModal() {
            const modal = document.getElementById('detailsModal');
            modal.style.display = 'none';
        }
        
        // Закрытие модального окна при клике вне его
        window.onclick = function(event) {
            const modal = document.getElementById('detailsModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Подтверждение удаления при отправке формы
        document.getElementById('applications-form')?.addEventListener('submit', function(e) {
            const selected = document.querySelectorAll('.app-checkbox:checked');
            if (selected.length === 0) {
                e.preventDefault();
                alert('Пожалуйста, выберите хотя бы одну заявку для удаления');
            } else {
                const confirmed = confirm(`Вы действительно хотите удалить ${selected.length} заявку(и)? Это действие нельзя отменить.`);
                if (!confirmed) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>