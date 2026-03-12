<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Таблица умножения</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
        }
        td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
            font-family: Arial, sans-serif;
        }
        td:first-child {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        tr:first-child td {
            background-color: #e0e0e0;
            font-weight: bold;
        }
    </style>
</head>
<body>
   
    <table>
        <caption><strong>Таблица умножения</strong></caption>
        <?php
       
        echo "<tr><td></td>"; 
      
        for ($j = 1; $j <= 10; $j++) {
            echo "<td><strong>{$j}</strong></td>";
        }
        echo "</tr>";
        
        for ($i = 1; $i <= 10; $i++) {
            echo "<tr>";
           
            echo "<td><strong>{$i}</strong></td>";
            
           
            for ($j = 1; $j <= 10; $j++) {
                $result = $i * $j;
                echo "<td>{$result}</td>";
            }
            
            echo "</tr>";
        }
        ?>
    </table>
    
  
</body>
</html>