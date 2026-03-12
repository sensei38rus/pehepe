<?php
function multiplicationTable($x = 10, $y = 10) {
    
    $x = max(1, (int)$x);
    $y = max(1, (int)$y);
    
    $output = "<table border='1' cellpadding='5'>";
    
  
    $output .= "<tr><th>*</th>";
    for ($col = 1; $col <= $x; $col++) {
        $output .= "<th>$col</th>";
    }
    $output .= "</tr>";
    
  
    for ($row = 1; $row <= $y; $row++) {
        $output .= "<tr>";
        $output .= "<th>$row</th>"; 
        
        for ($col = 1; $col <= $x; $col++) {
            $output .= "<td>" . ($row * $col) . "</td>";
        }
        
        $output .= "</tr>";
    }
    
    $output .= "</table>";
    
    return $output;
}


echo multiplicationTable();        // Таблица 10×10
echo multiplicationTable(5);       // Таблица 5×10
echo multiplicationTable(7, 7);    // Таблица 7×7
echo multiplicationTable(12, 15);  // Таблица 12×15
?>