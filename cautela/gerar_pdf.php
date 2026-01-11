<?php
require('fpdf.php');
require('db.php');

// Verifica login
if (!isset($_SESSION['usuario_logado'])) {
    header("Location: index.php");
    exit;
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
        $this->Cell(0, 5, utf8_decode('PRONTO RESERVA DE MATERIAL PELOTÃO INFOR/C2/GE'), 0, 1, 'C');
        
        $this->Ln(5);
        $this->SetFont('Arial', '', 10);
        
        setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
        $meses = [1=>'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $data = "RECIFE-PE, " . date('d') . " de " . $meses[(int)date('m')] . " de " . date('Y');
        
        $this->Cell(0, 5, utf8_decode(strtoupper($data)), 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina '.$this->PageNo().'/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

$w = array(60, 25, 25, 25, 25, 25); 

$pdf->SetFillColor(200, 200, 200);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell($w[0], 7, 'MATERIAL', 1, 0, 'C', true);
$pdf->Cell($w[1], 7, 'QTD TOTAL', 1, 0, 'C', true);
$pdf->Cell($w[2], 7, 'NO PEL', 1, 0, 'C', true);
$pdf->Cell($w[3], 7, 'CAUTELADO', 1, 0, 'C', true);
$pdf->Cell($w[4], 7, 'DISP', 1, 0, 'C', true);
$pdf->Cell($w[5], 7, 'BAIXADOS', 1, 0, 'C', true);
$pdf->Ln();

$pdf->SetFont('Arial', '', 9);
$stmt = $pdoCarga->query("SELECT * FROM pel01");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $pdf->Cell($w[0], 6, utf8_decode($row['material']), 1);
    $pdf->Cell($w[1], 6, $row['quantidade_total'], 1, 0, 'C');
    $pdf->Cell($w[2], 6, $row['na_reserva'], 1, 0, 'C');
    $pdf->Cell($w[3], 6, $row['cautelado'], 1, 0, 'C');
    $pdf->Cell($w[4], 6, $row['disponivel'], 1, 0, 'C');
    $pdf->Cell($w[5], 6, $row['indisponivel'], 1, 0, 'C');
    $pdf->Ln();
}

// --- ÁREA DE ASSINATURAS CORRIGIDA ---
$pdf->Ln(20);
$y = $pdf->GetY();
$pdf->Line(20, $y, 80, $y);
$pdf->Line(90, $y, 130, $y);
$pdf->Line(140, $y, 190, $y);

// Correção aqui: De XY para SetXY
$pdf->SetXY(20, $y+2);
$pdf->Cell(60, 5, 'Oficial Conferente', 0, 0, 'C');

$pdf->SetXY(90, $y+2);
$pdf->Cell(40, 5, 'Lacre Reserva', 0, 0, 'C');

$pdf->SetXY(140, $y+2);
$pdf->Cell(50, 5, 'Cmt Cia', 0, 0, 'C');

$pdf->Ln(20);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, utf8_decode('DETALHAMENTO DE MATERIAIS CAUTELADOS'), 0, 1, 'L');

$w2 = array(70, 70, 20, 30);
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell($w2[0], 7, 'MATERIAL', 1, 0, 'C', true);
$pdf->Cell($w2[1], 7, utf8_decode('RESPONSÁVEL'), 1, 0, 'C', true);
$pdf->Cell($w2[2], 7, 'QTD', 1, 0, 'C', true);
$pdf->Cell($w2[3], 7, 'DATA', 1, 0, 'C', true);
$pdf->Ln();

$sqlDetalhado = "SELECT c.responsavel, c.quantidade, c.data_retirada, m.material 
                 FROM cautelas c 
                 JOIN pel01 m ON c.material_id = m.id 
                 ORDER BY m.material";
$stmtDet = $pdoCarga->query($sqlDetalhado);

$pdf->SetFont('Arial', '', 9);
if ($stmtDet->rowCount() > 0) {
    while ($c = $stmtDet->fetch(PDO::FETCH_ASSOC)) {
        $pdf->Cell($w2[0], 6, utf8_decode($c['material']), 1);
        $pdf->Cell($w2[1], 6, utf8_decode($c['responsavel']), 1);
        $pdf->Cell($w2[2], 6, $c['quantidade'], 1, 0, 'C');
        $pdf->Cell($w2[3], 6, date('d/m/Y', strtotime($c['data_retirada'])), 1, 0, 'C');
        $pdf->Ln();
    }
} else {
    $pdf->Cell(array_sum($w2), 10, utf8_decode('Não há.'), 1, 0, 'C');
}

$pdf->Output();
?>