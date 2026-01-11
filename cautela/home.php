<?php
require 'db.php';

// Verifica login
if (!isset($_SESSION['usuario_logado'])) {
    header("Location: index.php");
    exit;
}

// Inicializa variáveis de mensagem
$mensagem = "";
$erro = "";
$tipo_msg = ""; 

// --- VERIFICA SE HÁ MENSAGENS NA SESSÃO ---
if (isset($_SESSION['temp_msg'])) {
    $mensagem = $_SESSION['temp_msg'];
    $tipo_msg = "success";
    unset($_SESSION['temp_msg']); 
}
if (isset($_SESSION['temp_erro'])) {
    $erro = $_SESSION['temp_erro'];
    $tipo_msg = "danger";
    unset($_SESSION['temp_erro']); 
}

// --- LÓGICA 1: ABRIR CAUTELA (ATUALIZADA) ---
if (isset($_POST['abrir_cautela'])) {
    $id_material = $_POST['material_id'];
    $qtd = (int)$_POST['quantidade'];
    
    // Novos Campos
    $pg = $_POST['pg'];
    $nome_guerra = trim($_POST['nome_guerra']);
    $telefone = $_POST['telefone'];
    $om = $_POST['om'];

    // Cria o "Responsável" composto para exibição na lista (Ex: "3º Sgt Silva")
    $responsavel_composto = $pg . " " . $nome_guerra;

    if (empty($nome_guerra)) {
        $_SESSION['temp_erro'] = "É necessário informar o Nome de Guerra!";
    } else {
        $check = $pdoCarga->prepare("SELECT disponivel FROM pel01 WHERE id = :id");
        $check->execute(['id' => $id_material]);
        $mat = $check->fetch(PDO::FETCH_ASSOC);

        if ($mat && $mat['disponivel'] >= $qtd) {
            try {
                $pdoCarga->beginTransaction();

                // 1. Atualiza Estoque
                $sqlUp = "UPDATE pel01 SET disponivel = disponivel - :qtd, cautelado = cautelado + :qtd WHERE id = :id";
                $stmtUp = $pdoCarga->prepare($sqlUp);
                $stmtUp->execute(['qtd' => $qtd, 'id' => $id_material]);

                // 2. Cria Cautela com TODOS os dados
                $sqlIns = "INSERT INTO cautelas (material_id, pg, nome_guerra, telefone, om, responsavel, quantidade) 
                           VALUES (:mat_id, :pg, :nome, :tel, :om, :resp, :qtd)";
                $stmtIns = $pdoCarga->prepare($sqlIns);
                $stmtIns->execute([
                    'mat_id' => $id_material,
                    'pg' => $pg,
                    'nome' => $nome_guerra,
                    'tel' => $telefone,
                    'om' => $om,
                    'resp' => $responsavel_composto, // Mantém compatibilidade com histórico
                    'qtd' => $qtd
                ]);

                $pdoCarga->commit();
                
                $_SESSION['temp_msg'] = "Cautela aberta para <strong>$responsavel_composto</strong> com sucesso!";
                header("Location: home.php"); 
                exit; 
                
            } catch (Exception $e) {
                $pdoCarga->rollBack();
                $_SESSION['temp_erro'] = "Erro no banco: " . $e->getMessage();
            }
        } else {
            $_SESSION['temp_erro'] = "Quantidade indisponível no estoque!";
        }
    }
    header("Location: home.php");
    exit;
}

// --- LÓGICA 2: DEVOLVER ---
if (isset($_POST['devolver_item'])) {
    $id_cautela = $_POST['id_cautela'];

    $busca = $pdoCarga->prepare("SELECT c.*, m.material FROM cautelas c JOIN pel01 m ON c.material_id = m.id WHERE c.id = :id");
    $busca->execute(['id' => $id_cautela]);
    $itemCautela = $busca->fetch(PDO::FETCH_ASSOC);

    if ($itemCautela) {
        try {
            $pdoCarga->beginTransaction();
            $qtd = $itemCautela['quantidade'];
            $mat_id = $itemCautela['material_id'];

            // Salva no Histórico
            $sqlHist = "INSERT INTO historico (material, responsavel, quantidade, data_retirada, data_devolucao) 
                        VALUES (:mat, :resp, :qtd, :data_ret, NOW())";
            $stmtHist = $pdoCarga->prepare($sqlHist);
            $stmtHist->execute([
                'mat' => $itemCautela['material'],
                'resp' => $itemCautela['responsavel'],
                'qtd' => $qtd,
                'data_ret' => $itemCautela['data_retirada']
            ]);

            // Atualiza Estoque
            $sqlUp = "UPDATE pel01 SET disponivel = disponivel + :qtd, cautelado = cautelado - :qtd WHERE id = :id";
            $stmtUp = $pdoCarga->prepare($sqlUp);
            $stmtUp->execute(['qtd' => $qtd, 'id' => $mat_id]);

            // Deleta Cautela
            $sqlDel = "DELETE FROM cautelas WHERE id = :id";
            $stmtDel = $pdoCarga->prepare($sqlDel);
            $stmtDel->execute(['id' => $id_cautela]);

            $pdoCarga->commit();

            $_SESSION['temp_msg'] = "Material devolvido e arquivado no histórico!";
            header("Location: home.php");
            exit;

        } catch (Exception $e) {
            $pdoCarga->rollBack();
            $_SESSION['temp_erro'] = "Erro ao devolver: " . $e->getMessage();
            header("Location: home.php");
            exit;
        }
    }
}

