<?php


trait LoggerTrait {
    public function log($message) {
        echo "[LOG] $message<br>\n";
    }
}

interface Workable {
    public function work();
}


class Animal {
    public $name;           // публичное свойство
    protected $age;         // защищенное свойство
    private $id;            // приватное свойство
    private static $counter = 0;
    
    public function __construct($name, $age) {
        $this->name = $name;
        $this->age = $age;
        $this->id = ++self::$counter;
    }
    
    // Геттер для приватного свойства
    public function getId() {
        return $this->id;
    }
    
    // Геттер для защищенного свойства
    public function getAge() {
        return $this->age;
    }
    
    // Сеттер для защищенного свойства
    public function setAge($age) {
        if ($age > 0) {
            $this->age = $age;
        }
    }
    
    public function makeSound() {
        return "Издает звук";
    }
    
    public function getInfo() {
        return "ID: {$this->id}, Имя: {$this->name}, Возраст: {$this->age}";
    }
}


class Dog extends Animal implements Workable {
    use LoggerTrait;
    
    public $breed;  
    
    public function __construct($name, $age, $breed) {
        parent::__construct($name, $age);
        $this->breed = $breed;
        $this->log("Создана собака: $name");
    }
   
    public function makeSound() {
        return "Гав-гав!";
    }
    
   
    public function work() {
        $this->log("{$this->name} охраняет дом");
        return "{$this->name} охраняет дом";
    }
    
    
    public function play() {
        return "{$this->name} играет с мячом";
    }
    
    public function getInfo() {
        return parent::getInfo() . ", Порода: {$this->breed}";
    }
}

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>ООП в PHP</title>
    <style>
        body {
            font-family: monospace;
            margin: 20px;
            line-height: 1.5;
        }
        h3 {
            color: #333;
            margin-top: 20px;
        }
        hr {
            margin: 20px 0;
        }
    </style>
</head>
<body>\n";

echo "<h3>СОЗДАНИЕ ОБЪЕКТОВ</h3>\n";

// Создаем экземпляр базового класса
$animal = new Animal("Барсик", 3);
echo "<strong>Базовый класс:</strong><br>\n";
echo $animal->getInfo() . "<br>\n";
echo "Звук: " . $animal->makeSound() . "<br><br>\n";

// Создаем экземпляр унаследованного класса
$dog = new Dog("Рекс", 5, "Овчарка");
echo "<strong>Унаследованный класс:</strong><br>\n";
echo $dog->getInfo() . "<br>\n";
echo "Звук: " . $dog->makeSound() . "<br>\n";
echo $dog->work() . "<br>\n";
echo $dog->play() . "<br><br>\n";

echo "<h3>РАБОТА СО СВОЙСТВАМИ</h3>\n";

// Работа с защищенным свойством через геттер/сеттер
echo "Возраст Рекса: " . $dog->getAge() . " лет<br>\n";
$dog->setAge(6);
echo "Новый возраст: " . $dog->getAge() . " лет<br>\n";

// Работа с приватным свойством через геттер
echo "ID Рекса: " . $dog->getId() . "<br>\n";
echo "ID Барсика: " . $animal->getId() . "<br><br>\n";

echo "<h3>ПРОВЕРКА ИНТЕРФЕЙСА</h3>\n";
if ($dog instanceof Workable) {
    echo "✓ Dog реализует интерфейс Workable<br><br>\n";
}

echo "<h3>ПРОВЕРКА ТРЕЙТА</h3>\n";
echo "Логи из трейта:<br>\n";
$dog->log("Дополнительное сообщение");

echo "</body>
</html>\n";

?>