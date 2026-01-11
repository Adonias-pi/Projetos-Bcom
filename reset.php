<?php
require 'db.php';

if (isset($_POST['reset_btn'])) {
    $login = $_POST['user'];
    $novaSenha = $_POST['pass'];
    
    // Atualiza a senha apenas se o usuário existir
    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
    $stmt = $pdoUser->prepare("UPDATE usuarios SET senha = :senha WHERE login = :login");
    $stmt->execute(['senha' => $senhaHash, 'login' => $login]);
    
    if ($stmt->rowCount() > 0) {
        $msg = "Senha alterada com sucesso!";
    } else {
        $msg = "Usuário não encontrado.";
    }
}
?>
<!DOCTYPE html>
<body>
    <center>
        <h2>Resetar Senha</h2>
        <?php if(isset($msg)) echo "<p>$msg</p>"; ?>
        <form method="POST">
            <input type="text" name="user" placeholder="Usuário" required><br><br>
            <input type="password" name="pass" placeholder="Nova Senha" required><br><br>
            <button type="submit" name="reset_btn">Alterar Senha</button>
        </form>
        <br><a href="index.php">Voltar</a>
    </center>
</body>