// Buscas de dados
$materiais = $pdoCarga->query("SELECT * FROM pel01")->fetchAll(PDO::FETCH_ASSOC);
$sqlDetalhado = "SELECT c.id as id_cautela, c.responsavel, c.pg, c.nome_guerra, c.quantidade, c.data_retirada, m.material 
                 FROM cautelas c JOIN pel01 m ON c.material_id = m.id ORDER BY c.data_retirada DESC";
$listaCautelados = $pdoCarga->query($sqlDetalhado)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Siscarga PELC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(90deg, #1e3c72 0%, #2a5298 100%); }
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-header { background-color: white; border-bottom: 2px solid #f0f2f5; font-weight: bold; color: #444; }
        .table thead th { background-color: #343a40; color: white; border: none; }
        .badge-status { font-size: 0.9em; padding: 5px 10px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark navbar-expand-lg mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><i class="fas fa-shield-alt me-2"></i>SISCARGA PELC</a>
            <div class="d-flex align-items-center text-white">
                <a href="admin.php" class="btn btn-outline-warning btn-sm me-3"><i class="fas fa-cog"></i> Admin</a>
                <span class="me-3 d-none d-md-block"><i class="fas fa-user-circle me-1"></i> Olá, <?= htmlspecialchars($_SESSION['usuario_logado']); ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </div>
    </nav>

    <div class="container">

        <?php if($mensagem): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if($erro): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?= $erro ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header py-3 text-primary">
                        <i class="fas fa-file-export me-2"></i> Nova Cautela (Preencha os dados do Militar)
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3 align-items-end">
                            
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-muted">Material</label>
                                <select name="material_id" class="form-select" required>
                                    <option value="" selected disabled>Selecione...</option>
                                    <?php foreach($materiais as $m): ?>
                                        <option value="<?= $m['id'] ?>"><?= $m['material'] ?> (Disp: <?= $m['disponivel'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label fw-bold text-muted">P/G</label>
                                <select name="pg" class="form-select" required>
                                    <option value="Cel">Cel</option>
                                    <option value="Ten Cel">Ten Cel</option>
                                    <option value="Maj">Maj</option>
                                    <option value="Cap">Cap</option>
                                    <option value="1º Ten">1º Ten</option>
                                    <option value="2º Ten">2º Ten</option>
                                    <option value="Asp">Asp</option>
                                    <option value="Subten">Subten</option>
                                    <option value="1º Sgt">1º Sgt</option>
                                    <option value="2º Sgt">2º Sgt</option>
                                    <option value="3º Sgt">3º Sgt</option>
                                    <option value="Cb">Cb</option>
                                    <option value="Sd">Sd</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold text-muted">Nome de Guerra</label>
                                <input type="text" name="nome_guerra" class="form-control" placeholder="Ex: Silva" required>
                            </div>

                             <div class="col-md-3">
                                <label class="form-label fw-bold text-muted">Telefone</label>
                                <input type="text" name="telefone" class="form-control" placeholder="(81) 9..." required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-muted">OM</label>
                                <input type="text" name="om" class="form-control" value="4º B COM GE" required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-bold text-muted">Qtd</label>
                                <input type="number" name="quantidade" class="form-control" min="1" value="1" required>
                            </div>

                            <div class="col-md-6">
                                <button type="submit" name="abrir_cautela" class="btn btn-primary w-100">
                                    <i class="fas fa-check"></i> Confirmar Cautela
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="card h-100 border-start border-4 border-warning">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white">
                        <span class="text-warning-emphasis"><i class="fas fa-clipboard-list me-2"></i> Cautelas Ativas</span>
                        <span class="badge bg-warning text-dark"><?= count($listaCautelados) ?> Pendentes</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Material</th>
                                        <th>Responsável</th>
                                        <th>Qtd</th>
                                        <th class="text-end">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($listaCautelados) > 0): ?>
                                        <?php foreach($listaCautelados as $c): ?>
                                        <tr>
                                            <td class="fw-bold text-secondary"><?= $c['material'] ?></td>
                                            <td><i class="fas fa-user-tag text-muted me-1 small"></i> <?= $c['responsavel'] ?></td>
                                            <td><span class="badge bg-secondary rounded-pill"><?= $c['quantidade'] ?></span></td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-2">
                                                    <a href="/projeto_pelc/gerar_cautela_individual.php?id=<?= $c['id_cautela'] ?>" target="_blank" class="btn btn-outline-danger btn-sm" title="PDF">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="id_cautela" value="<?= $c['id_cautela'] ?>">
                                                        <button type="submit" name="devolver_item" class="btn btn-outline-success btn-sm" title="Devolver">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                <i class="fas fa-check-double fa-2x mb-2"></i><br>Tudo limpo!
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100 border-start border-4 border-success">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white">
                        <span class="text-success-emphasis"><i class="fas fa-boxes me-2"></i> Estoque Geral</span>
                        <a href="/projeto_pelc/gerar_pdf.php" target="_blank" class="btn btn-sm btn-dark">
                            <i class="fas fa-file-pdf me-1"></i> Relatório
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 text-center" style="font-size: 0.9rem;">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Mat</th>
                                        <th>Tot</th>
                                        <th>Cau</th>
                                        <th>Disp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $stmt = $pdoCarga->query("SELECT * FROM pel01");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
                                        $classeDisp = ($row['disponivel'] == 0) ? 'bg-danger text-white' : '';
                                    ?>
                                    <tr>
                                        <td class="text-start ps-2"><?= $row['material'] ?></td>
                                        <td><?= $row['quantidade_total'] ?></td>
                                        <td class="text-warning fw-bold"><?= $row['cautelado'] ?></td>
                                        <td class="<?= $classeDisp ?> fw-bold"><?= $row['disponivel'] ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>