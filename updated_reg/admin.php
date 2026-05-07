<?php
session_start();

// Определяем разделитель (должен совпадать с тем, что используется в form.php)
$delimiter = '|';

// Файл с заявками
$applicationsFile = 'applications/applications.csv';

// Обработка удаления заявок (мягкое удаление - меняем статус)
$deleteMessage = '';
$deleteError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_applications'])) {
    if (isset($_POST['selected_applications']) && is_array($_POST['selected_applications'])) {
        $selectedRows = $_POST['selected_applications'];
        $deletedCount = 0;
        
        if (file_exists($applicationsFile)) {
            // Читаем все заявки
            $lines = file($applicationsFile, FILE_IGNORE_NEW_LINES);
            $newLines = [];
            
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                
                // Проверяем, нужно ли пометить эту строку как удаленную
                $isSelected = false;
                foreach ($selectedRows as $selectedRowHash) {
                    if (md5($line) === $selectedRowHash) {
                        $isSelected = true;
                        break;
                    }
                }
                
                if ($isSelected) {
                    // Помечаем как удаленную: добавляем статус "deleted" в начало или конец строки
                    // Формат: статус|остальные_данные
                    $parts = explode($delimiter, $line);
                    // Добавляем статус в начало (0 - активна, 1 - удалена)
                    array_unshift($parts, '1');
                    $newLines[] = implode($delimiter, $parts);
                    $deletedCount++;
                } else {
                    // Проверяем, есть ли уже статус у строки
                    $parts = explode($delimiter, $line);
                    // Если первый элемент не похож на статус (не 0 или 1), добавляем статус 0
                    if (!isset($parts[0]) || ($parts[0] !== '0' && $parts[0] !== '1')) {
                        array_unshift($parts, '0');
                        $newLines[] = implode($delimiter, $parts);
                    } else {
                        $newLines[] = $line;
                    }
                }
            }
            
            // Сохраняем обновленный файл
            if (file_put_contents($applicationsFile, implode(PHP_EOL, $newLines) . PHP_EOL, LOCK_EX)) {
                $deleteMessage = $deletedCount > 0 ? "✓ Помечено как удалено заявок: $deletedCount" : "✓ Операция выполнена";
            } else {
                $deleteError = "✗ Ошибка при обновлении файла заявок";
            }
        } else {
            $deleteError = "✗ Файл с заявками не найден";
        }
    } else {
        $deleteError = "✗ Не выбрано ни одной заявки для удаления";
    }
}

// Функция для парсинга строки заявки
function parseApplicationLine($line, $delimiter) {
    if (empty(trim($line))) return null;
    
    $parts = explode($delimiter, $line);
    
    // Определяем наличие статуса
    $status = '0'; // по умолчанию активна
    $dataStartIndex = 0;
    
    // Проверяем, есть ли статус в первом элементе
    if (isset($parts[0]) && ($parts[0] === '0' || $parts[0] === '1')) {
        $status = $parts[0];
        $dataStartIndex = 1;
    }
    
    // Если статус удален, возвращаем null (не показываем)
    if ($status === '1') {
        return null;
    }
    
    // Ожидаемая структура данных (без статуса):
    // 0: дата, 1: IP, 2: имя, 3: фамилия, 4: email, 5: телефон, 6: тематика, 7: оплата, 8: рассылка
    $data = array_slice($parts, $dataStartIndex);
    
    return [
        'date' => $data[0] ?? 'Не указано',
        'ip' => $data[1] ?? 'Не указан',
        'name' => $data[2] ?? 'Не указано',
        'surname' => $data[3] ?? 'Не указано',
        'email' => $data[4] ?? 'Не указан',
        'phone' => $data[5] ?? 'Не указан',
        'topic' => $data[6] ?? 'Не указана',
        'payment' => $data[7] ?? 'Не указан',
        'newsletter' => $data[8] ?? 'Нет',
        'raw_line' => $line,
        'row_hash' => md5($line)
    ];
}

