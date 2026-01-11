<?php
require 'db.php';

// Verifica se está logado (Admin ou Usuário Comum)
if (!isset($_SESSION['admin_logado']) && !isset($_SESSION['usuario_logado'])) {
    header("Location: index.php");
    exit;
}

// Inicializa a busca
$termo = "";
$logs = [];

try {
    // Consulta Padrão
    $sql = "SELECT * FROM historico ORDER BY data_devolucao DESC";
    $params = [];

    // Se tiver busca
    if (isset($_GET['busca']) && !empty($_GET['busca'])) {
        $termo = $_GET['busca'];
        $sql = "SELECT * FROM historico WHERE responsavel LIKE :termo OR material LIKE :termo ORDER BY data_devolucao DESC";
        $params = ['termo' => "%$termo%"];
    }

    $stmt = $pdoCarga->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Se der erro (ex: tabela não existe), mostra aviso na tela
    $erro_banco = "Erro ao buscar histórico: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Cautelas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(90deg, #2c3e50 0%, #3498db 100%); }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><i class="fas fa-history me-2"></i>Histórico de Cautelas</a>
            <div>
                <?php if(isset($_SESSION['admin_logado'])): ?>
                    <a href="admin.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left"></i> Voltar (Admin)</a>
                <?php else: ?>
                    <a href="home.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left"></i> Voltar (Home)</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        
        <?php if(isset($erro_banco)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= $erro_banco ?> <br>
                <small>Verifique se você criou a tabela 'historico' no MySQL Workbench.</small>
            </div>
        <?php endif; ?>

        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <form method="GET" class="row g-2">
                    <div class="col-md-10">
                        <input type="text" name="busca" class="form-control" placeholder="Pesquisar por nome do militar ou material..." value="<?= htmlspecialchars($termo) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Data Devolução</th>
                                <th>Militar Responsável</th>
                                <th>Material</th>
                                <th>Qtd</th>
                                <th>Retirado em</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($logs) > 0): ?>
                                <?php foreach($logs as $log): ?>
                                <tr>
                                    <td class="fw-bold text-primary">
                                        <i class="fas fa-calendar-check me-2"></i>
                                        <?= date('d/m/Y H:i', strtotime($log['data_devolucao'])) ?>
                                    </td>
                                    <td class="fw-bold"><?= $log['responsavel'] ?></td>
                                    <td><?= $log['material'] ?></td>
                                    <td><span class="badge bg-secondary"><?= $log['quantidade'] ?></span></td>
                                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($log['data_retirada'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-history fa-3x mb-3"></i><br>
                                        <?php if($termo): ?>
                                            Nenhum resultado para "<b><?= $termo ?></b>".
                                        <?php else: ?>
                                            Nenhum histórico registrado ainda.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>