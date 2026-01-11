<?php
require 'db.php';

// Se já estiver logado como admin, joga direto pro painel
if (isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true) {
    header("Location: admin.php");
    exit;
}

if (isset($_POST['admin_login_btn'])) {
    $user = $_POST['user'];
    $pass = $_POST['pass'];

    // CREDENCIAIS FIXAS (Conforme solicitado)
    $adminUser = 'suporte';
    $adminPass = 'S#mCL@rIDa&ede';

    if ($user === $adminUser && $pass === $adminPass) {
        $_SESSION['admin_logado'] = true; // Cria a "chave mestre" na sessão
        header("Location: admin.php");
        exit;
    } else {
        $erro = "Credenciais administrativas inválidas!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Siscarga PELC2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            /* Fundo levemente diferente (mais escuro) para diferenciar do login comum */
            background: linear-gradient(135deg, #232526 0%, #414345 100%); 
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .card-login {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
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
            background-color: #333;
            color: white;
            transition: 0.3s;
        }
        .btn-custom:hover {
            background-color: #000;
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
            <?php if(file_exists('brasao.png')): ?>
                <img src="brasao.png" class="brasao-img" alt="Brasão">
            <?php else: ?>
                <i class="fas fa-user-shield fa-3x text-dark"></i>
            <?php endif; ?>
            <h4 class="mt-2 text-dark fw-bold">ADMINISTRAÇÃO</h4>
            <p class="text-danger small fw-bold">Acesso Exclusivo Suporte</p>
        </div>
        <div class="card-body p-4">
            <?php if(isset($erro)): ?>
                <div class="alert alert-danger py-2 text-center text-small">
                    <i class="fas fa-lock"></i> <?= $erro ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted">Usuário Admin</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-user-cog"></i></span>
                        <input type="text" name="user" class="form-control" placeholder="Usuário" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted">Senha Admin</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-key"></i></span>
                        <input type="password" name="pass" class="form-control" placeholder="Senha" required>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" name="admin_login_btn" class="btn btn-custom btn-lg shadow-sm">
                        ACESSAR PAINEL <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </form>
        </div>
        <div class="card-footer bg-light text-center py-3">
            <small><a href="home.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left"></i> Voltar para Home (Usuário)</a></small>
        </div>
    </div>

</body>
</html>