// Получаем список всех активных заявок
$applications = [];
if (file_exists($applicationsFile)) {
    $lines = file($applicationsFile, FILE_IGNORE_NEW_LINES);
    
    foreach ($lines as $line) {
        if (empty(trim($line))) continue;
        
        $appData = parseApplicationLine($line, $delimiter);
        if ($appData !== null) {
            $applications[] = $appData;
        }
    }
    
    // Сортируем заявки по дате (новые сверху)
    usort($applications, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
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
                <div>активных заявок</div>
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
            
            <div class="message info-message">
                 Удаление заявок выполняется "мягко" - они помечаются как удаленные, но остаются в базе.
                При необходимости их можно восстановить через прямой доступ к файлу.
            </div>
            
            <?php if ($totalApplications > 0): ?>
                <form method="POST" action="" id="applications-form">
                    <div class="toolbar">
                        <div class="select-all">
                            <input type="checkbox" id="select-all-checkbox">
                            <label for="select-all-checkbox">Выбрать все</label>
                        </div>
                        <button type="submit" name="delete_applications" class="delete-btn" 
                                onclick="return confirm('Вы уверены, что хотите пометить выбранные заявки как удаленные? Их можно будет восстановить только через прямой доступ к файлу.');">
                            🗑️ Пометить как удаленные
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
                                    <th>IP адрес</th>
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
                                    <tr data-row-hash="<?php echo htmlspecialchars($app['row_hash']); ?>">
                                        <td class="checkbox-col">
                                            <input type="checkbox" name="selected_applications[]" 
                                                   value="<?php echo htmlspecialchars($app['row_hash']); ?>"
                                                   class="app-checkbox">
                                        </td>
                                        <td><?php echo htmlspecialchars($app['date']); ?></td>
                                        <td class="ip-col"><?php echo htmlspecialchars($app['ip']); ?></td>
                                        <td><?php echo htmlspecialchars($app['name']); ?></td>
                                        <td><?php echo htmlspecialchars($app['surname']); ?></td>
                                        <td><?php echo htmlspecialchars($app['email']); ?></td>
                                        <td><?php echo htmlspecialchars($app['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($app['topic']); ?></td>
                                        <td><?php echo htmlspecialchars($app['payment']); ?></td>
                                        <td><?php echo ($app['newsletter'] ?? 'Нет') === 'Да' ? '✓ Да' : '✗ Нет'; ?></td>
                                        <td>
                                            <a href="javascript:void(0)" class="view-link" 
                                               onclick='showDetails(<?php echo json_encode($app); ?>)'>
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
                    <p>📭 Нет активных заявок</p>
                    <p>Заявки появятся здесь после того, как пользователи заполнят форму регистрации</p>
                    <p style="font-size: 12px; margin-top: 20px;">ℹ️ Возможно, все заявки были помечены как удаленные</p>
                </div>
            <?php endif; ?>
            
            <a href="application.php" class="back-link">← Вернуться на главную страницу</a>
            
            <div class="footer-note">
                 Система мягкого удаления: удаленные заявки не отображаются в списке, но физически остаются в файле с пометкой "1" в начале строки
            </div>
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
        function showDetails(appData) {
            const modal = document.getElementById('detailsModal');
            const modalContent = document.getElementById('modalContent');
            
            // Форматируем содержимое для отображения
            let html = `
                <div class="app-detail"><strong> Дата и время:</strong> ${escapeHtml(appData.date)}</div>
                <div class="app-detail"><strong> IP адрес:</strong> ${escapeHtml(appData.ip)}</div>
                <div class="app-detail"><strong> Имя:</strong> ${escapeHtml(appData.name)}</div>
                <div class="app-detail"><strong> Фамилия:</strong> ${escapeHtml(appData.surname)}</div>
                <div class="app-detail"><strong> Email:</strong> ${escapeHtml(appData.email)}</div>
                <div class="app-detail"><strong> Телефон:</strong> ${escapeHtml(appData.phone)}</div>
                <div class="app-detail"><strong> Тематика:</strong> ${escapeHtml(appData.topic)}</div>
                <div class="app-detail"><strong> Способ оплаты:</strong> ${escapeHtml(appData.payment)}</div>
                <div class="app-detail"><strong> Получать рассылку:</strong> ${escapeHtml(appData.newsletter) === 'Да' ? '✓ Да' : '✗ Нет'}</div>
            `;
            
            modalContent.innerHTML = html;
            modal.style.display = 'flex';
        }
        
        // Вспомогательная функция для экранирования HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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
        const form = document.getElementById('applications-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const selected = document.querySelectorAll('.app-checkbox:checked');
                if (selected.length === 0) {
                    e.preventDefault();
                    alert('Пожалуйста, выберите хотя бы одну заявку');
                } else {
                    const confirmed = confirm(`Вы действительно хотите пометить ${selected.length} заявку(и) как удаленные?\n\nОни исчезнут из списка, но останутся в файле с пометкой "удалена".`);
                    if (!confirmed) {
                        e.preventDefault();
                    }
                }
            });
        }
    </script>
</body>
</html>