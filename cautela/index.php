<?php
require 'db.php';

if (isset($_POST['login_btn'])) {
    $login = $_POST['user'];
    $senha = $_POST['pass'];

    $stmt = $pdoUser->prepare("SELECT * FROM usuarios WHERE login = :login");
    $stmt->execute(['login' => $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['usuario_logado'] = $user['login'];
        header("Location: home.php");
        exit;
    } else {
        $erro = "Usuário ou senha incorretos!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Siscarga PELC2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); /* Azul Militar Moderno */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .card-login {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .card-header {
            background-color: white;
            border-bottom: none;
            padding-top: 30px;
            text-align: center;
        }
        .btn-custom {
            background-color: #1e3c72;
            color: white;
            transition: 0.3s;
        }
        .btn-custom:hover {
            background-color: #162e58;
            color: white;
        }
        .brasao-img {
            width: 80px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

    <div class="card card-login">
        <div class="card-header">
            <?php if(file_exists('bcomge.png')): ?>
                <img src="bcomge.png" class="brasao-img" alt="Brasão">
            <?php else: ?>
                <i class="fas fa-shield-alt fa-3x text-primary"></i>
            <?php endif; ?>
            <h4 class="mt-2 text-primary fw-bold">SISCARGA PELC</h4>
            <p class="text-muted small">Acesso Restrito</p>
        </div>
        <div class="card-body p-4">
            <?php if(isset($erro)): ?>
                <div class="alert alert-danger py-2 text-center text-small">
                    <i class="fas fa-exclamation-circle"></i> <?= $erro ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted">Usuário</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-user"></i></span>
                        <input type="text" name="user" class="form-control" placeholder="Identificação" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted">Senha</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-lock"></i></span>
                        <input type="password" name="pass" class="form-control" placeholder="Sua senha" required>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" name="login_btn" class="btn btn-custom btn-lg shadow-sm">
                        ENTRAR <i class="fas fa-sign-in-alt ms-2"></i>
                    </button>
                </div>
            </form>
        </div>
        <div class="card-footer bg-light text-center py-3">
            <small><a href="register.php" class="text-decoration-none text-muted">Criar conta</a></small>
            <span class="text-muted mx-2">|</span>
            <small><a href="reset.php" class="text-decoration-none text-muted">Esqueci a senha</a></small>
        </div>
    </div>

</body>
</html>