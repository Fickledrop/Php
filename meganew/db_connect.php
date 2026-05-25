<?php
define('DB_HOST', 'localhost'); 
define('DB_USER', 'root');  
define('DB_PASS', '');   
define('DB_NAME', 'jopa');  

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME); 

if ($conn->connect_error) {
    die("Ошибка подключения к базе данных: " . $conn->connect_error);
}
?>