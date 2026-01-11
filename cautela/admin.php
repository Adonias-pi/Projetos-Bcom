<?php
require 'db.php';

// --- ALTERAÇÃO DE SEGURANÇA ---
// Verifica se a sessão específica de ADMIN existe.
// Se não existir, manda para o login de admin (admin_login.php)
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: admin_login.php");
    exit;
}
// ------------------------------

// Inicializa mensagens
$mensagem = "";
$erro = "";


// ---------------- LÓGICA DE BANCO DE DADOS ----------------

// 1. ADICIONAR NOVO MATERIAL
if (isset($_POST['add_material'])) {
    $nome = trim($_POST['nome_material']);
    $total = (int)$_POST['qtd_total'];
    
    // Ao criar, Disponível = Total (pois ninguém cautelou ainda e indisponível é 0)
    if (!empty($nome) && $total > 0) {
        $stmt = $pdoCarga->prepare("INSERT INTO pel01 (material, quantidade_total, disponivel, cautelado, indisponivel) VALUES (:nome, :total, :total, 0, 0)");
        if ($stmt->execute(['nome' => $nome, 'total' => $total])) {
            $_SESSION['msg_admin'] = "Material <b>$nome</b> criado com sucesso!";
            header("Location: admin.php");
            exit;
        }
    } else {
        $erro = "Preencha o nome e uma quantidade válida.";
    }
}

// 2. EDITAR MATERIAL (ALTERAR TOTAL OU INDISPONÍVEL)
if (isset($_POST['edit_material'])) {
    $id = $_POST['edit_id'];
    $novo_total = (int)$_POST['edit_total'];
    $novo_indisponivel = (int)$_POST['edit_indisponivel'];
    $nome_edit = $_POST['edit_nome'];

    // Busca dados atuais para pegar o CAUTELADO (que não muda por aqui)
    $busca = $pdoCarga->prepare("SELECT cautelado FROM pel01 WHERE id = :id");
    $busca->execute(['id' => $id]);
    $atual = $busca->fetch(PDO::FETCH_ASSOC);

    if ($atual) {
        $cautelado = $atual['cautelado'];
        
        // MATEMÁTICA: Disponível = Total - (O que está na rua + O que quebrou)
        $novo_disponivel = $novo_total - ($cautelado + $novo_indisponivel);

        if ($novo_disponivel < 0) {
            $erro = "Erro: A conta não fecha! Você tentou reduzir o total para um número menor do que já está cautelado/indisponível.";
        } else {
            $sql = "UPDATE pel01 SET material = :nome, quantidade_total = :total, indisponivel = :indisp, disponivel = :disp WHERE id = :id";
            $stmt = $pdoCarga->prepare($sql);
            $stmt->execute([
                'nome' => $nome_edit,
                'total' => $novo_total,
                'indisp' => $novo_indisponivel,
                'disp' => $novo_disponivel,
                'id' => $id
            ]);
            $_SESSION['msg_admin'] = "Material atualizado com sucesso!";
            header("Location: admin.php");
            exit;
        }
    }
}

// 3. DELETAR MATERIAL
if (isset($_POST['delete_material'])) {
    $id = $_POST['delete_id'];
    
    // Verifica se tem item cautelado antes de apagar
    $check = $pdoCarga->prepare("SELECT cautelado FROM pel01 WHERE id = :id");
    $check->execute(['id' => $id]);
    $item = $check->fetch(PDO::FETCH_ASSOC);

    if ($item['cautelado'] > 0) {
        $erro = "Não é possível apagar este item pois existem unidades cauteladas (na rua)! Recolha primeiro.";
    } else {
        $pdoCarga->prepare("DELETE FROM pel01 WHERE id = :id")->execute(['id' => $id]);
        $_SESSION['msg_admin'] = "Item removido do sistema.";
        header("Location: admin.php");
        exit;
    }
}

