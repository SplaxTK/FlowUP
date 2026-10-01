<?php
//config.php

$host = 'localhost';
$dbname = 'flowup'; 
$user = 'root';        
$pass = '#Hamburguer136415110802012345678910';            

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados.");
}