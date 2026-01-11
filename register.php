<?php
require 'db.php';

if (isset($_POST['register_btn'])) {
    $login = $_POST['user'];
    $senha = $_POST['pass'];

    // Verifica se já existe
    $stmt = $pdoUser->prepare("SELECT id FROM usuarios WHERE login = :login");
    $stmt->execute(['login' => $login]);
    
    if ($stmt->rowCount() > 0) {
        $msg = "Usuário já existe!";
    } else {
        // CRIPTOGRAFA A SENHA (Segurança essencial)
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $pdoUser->prepare("INSERT INTO usuarios (login, senha) VALUES (:login, :senha)");
        if ($stmt->execute(['login' => $login, 'senha' => $senhaHash])) {
            header("Location: index.php"); // Redireciona para login após criar
            exit;
        } else {
            $msg = "Erro ao criar conta.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head><title>Criar Conta</title></head>
<body>
    <center>
        <h2>Criar Nova Conta</h2>
        <?php if(isset($msg)) echo "<p>$msg</p>"; ?>
        <form method="POST">
            <input type="text" name="user" placeholder="Novo Usuário" required><br><br>
            <input type="password" name="pass" placeholder="Nova Senha" required><br><br>
            <button type="submit" name="register_btn">Registrar</button>
        </form>
        <br><a href="index.php">Voltar para Login</a>
    </center>
</body>
</html>