// Captura mensagem da sessão (PRG)
if (isset($_SESSION['msg_admin'])) {
    $mensagem = $_SESSION['msg_admin'];
    unset($_SESSION['msg_admin']);
}

$materiais = $pdoCarga->query("SELECT * FROM pel01 ORDER BY material")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Administração - Siscarga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(90deg, #2c3e50 0%, #4ca1af 100%); } /* Cor diferente para diferenciar da Home */
        .btn-action { width: 35px; height: 35px; padding: 0; line-height: 35px; border-radius: 50%; text-align: center; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark navbar-expand-lg mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><i class="fas fa-cogs me-2"></i>ADMINISTRAÇÃO</a>
            <div class="d-flex">
                <a href="home.php" class="btn btn-outline-light btn-sm me-2"><i class="fas fa-home"></i> Voltar para Home</a>
                <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </div>
    </nav>

    <div class="container">
        
        <?php if($mensagem) echo "<div class='alert alert-success'>$mensagem</div>"; ?>
        <?php if($erro) echo "<div class='alert alert-danger'>$erro</div>"; ?>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="m-0 text-secondary fw-bold">Gerenciar Materiais</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus"></i> Novo Item
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Material</th>
                                <th class="text-center">Total</th>
                                <th class="text-center text-warning">Cautelado</th>
                                <th class="text-center text-danger">Indisponível</th>
                                <th class="text-center text-success">Disponível</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($materiais as $m): ?>
                            <tr>
                                <td class="fw-bold"><?= $m['material'] ?></td>
                                <td class="text-center"><?= $m['quantidade_total'] ?></td>
                                <td class="text-center"><?= $m['cautelado'] ?></td>
                                <td class="text-center"><?= $m['indisponivel'] ?></td>
                                <td class="text-center fw-bold"><?= $m['disponivel'] ?></td>
                                <td class="text-end">
                                    <button class="btn btn-warning btn-sm btn-action text-white" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal"
                                            data-id="<?= $m['id'] ?>"
                                            data-nome="<?= $m['material'] ?>"
                                            data-total="<?= $m['quantidade_total'] ?>"
                                            data-indisp="<?= $m['indisponivel'] ?>">
                                        <i class="fas fa-pen"></i>
                                    </button>

                                    <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja apagar este item permanentemente?');">
                                        <input type="hidden" name="delete_id" value="<?= $m['id'] ?>">
                                        <button type="submit" name="delete_material" class="btn btn-danger btn-sm btn-action">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Novo Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <label>Nome do Material</label>
                        <input type="text" name="nome_material" class="form-control mb-3" required>
                        <label>Quantidade Total Inicial</label>
                        <input type="number" name="qtd_total" class="form-control" min="1" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="add_material" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="edit_id" id="edit_id">
                        
                        <label>Nome do Material</label>
                        <input type="text" name="edit_nome" id="edit_nome" class="form-control mb-3" required>
                        
                        <div class="row">
                            <div class="col-6">
                                <label>Qtd Total</label>
                                <input type="number" name="edit_total" id="edit_total" class="form-control" required>
                                <small class="text-muted">Inventário completo</small>
                            </div>
                            <div class="col-6">
                                <label>Qtd Indisponível</label>
                                <input type="number" name="edit_indisponivel" id="edit_indisponivel" class="form-control" required>
                                <small class="text-muted">Quebrado/Baixado</small>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3 py-2 small">
                            <i class="fas fa-info-circle"></i> O campo <b>Disponível</b> será recalculado automaticamente.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="edit_material" class="btn btn-warning text-white">Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para preencher o Modal de Edição com os dados da linha clicada
        var editModal = document.getElementById('editModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            
            var id = button.getAttribute('data-id');
            var nome = button.getAttribute('data-nome');
            var total = button.getAttribute('data-total');
            var indisp = button.getAttribute('data-indisp');

            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nome').value = nome;
            document.getElementById('edit_total').value = total;
            document.getElementById('edit_indisponivel').value = indisp;
        });
    </script>
</body>
</html>