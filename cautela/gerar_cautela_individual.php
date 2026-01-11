<?php
require('fpdf.php');
require('db.php');

if (!isset($_SESSION['usuario_logado'])) {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("ID da cautela não fornecido.");
}

$id_cautela = $_GET['id'];

// Busca os dados da cautela
$sql = "SELECT c.*, m.material 
        FROM cautelas c 
        JOIN pel01 m ON c.material_id = m.id 
        WHERE c.id = :id";
$stmt = $pdoCarga->prepare($sql);
$stmt->execute(['id' => $id_cautela]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dados) {
    die("Cautela não encontrada.");
}

class PDF extends FPDF
{
    function Header()
    {
        if(file_exists('brasao.png')) {
            $this->Image('brasao.png', 95, 10, 20);
        }
        
        $this->SetFont('Arial', 'B', 12);
        $this->Ln(25); 
        $this->Cell(0, 5, utf8_decode('EXÉRCITO BRASILEIRO'), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('7ª DIVISÃO DE EXÉRCITO'), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('4º BATALHÃO DE COMUNICAÇÕES E GUERRA ELETRÔNICA'), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('BATALHÃO ARRAIAL NOVO DO BOM JESUS'), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('(4° B Com Ex/1946)'), 0, 1, 'C');
        $this->Ln(15);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Siscarga PELC - Pagina '.$this->PageNo().'/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// TÍTULO
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, utf8_decode('DOCUMENTO DE CAUTELA EXTERNA DE MATERIAL'), 0, 1, 'C');
$pdf->Ln(5);

// --- TABELA ---
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(220, 220, 220); 

$w = array(15, 115, 30, 30); 
$pdf->Cell($w[0], 10, 'QTD', 1, 0, 'C', true);
$pdf->Cell($w[1], 10, 'MATERIAL', 1, 0, 'C', true);
$pdf->Cell($w[2], 10, utf8_decode('PREÇO UNID'), 1, 0, 'C', true);
$pdf->Cell($w[3], 10, 'OBS', 1, 0, 'C', true);
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
$altura_linha = 8;
$pdf->Cell($w[0], $altura_linha, str_pad($dados['quantidade'], 2, '0', STR_PAD_LEFT), 1, 0, 'C');
$pdf->Cell($w[1], $altura_linha, utf8_decode($dados['material']), 1, 0, 'L');
$pdf->Cell($w[2], $altura_linha, '', 1, 0, 'C');
$pdf->Cell($w[3], $altura_linha, '', 1, 0, 'C');
$pdf->Ln(20); 

// --- DADOS DO MILITAR ---
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 10, utf8_decode('DADOS DO MILITAR QUE CAUTELA:'), 0, 1, 'C');
$pdf->Ln(5);

$h_line = 8;

// NOME: AQUI ESTÁ A MUDANÇA (Apenas Nome de Guerra)
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(15, $h_line, 'NOME:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 11);
// Se tiver nome_guerra, usa ele. Se for antigo, usa o responsável completo.
$apenas_nome = (isset($dados['nome_guerra']) && !empty($dados['nome_guerra'])) ? $dados['nome_guerra'] : $dados['responsavel'];
$pdf->Cell(175, $h_line, utf8_decode($apenas_nome), 'B', 1, 'L');

// P/G
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(12, $h_line, 'P/G:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 11);
$pg_show = isset($dados['pg']) ? $dados['pg'] : '';
$pdf->Cell(178, $h_line, utf8_decode($pg_show), 'B', 1, 'L');

// TELEFONE
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(25, $h_line, 'TELEFONE:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 11);
$tel_show = isset($dados['telefone']) ? $dados['telefone'] : '';
$pdf->Cell(165, $h_line, utf8_decode($tel_show), 'B', 1, 'L');

// OM
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(10, $h_line, 'OM:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 11);
$om_show = isset($dados['om']) ? $dados['om'] : '4º B COM GE';
$pdf->Cell(180, $h_line, utf8_decode($om_show), 'B', 1, 'L');

// DATAS
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(40, $h_line, 'DATA DA CAUTELA:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(150, $h_line, date('d/m/Y', strtotime($dados['data_retirada'])), 'B', 1, 'L');

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(75, $h_line, utf8_decode('DATA PREVISTA PARA DEVOLUÇÃO:'), 0, 0, 'L');
$pdf->Cell(115, $h_line, '', 'B', 1, 'L');

$pdf->Ln(20);

// DATA E ASSINATURAS
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
$meses = [1=>'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$data_extenso = "Recife-PE, " . date('d') . " de " . $meses[(int)date('m')] . " de " . date('Y');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 10, utf8_decode($data_extenso), 0, 1, 'C');

$pdf->Ln(15);

// Assinatura (Militar que cautela)
// Aqui usamos o nome completo (PG + Nome) para ficar formal na assinatura
$nome_para_assinatura = (isset($dados['nome_guerra']) && !empty($dados['nome_guerra'])) ? $dados['pg'] . " " . $dados['nome_guerra'] : $dados['responsavel'];

$pdf->Cell(0, 5, '__________________________________________________', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 5, utf8_decode($nome_para_assinatura), 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, 'Militar que cautela', 0, 1, 'C');

$pdf->Ln(15);
$pdf->Cell(0, 5, '__________________________________________________', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 5, 'Oficial / Sgt Carga', 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, utf8_decode('Responsável pela Reserva'), 0, 1, 'C');

$pdf->Output();
?>