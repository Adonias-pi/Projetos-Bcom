<?php
// db.php
$host = 'localhost';
$user = 'root'; // Seu usuário do MySQL (padrão XAMPP é root)
$pass = 'Lucas@24232723';     // Sua senha do MySQL (padrão XAMPP é vazio)

try {
    // Conexão com banco de Usuários (pelc2)
    $pdoUser = new PDO("mysql:host=$host;dbname=pelc2", $user, $pass);
    $pdoUser->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Conexão com banco de Carga (carga)
    $pdoCarga = new PDO("mysql:host=$host;dbname=carga", $user, $pass);
    $pdoCarga->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Inicia a sessão em todas as páginas que incluírem este arquivo
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>