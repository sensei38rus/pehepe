<?php

// 1. Получение расширения файла
function getFileExtension($filename) {
    if (preg_match('/\.([^.]+)$/', $filename, $matches)) {
        return $matches[1];
    }
    return '';
}

// 2. Проверка типа файла
function checkFileType($filename) {
    $patterns = [
        'archive' => '/\.(zip|rar|7z|tar|gz|bz2)$/i',
        'audio' => '/\.(mp3|wav|ogg|flac|aac|m4a)$/i',
        'video' => '/\.(mp4|avi|mkv|mov|wmv|flv|webm)$/i',
        'image' => '/\.(jpg|jpeg|png|gif|bmp|svg|webp)$/i'
    ];
    
    $result = [];
    foreach ($patterns as $type => $pattern) {
        if (preg_match($pattern, $filename)) {
            $result[] = $type;
        }
    }
    return $result;
}

// 3. Поиск строки в тегах <title>
function findTitle($html) {
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
        return trim($matches[1]);
    }
    return null;
}

// 4. Поиск всех ссылок в тегах <a> (атрибут href)
function findAllLinks($html) {
    preg_match_all('/<a[^>]*\shref=["\']([^"\']+)["\'][^>]*>/is', $html, $matches);
    return $matches[1];
}

// 5. Поиск всех ссылок на картинки в тегах <img> (атрибут src)
function findAllImages($html) {
    preg_match_all('/<img[^>]*\ssrc=["\']([^"\']+)["\'][^>]*>/is', $html, $matches);
    return $matches[1];
}

// 6. Подсветка заданной строки с помощью <strong>
function highlightString($text, $search) {
    $pattern = '/' . preg_quote($search, '/') . '/i';
    return preg_replace($pattern, '<strong>$0</strong>', $text);
}

// 7. Замена смайликов на картинки
function replaceSmilies($text) {
    $smilies = [
        '/:\)/' => '<img src="smile.png" alt=":)" class="smiley">',
        '/;\)/' => '<img src="wink.png" alt=";)" class="smiley">',
        '/:\(/' => '<img src="sad.png" alt=":(" class="smiley">'
    ];
    
    return preg_replace(array_keys($smilies), array_values($smilies), $text);
}

// 8. Удаление случайных повторяющихся пробелов
function removeDuplicateSpaces($string) {
    return preg_replace('/\s+/', ' ', trim($string));
}




// 1. Расширение файла
echo "1. Расширение файла picture.jpg: " . getFileExtension('picture.jpg') . "\n";

// 2. Проверка типа файла
$file = 'video.mp4';
$types = checkFileType($file);
echo "2. Файл {$file} является: " . implode(', ', $types) . "\n";

// 3. Поиск title
$html = '<html><head><title>Мой сайт</title></head><body>...</body></html>';
echo "3. Найден title: " . findTitle($html) . "\n";

// 4. Поиск ссылок
$html = '<a href="page1.php">Ссылка 1</a> <a href="page2.php">Ссылка 2</a>';
$links = findAllLinks($html);
echo "4. Найденные ссылки: " . implode(', ', $links) . "\n";

// 5. Поиск картинок
$html = '<img src="image1.jpg"> <img src="image2.png">';
$images = findAllImages($html);
echo "5. Найденные картинки: " . implode(', ', $images) . "\n";

// 6. Подсветка строки
$text = "PHP - лучший язык программирования";
echo "6. Подсветка: " . highlightString($text, "PHP") . "\n";

// 7. Замена смайликов
$text = "Привет :) Как дела? ;) Грустно :(";
echo "7. Замена смайликов: " . replaceSmilies($text) . "\n";

// 8. Удаление пробелов
$text = "Это    текст   с   множественными     пробелами";
echo "8. Исправленный текст: " . removeDuplicateSpaces($text) . "\n";

?>