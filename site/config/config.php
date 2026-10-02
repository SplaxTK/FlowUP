<?php
<<<<<<< HEAD
require_once dirname(__DIR__, 2) . '/config/database.php';
=======
$host = 'localhost';
$db = 'flowup';
$user = 'root';
<<<<<<< HEAD
$pass = getenv('SENHA');
=======
$pass = '';
>>>>>>> 027ff9b178ed55261883e0aab599b3878f02e864
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES => false,
];

try {
	$pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
	exit('Falha na conexão com o banco de dados. Verifique se o MySQL está ativo e se o banco flowup foi importado.');
}
>>>>>>> b1f403ede398c5ad9831e3e31011fc1355d841df
