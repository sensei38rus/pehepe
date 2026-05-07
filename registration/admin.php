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
        font-family: Arial, sans-serif;
        background: #f0f0f0;
        padding: 20px;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        background: white;
        border-radius: 5px;
        border: 1px solid #ddd;
    }
    
    .header {
        background: #4a90e2;
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .header h1 {
        font-size: 24px;
    }
    
    .stats {
        background: rgba(255,255,255,0.2);
        padding: 5px 15px;
        border-radius: 3px;
        text-align: center;
    }
    
    .stats .number {
        font-size: 24px;
        font-weight: bold;
    }
    
    .content {
        padding: 20px;
    }
    
    .toolbar {
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .select-all {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .delete-btn {
        background: #e74c3c;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 3px;
        cursor: pointer;
    }
    
    .delete-btn:hover {
        background: #c0392b;
    }
    
    .message {
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 3px;
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
    
    .info-message {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
        padding: 10px;
        margin-bottom: 20px;
    }
    
    .applications-table {
        overflow-x: auto;
    }
    
    .applications-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .applications-table th,
    .applications-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    .applications-table th {
        background: #f5f5f5;
        font-weight: bold;
    }
    
    .applications-table tr:hover {
        background: #f9f9f9;
    }
    
    .applications-table tr.selected {
        background: #e3f2fd;
    }
    
    .checkbox-col {
        width: 30px;
        text-align: center;
    }
    
    .view-link {
        color: #4a90e2;
        text-decoration: none;
        cursor: pointer;
    }
    
    .view-link:hover {
        text-decoration: underline;
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
    }
    
    .modal-content {
        background: white;
        margin: 10% auto;
        padding: 20px;
        width: 500px;
        max-width: 90%;
        border-radius: 5px;
        position: relative;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #ddd;
    }
    
    .close {
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }
    
    .close:hover {
        color: #333;
    }
    
    .app-detail {
        margin-bottom: 10px;
        padding: 8px;
        background: #f9f9f9;
        border-radius: 3px;
    }
    
    .app-detail strong {
        display: inline-block;
        width: 130px;
    }
    
    .no-applications {
        text-align: center;
        padding: 40px;
        color: #999;
    }
    
    .back-link {
        display: inline-block;
        margin-top: 20px;
        color: #4a90e2;
        text-decoration: none;
    }
    
    .back-link:hover {
        text-decoration: underline;
    }
    
    .footer-note {
        margin-top: 20px;
        padding: 10px;
        background: #f5f5f5;
        font-size: 12px;
        color: #666;
        text-align: center;
    }
    
    @media (max-width: 768px) {
        .header {
            flex-direction: column;
            text-align: center;
        }
        
        .toolbar {
            flex-direction: column;
            gap: 10px;
        }
        
        .modal-content {
            width: 95%;
            margin: 20% auto;
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
                    <p> Пока нет ни одной заявки</p>
                    <p>Заявки появятся здесь после того, как пользователи заполнят форму регистрации</p>
                </div>
            <?php endif; ?>
            
            <a href="application.php" class="back-link">← Вернуться на главную страницу</a>
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