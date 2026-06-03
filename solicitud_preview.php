<?php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/vendor/autoload.php';

requireLogin();

// ─── Datos de prueba ────────────────────────────────────────────────────────
$folio     = '0017';
$clienteId = 'CS0022';
$fecha     = '14/05/2025';
$logoPath  = __DIR__ . '/assets/img/logo-website-transparente.png';

$d = [
    // Crédito
    'monto'              => '$450,000.00',
    'plazo'              => '24',   // '12','18','24','36'

    // Datos personales
    'nombres'            => 'CARLOS ALBERTO',
    'apellido_pat'       => 'RAMÍREZ',
    'apellido_mat'       => 'FUENTES',
    'nac_dia'            => '08',
    'nac_mes'            => '03',
    'nac_anio'           => '1988',
    'pais_nac'           => 'MÉXICO',
    'nacionalidad'       => 'MEXICANA',
    'sexo'               => 'M',   // 'F' o 'M'
    'entidad_nac'        => 'CIUDAD DE MÉXICO',
    'rfc'                => 'RAFC880308HDF',
    'curp'               => 'RAFC880308HDFMRR09',
    'ocupacion'          => 'INGENIERO EN SISTEMAS',
    'tel_casa'           => '55 1234 5678',
    'tel_cel'            => '55 9876 5432',
    'email'              => 'c.ramirez@correo.com',
    'tipo_id'            => 'INE / IFE',
    'folio_id'           => 'IDMEX2023000456789',
    'estado_civil'       => 'Casado (a)',  // Soltero/Unión Libre/Divorciado/Viudo/Casado
    'regimen_mat'        => 'Separación de Bienes',  // Sociedad Conyugal / Separación de Bienes

    // Domicilio actual
    'dom_tipo'           => 'Propia',  // Propia/Rentada/Hipotecada/De familiares
    'dom_calle'          => 'Av. Insurgentes Sur 1457 Int. 302',
    'dom_colonia'        => 'Insurgentes Mixcoac',
    'dom_delegacion'     => 'Benito Juárez',
    'dom_ciudad'         => 'Ciudad de México',
    'dom_entidad'        => 'Ciudad de México',
    'dom_pais'           => 'México',
    'dom_cp'             => '03920',
    'dom_anios'          => '5',
    // Domicilio anterior (vacío = menos de 1 año, se muestra el bloque)
    'dom_ant_calle'      => '',
    'dom_ant_colonia'    => '',
    'dom_ant_delegacion' => '',
    'dom_ant_ciudad'     => '',
    'dom_ant_entidad'    => '',
    'dom_ant_pais'       => '',
    'dom_ant_cp'         => '',
    'dom_ant_anios'      => '',

    // Empleo actual
    'regimen_emp'        => 'Asalariado',  // Asalariado/Honorarios/Actividad Empresarial
    'actividad_giro'     => 'TECNOLOGÍA DE LA INFORMACIÓN',
    'emp_ing_mes'        => '03',
    'emp_ing_anio'       => '2018',
    'sueldo'             => '$28,500.00',
    'otros_ingresos'     => 'NO',  // SI / NO
    'fuente_otros'       => '',
    'empresa'            => 'GRUPO TECNOLÓGICO AVANZADO S.A. DE C.V.',
    'ocup_empresa'       => 'INGENIERO SR.',
    'puesto'             => 'LÍDER DE PROYECTO',
    'jefe_nombre'        => 'ING. MARCO ANTONIO VILLA PÉREZ / DIRECTOR DE TI',
    'jefe_tel_of'        => '55 4000 1200',
    'jefe_tel_otro'      => '',
    'dom_laboral'        => 'Blvd. Manuel Ávila Camacho 36, Col. Lomas de Chapultepec, Alcaldía Miguel Hidalgo, CDMX, 11000',
    // Empleo anterior
    'emp_ant_ing_mes'    => '06',
    'emp_ant_ing_anio'   => '2014',
    'emp_ant_sueldo'     => '$18,000.00',
    'emp_ant_otros'      => 'NO',  // SI/NO/N/A
    'emp_ant_fuente'     => '',
    'emp_ant_empresa'    => 'SOLUCIONES DIGITALES DEL NORTE S.C.',
    'emp_ant_ocup'       => 'DESARROLLADOR JR.',
    'emp_ant_puesto'     => 'ANALISTA DE SISTEMAS',

    // Pág. 2 — continuación empleo anterior
    'emp_ant_jefe'       => 'LIC. PATRICIA MORALES SOTO / GERENTE GENERAL',
    'emp_ant_tel_of'     => '81 8765 4321',
    'emp_ant_tel_otro'   => '',
    'emp_ant_dom_lab'    => 'Av. Constitución 100 Nte., Col. Centro, Monterrey, N.L., 64000',

    // PEP
    'pep1'               => 'NO',  // NO / SI
    'pep1_desc'          => '',
    'pep2'               => 'NO',  // NO / SI
    'pep2_nombre'        => '',
    'actua_nombre_propio'=> 'Si',  // Si / No

    // Tercero (vacío = no aplica)
    'tercero_nombres'    => '',
    'tercero_ap_pat'     => '',
    'tercero_ap_mat'     => '',

    // Referencias personales
    'refs'               => [
        ['nombre' => 'GABRIELA SOTO RÍOS',    'tel' => '55 2233 4455', 'horario' => '9am - 6pm', 'parentesco' => 'Hermana'],
        ['nombre' => 'ROBERTO LUNA HERRERA',  'tel' => '55 6677 8899', 'horario' => '10am - 8pm', 'parentesco' => 'Amigo'],
        ['nombre' => 'FERNANDA CRUZ VARGAS',  'tel' => '55 1122 3344', 'horario' => '8am - 5pm',  'parentesco' => 'Compañero trabajo'],
    ],
    'mercado_si'         => false,
    'llamadas_si'        => false,

    // Firma electrónica
    'fea'                => 'NO',  // SI / NO
    'fea_cert'           => '',
    'fea_nombre_decl'    => 'CARLOS ALBERTO RAMÍREZ FUENTES',
];

// ─── Constantes de layout ───────────────────────────────────────────────────
$ml = 10;   // margin left
$mr = 10;   // margin right
$cw = 190;  // content width (210 - 10 - 10)
$fw = 190 / 3; // field width para 3 columnas ≈ 63.33mm

// ─── Setup TCPDF ────────────────────────────────────────────────────────────
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Wiser Financiera');
$pdf->SetAuthor('Wiser Financiera');
$pdf->SetTitle('Solicitud de Crédito');
$pdf->SetMargins($ml, 10, $mr);
$pdf->SetAutoPageBreak(false);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// ─── Helpers ────────────────────────────────────────────────────────────────

function pdfHeader($pdf, $ml, $cw, $logoPath, $folio, $clienteId, $fecha)
{
    $y = 10;
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, $ml, $y, 48, 14, 'PNG');
    }
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(0, 70, 127);
    $pdf->SetXY($ml, $y);
    $pdf->Cell($cw, 5, 'SOLICITUD DE CRÉDITO', 0, 0, 'R');

    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    foreach ([[$y + 5, 'Folio:' . $folio], [$y + 9, 'Cliente:' . $clienteId], [$y + 13, 'Fecha:' . $fecha]] as [$ry, $txt]) {
        $pdf->SetXY($ml, $ry);
        $pdf->Cell($cw, 4, $txt, 0, 0, 'R');
    }

    // Línea azul separadora (debajo del bloque de cabecera completo)
    $pdf->SetDrawColor(0, 70, 127);
    $pdf->SetLineWidth(0.35);
    $pdf->Line($ml, 29, $ml + $cw, 29);
    $pdf->SetLineWidth(0.2);
    $pdf->SetDrawColor(0, 0, 0);
}

function secHeader($pdf, $ml, $y, $cw, $text)
{
    $pdf->SetFillColor(221, 235, 247);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->SetXY($ml, $y);
    $pdf->Cell($cw, 5.5, '  ' . $text, 0, 0, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    return $y + 5.5;
}

// Campo etiquetado: 3mm etiqueta + 5mm valor con borde inferior = 8mm total
function campo($pdf, $x, $y, $w, $label, $val = '')
{
    $pdf->SetFont('helvetica', '', 5.8);
    $pdf->SetTextColor(90, 90, 90);
    $pdf->SetXY($x, $y);
    $pdf->Cell($w, 3, $label, 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY($x, $y + 3);
    $pdf->Cell($w - 0.5, 5, $val, 'LBR', 0, 'L');
}

// Checkbox cuadrado con etiqueta
function cb($pdf, $x, $y, $label, $checked = false)
{
    $sz = 2.8;
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetFillColor($checked ? 0 : 255, $checked ? 70 : 255, $checked ? 127 : 255);
    $pdf->Rect($x, $y + 0.6, $sz, $sz, $checked ? 'DF' : 'D');
    $pdf->SetXY($x + $sz + 0.8, $y);
    $pdf->SetFont('helvetica', '', 6.5);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(22, 4.5, $label, 0, 0, 'L');
}

function footerPDF($pdf, $ml, $cw)
{
    $pdf->SetFont('helvetica', '', 6.5);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->SetXY($ml, 286);
    $pdf->Cell($cw, 4, 'RECA :16470-439-041284/01-02968-1024', 0, 0, 'C');
}

function notaTexto($pdf, $ml, $y, $cw, $texto)
{
    $pdf->SetFont('helvetica', '', 6);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->SetXY($ml, $y);
    $pdf->MultiCell($cw, 3.2, $texto, 0, 'L');
    $pdf->SetTextColor(0, 0, 0);
}

// ═══════════════════════════════════════════════════════════════════════════
//  PÁGINA 1
// ═══════════════════════════════════════════════════════════════════════════
$pdf->AddPage();
pdfHeader($pdf, $ml, $cw, $logoPath, $folio, $clienteId, $fecha);
$y = 31;

// ── INFORMACIÓN DEL CRÉDITO ──────────────────────────────────────────────
$y = secHeader($pdf, $ml, $y, $cw, 'INFORMACIÓN DEL CRÉDITO');

campo($pdf, $ml, $y, 78, 'Monto solicitado', $d['monto']);

$pdf->SetFont('helvetica', '', 5.8);
$pdf->SetTextColor(90, 90, 90);
$pdf->SetXY($ml + 80, $y);
$pdf->Cell($cw - 80, 3, 'Plazo del Crédito (Meses)', 0, 0, 'L');

$cbYcred = $y + 3.5;
foreach (['12', '18', '24', '36'] as $i => $opt) {
    cb($pdf, $ml + 80 + $i * 22, $cbYcred, $opt, $d['plazo'] === $opt);
}
$pdf->SetFont('helvetica', '', 6.5);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($ml + 170, $cbYcred);
$pdf->Cell(8, 4.5, 'Otro:', 0, 0, 'L');
$otroVal = in_array($d['plazo'], ['12','18','24','36']) ? '' : $d['plazo'];
$pdf->SetXY($ml + 178, $cbYcred + 0.5);
$pdf->Cell(12, 4, $otroVal, 'B', 0, 'L');

$y += 9;
$y += 1.5;

// ── INFORMACIÓN DEL CLIENTE ──────────────────────────────────────────────
$y = secHeader($pdf, $ml, $y, $cw, 'INFORMACIÓN DEL CLIENTE');

// Fila 1: Nombre / Apellidos
campo($pdf, $ml,           $y, $fw - 1, 'Nombre(s) (sin abreviaturas)', $d['nombres']);
campo($pdf, $ml + $fw,     $y, $fw - 1, 'Apellido Paterno',             $d['apellido_pat']);
campo($pdf, $ml + $fw * 2, $y, $fw,     'Apellido Materno',             $d['apellido_mat']);
$y += 8;

// Fila 2: Fecha nacimiento / País / Nacionalidad / Sexo
$pdf->SetFont('helvetica', '', 5.8);
$pdf->SetTextColor(90, 90, 90);
$pdf->SetXY($ml, $y);       $pdf->Cell(35, 3, 'Fecha de Nacimiento', 0, 0, 'L');
$pdf->SetXY($ml + 40, $y);  $pdf->Cell(32, 3, 'País de Nacimiento',  0, 0, 'L');
$pdf->SetXY($ml + 78, $y);  $pdf->Cell(30, 3, 'Nacionalidad',        0, 0, 'L');

foreach ([[0, 'Día', $d['nac_dia']], [8, 'Mes', $d['nac_mes']], [17, 'Año', $d['nac_anio']]] as [$dx, $dlbl, $dval]) {
    $pdf->SetFont('helvetica', '', 5.5);
    $pdf->SetTextColor(90, 90, 90);
    $pdf->SetXY($ml + $dx, $y + 3);
    $pdf->Cell(7, 2.5, $dlbl, 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY($ml + $dx, $y + 5.5);
    $pdf->Cell(7, 4.5, $dval, 'B', 0, 'C');
}
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($ml + 40, $y + 3);  $pdf->Cell(32, 4.5, $d['pais_nac'],    'B', 0, 'L');
$pdf->SetXY($ml + 78, $y + 3);  $pdf->Cell(30, 4.5, $d['nacionalidad'], 'B', 0, 'L');

cb($pdf, $ml + 152, $y + 3, 'Femenino',  $d['sexo'] === 'F');
cb($pdf, $ml + 168, $y + 3, 'Masculino', $d['sexo'] === 'M');
$y += 10;

// Fila 3: Entidad federativa nacimiento
campo($pdf, $ml, $y, $cw * 0.55, 'Entidad federativa de nacimiento', $d['entidad_nac']);
$y += 8;

// Fila 4: RFC / CURP / Ocupación
campo($pdf, $ml,       $y, 56, 'RFC con homoclave', $d['rfc']);
campo($pdf, $ml + 58,  $y, 76, 'CURP',              $d['curp']);
campo($pdf, $ml + 136, $y, 54, 'Ocupación',         $d['ocupacion']);
$y += 8;

// Fila 5: Teléfonos / Email
campo($pdf, $ml,       $y, 56, 'Teléfono de Casa',  $d['tel_casa']);
campo($pdf, $ml + 58,  $y, 56, 'Teléfono celular',  $d['tel_cel']);
campo($pdf, $ml + 116, $y, 74, 'E-mail',            $d['email']);
$y += 8;

// Fila 6: Tipo ID / Folio
campo($pdf, $ml,      $y, 90, 'Tipo de Identificación',        $d['tipo_id']);
campo($pdf, $ml + 92, $y, 98, 'Folio o número de identificación', $d['folio_id']);
$y += 8;

// Fila 7: Estado Civil / Régimen
$pdf->SetFont('helvetica', '', 5.8);
$pdf->SetTextColor(90, 90, 90);
$pdf->SetXY($ml, $y);        $pdf->Cell(60, 3, 'Estado Civil',       0, 0, 'L');
$pdf->SetXY($ml + 135, $y);  $pdf->Cell(55, 3, 'Régimen Matrimonial:', 0, 0, 'R');
$pdf->SetTextColor(0, 0, 0);

$ecY = $y + 3.5;
$ecX = $ml;
foreach (['Soltero (a)', 'Unión Libre', 'Divorciado (a)', 'Viudo (a)', 'Casado (a)'] as $ec) {
    cb($pdf, $ecX, $ecY, $ec, $d['estado_civil'] === $ec);
    $ecX += strlen($ec) * 1.65 + 5;
}
cb($pdf, $ml + 142, $ecY,       'Sociedad Conyugal',   $d['regimen_mat'] === 'Sociedad Conyugal');
cb($pdf, $ml + 142, $ecY + 4.5, 'Separación de Bienes', $d['regimen_mat'] === 'Separación de Bienes');
$y += 12;
$y += 2;

// ── DOMICILIO ────────────────────────────────────────────────────────────
$y = secHeader($pdf, $ml, $y, $cw, 'DOMICILIO');

$pdf->SetFont('helvetica', '', 5.8);
$pdf->SetTextColor(90, 90, 90);
$pdf->SetXY($ml, $y);
$pdf->Cell(35, 3, 'Tipo de Propiedad', 0, 0, 'L');
$pdf->SetTextColor(0, 0, 0);

$cbdomY = $y + 3.5;
foreach (['Propia', 'Rentada', 'Hipotecada', 'De familiares'] as $i => $tp) {
    cb($pdf, $ml + $i * 35, $cbdomY, $tp, $d['dom_tipo'] === $tp);
}
$y += 9;

campo($pdf, $ml,       $y, 82, 'Domicilio (Calle y número exterior e interior)', $d['dom_calle']);
campo($pdf, $ml + 84,  $y, 60, 'Colonia/Urbanización',                           $d['dom_colonia']);
campo($pdf, $ml + 146, $y, 44, 'Delegación/Municipio / Demarcación política',    $d['dom_delegacion']);
$y += 8;

campo($pdf, $ml,       $y, 42, 'Ciudad / Población',         $d['dom_ciudad']);
campo($pdf, $ml + 44,  $y, 44, 'Entidad Federativa / Estado', $d['dom_entidad']);
campo($pdf, $ml + 90,  $y, 26, 'País',                        $d['dom_pais']);
campo($pdf, $ml + 118, $y, 20, 'C.P.',                        $d['dom_cp']);
campo($pdf, $ml + 140, $y, 50, 'Años de residencia',          $d['dom_anios']);
$y += 8;

notaTexto($pdf, $ml, $y, $cw,
    'En caso de tener menos de 1 año de residencia en el domicilio actual, por favor proporcione los datos de su domicilio anterior:');
$y += 4;

campo($pdf, $ml,       $y, 82, 'Domicilio (Calle y número exterior e interior)', $d['dom_ant_calle']);
campo($pdf, $ml + 84,  $y, 60, 'Colonia/Urbanización',                           $d['dom_ant_colonia']);
campo($pdf, $ml + 146, $y, 44, 'Delegación/Municipio / Demarcación política',    $d['dom_ant_delegacion']);
$y += 8;

campo($pdf, $ml,       $y, 42, 'Ciudad / Población',          $d['dom_ant_ciudad']);
campo($pdf, $ml + 44,  $y, 44, 'Entidad Federativa / Estado',  $d['dom_ant_entidad']);
campo($pdf, $ml + 90,  $y, 26, 'País',                         $d['dom_ant_pais']);
campo($pdf, $ml + 118, $y, 20, 'C.P.',                         $d['dom_ant_cp']);
campo($pdf, $ml + 140, $y, 50, 'Años de residencia',           $d['dom_ant_anios']);
$y += 8;
$y += 2;

// ── EMPLEO / OCUPACIÓN ───────────────────────────────────────────────────
$y = secHeader($pdf, $ml, $y, $cw, 'EMPLEO / OCUPACIÓN');

$pdf->SetFont('helvetica', '', 5.8);
$pdf->SetTextColor(90, 90, 90);
$pdf->SetXY($ml, $y);        $pdf->Cell(65, 3, 'Régimen', 0, 0, 'L');
$pdf->SetXY($ml + 75, $y);   $pdf->Cell($cw - 75, 3, 'Actividad o Giro del negocio al que se dedica:', 0, 0, 'L');
$pdf->SetTextColor(0, 0, 0);

$regY = $y + 3.5;
cb($pdf, $ml,      $regY, 'Asalariado',          $d['regimen_emp'] === 'Asalariado');
cb($pdf, $ml + 22, $regY, 'Honorarios',          $d['regimen_emp'] === 'Honorarios');
cb($pdf, $ml + 44, $regY, 'Actividad Empresarial', $d['regimen_emp'] === 'Actividad Empresarial');

$pdf->SetXY($ml + 75, $y + 3);
$pdf->Cell($cw - 75 - 0.5, 5, $d['actividad_giro'], 'B', 0, 'L');
$y += 9;

$pdf->SetFont('helvetica', '', 5.8);
$pdf->SetTextColor(90, 90, 90);
$pdf->SetXY($ml, $y);
$pdf->Cell(100, 3, 'Fecha de ingreso o inicio de actividad:', 0, 0, 'R');
foreach ([[102, 'Mes', $d['emp_ing_mes']], [120, 'Año', $d['emp_ing_anio']]] as [$dx, $lbl, $val]) {
    $pdf->SetXY($ml + $dx, $y);
    $pdf->Cell(14, 3, $lbl, 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY($ml + $dx, $y + 3);
    $pdf->Cell(14, 5, $val, 'B', 0, 'L');
    $pdf->SetFont('helvetica', '', 5.8);
    $pdf->SetTextColor(90, 90, 90);
}
$pdf->SetTextColor(0, 0, 0);
$y += 8;

campo($pdf, $ml, $y, 55, 'Sueldo o percepciones mensuales', $d['sueldo']);
$pdf->SetFont('helvetica', '', 5.8);
$pdf->SetTextColor(90, 90, 90);
$pdf->SetXY($ml + 57, $y);
$pdf->Cell(18, 3, 'Otros ingresos:', 0, 0, 'L');
$pdf->SetTextColor(0, 0, 0);
cb($pdf, $ml + 57, $y + 3.5, 'SI', $d['otros_ingresos'] === 'SI');
cb($pdf, $ml + 65, $y + 3.5, 'NO', $d['otros_ingresos'] === 'NO');
campo($pdf, $ml + 82, $y, $cw - 82, 'Fuente de otros ingresos:', $d['fuente_otros']);
$y += 8;

campo($pdf, $ml,       $y, 70, 'Empresa donde trabaja',                       $d['empresa']);
campo($pdf, $ml + 72,  $y, 60, 'Ocupación',                                   $d['ocup_empresa']);
campo($pdf, $ml + 134, $y, 56, 'Puesto que ocupa actualmente en la empresa',  $d['puesto']);
$y += 8;

campo($pdf, $ml,       $y, 90, 'Nombre y puesto del jefe inmediato', $d['jefe_nombre']);
campo($pdf, $ml + 92,  $y, 50, 'T e léfono oficina',                 $d['jefe_tel_of']);
campo($pdf, $ml + 144, $y, 46, 'Otro teléfono',                      $d['jefe_tel_otro']);
$y += 8;

campo($pdf, $ml, $y, $cw, 'Domicilio Laboral (Calle y número exterior e interior, Col., Ciudad, Edo. y C.P.)', $d['dom_laboral']);
$y += 8;

notaTexto($pdf, $ml, $y, $cw,
    'En caso de tener menos de 1 año de antigüedad en la empresa actual por favor proporcione los datos de su empleo anterior.');
$y += 5;

$pdf->SetFont('helvetica', '', 5.8);
$pdf->SetTextColor(90, 90, 90);
$pdf->SetXY($ml, $y);
$pdf->Cell(100, 3, 'Fecha de ingreso o inicio de actividad:', 0, 0, 'R');
foreach ([[102, 'Mes', $d['emp_ant_ing_mes']], [120, 'Año', $d['emp_ant_ing_anio']]] as [$dx, $lbl, $val]) {
    $pdf->SetXY($ml + $dx, $y);
    $pdf->Cell(14, 3, $lbl, 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY($ml + $dx, $y + 3);
    $pdf->Cell(14, 5, $val, 'B', 0, 'L');
    $pdf->SetFont('helvetica', '', 5.8);
    $pdf->SetTextColor(90, 90, 90);
}
$pdf->SetTextColor(0, 0, 0);
$y += 8;

campo($pdf, $ml, $y, 55, 'Sueldo o percepciones mensuales', $d['emp_ant_sueldo']);
$pdf->SetFont('helvetica', '', 5.8);
$pdf->SetTextColor(90, 90, 90);
$pdf->SetXY($ml + 57, $y);
$pdf->Cell(18, 3, 'Otros ingresos:', 0, 0, 'L');
$pdf->SetTextColor(0, 0, 0);
cb($pdf, $ml + 57, $y + 3.5, 'SI',  $d['emp_ant_otros'] === 'SI');
cb($pdf, $ml + 65, $y + 3.5, 'NO',  $d['emp_ant_otros'] === 'NO');
cb($pdf, $ml + 73, $y + 3.5, 'N/A', $d['emp_ant_otros'] === 'N/A');
campo($pdf, $ml + 82, $y, $cw - 82, 'Fuente de otros ingresos:', $d['emp_ant_fuente']);
$y += 8;

campo($pdf, $ml,       $y, 70, 'Empresa donde trabaja',                      $d['emp_ant_empresa']);
campo($pdf, $ml + 72,  $y, 60, 'Ocupación',                                  $d['emp_ant_ocup']);
campo($pdf, $ml + 134, $y, 56, 'Puesto que ocupa actualmente en la empresa', $d['emp_ant_puesto']);

$pdf->SetFont('helvetica', '', 5.8);
$pdf->SetTextColor(90, 90, 90);
$pdf->SetXY($ml, 279);
$pdf->Cell($cw, 3, 'Delegación/Municipio / Demarcación política', 0, 0, 'R');

footerPDF($pdf, $ml, $cw);

// ═══════════════════════════════════════════════════════════════════════════
//  PÁGINA 2
// ═══════════════════════════════════════════════════════════════════════════
$pdf->AddPage();
pdfHeader($pdf, $ml, $cw, $logoPath, $folio, $clienteId, $fecha);
$y = 31;

campo($pdf, $ml,       $y, 90, 'Nombre y puesto del jefe inmediato', $d['emp_ant_jefe']);
campo($pdf, $ml + 92,  $y, 50, 'Teléfono oficina',                  $d['emp_ant_tel_of']);
campo($pdf, $ml + 144, $y, 46, 'Otro teléfono',                     $d['emp_ant_tel_otro']);
$y += 8;

campo($pdf, $ml, $y, $cw, 'Domicilio Laboral (Calle y número exterior e interior, Col., Ciudad, Edo. y C.P.)', $d['emp_ant_dom_lab']);
$y += 8;
$y += 2;

// ── Pregunta PEP 1 ───────────────────────────────────────────────────────
$pdf->SetFont('helvetica', '', 6.5);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($ml, $y);
$pdf->MultiCell($cw - 40, 3.5,
    '¿Desempeña o ha desempeñado un cargo público o político en territorio nacional o en un país extranjero?', 0, 'L');
cb($pdf, $ml,      $y + 7.5, 'NO', $d['pep1'] === 'NO');
cb($pdf, $ml + 12, $y + 7.5, 'SI', $d['pep1'] === 'SI');
$pdf->SetFont('helvetica', '', 5.8);
$pdf->SetTextColor(90, 90, 90);
$pdf->SetXY($ml + 27, $y + 7.5);
$pdf->Cell(20, 3, 'Describa cuál:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 7.5);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($ml + 48, $y + 11);
$pdf->Cell($cw - 48, 4, $d['pep1_desc'], 'B', 0, 'L');
$y += 18;

// ── Pregunta PEP 2 ───────────────────────────────────────────────────────
$pdf->SetFont('helvetica', '', 6.5);
$pdf->SetXY($ml, $y);
$pdf->MultiCell($cw - 40, 3.5,
    '¿Es usted cónyuge, concubino(a), hijo, hermano, abuelo, padre o nieto de alguna persona que desempeñe o haya desempeñado un cargo público o político?', 0, 'L');
cb($pdf, $ml,      $y + 9, 'NO', $d['pep2'] === 'NO');
cb($pdf, $ml + 12, $y + 9, 'SI', $d['pep2'] === 'SI');
$pdf->SetFont('helvetica', '', 5.8);
$pdf->SetTextColor(90, 90, 90);
$pdf->SetXY($ml + 27, $y + 9);
$pdf->Cell(14, 3, 'Nombre:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 7.5);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($ml + 42, $y + 12.5);
$pdf->Cell($cw - 42, 4, $d['pep2_nombre'], 'B', 0, 'L');
$y += 19;

// ── Declaro bajo protesta ────────────────────────────────────────────────
$pdf->SetFont('helvetica', '', 6.5);
$pdf->SetXY($ml, $y);
$pdf->MultiCell($cw, 3.5,
    "Declaro bajo protesta de decir verdad que para efectos de la realización de operaciones con Wiser Financiera SAPI de CV SOFOM ENR estoy\nactuando bajo la siguiente manera:  A nombre y por cuenta propia",
    0, 'L');
$y += 9;
cb($pdf, $ml + 3, $y, 'No', $d['actua_nombre_propio'] === 'No');
$y += 5;
cb($pdf, $ml + 3, $y, 'Si', $d['actua_nombre_propio'] === 'Si');
$y += 7;

notaTexto($pdf, $ml, $y, $cw,
    'En caso de actuar por cuenta de un tercero proporcionar los siguientes datos (ya sea persona fisica o moral), en caso de que no, pasar al siguiente apartado');
$y += 4.5;

// ── INFORMACIÓN DEL TERCERO ──────────────────────────────────────────────
$y = secHeader($pdf, $ml, $y, $cw, 'INFORMACIÓN DEL TERCERO AUTORIZADO O PROVEEDOR DE RECURSOS');

campo($pdf, $ml,           $y, $fw - 1, 'Nombre(s) (sin abreviaturas)', $d['tercero_nombres']);
campo($pdf, $ml + $fw,     $y, $fw - 1, 'Apellido Paterno',             $d['tercero_ap_pat']);
campo($pdf, $ml + $fw * 2, $y, $fw,     'Apellido Materno',             $d['tercero_ap_mat']);
$y += 8;

// Fecha nacimiento tercero (vacío)
$pdf->SetFont('helvetica', '', 5.8);
$pdf->SetTextColor(90, 90, 90);
foreach ([[$ml, 'Fecha de Nacimiento', 35], [$ml + 40, 'País de Nacimiento', 32], [$ml + 78, 'Entidad federativa de nacimiento', 55]] as [$px, $plbl, $pw]) {
    $pdf->SetXY($px, $y);
    $pdf->Cell($pw, 3, $plbl, 0, 0, 'L');
}
foreach ([[0, 'Día'], [8, 'Mes'], [17, 'Año']] as [$dx, $dlbl]) {
    $pdf->SetFont('helvetica', '', 5.5);
    $pdf->SetXY($ml + $dx, $y + 3);
    $pdf->Cell(7, 2.5, $dlbl, 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY($ml + $dx, $y + 5.5);
    $pdf->Cell(7, 4.5, '', 'B', 0, 'C');
    $pdf->SetFont('helvetica', '', 5.8);
    $pdf->SetTextColor(90, 90, 90);
}
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($ml + 40, $y + 3);  $pdf->Cell(32, 4.5, '', 'B', 0, 'L');
$pdf->SetXY($ml + 78, $y + 3);  $pdf->Cell(55, 4.5, '', 'B', 0, 'L');
cb($pdf, $ml + 152, $y + 3, 'Femenino');
cb($pdf, $ml + 168, $y + 3, 'Masculino');
$y += 10;

campo($pdf, $ml,       $y, 56, 'RFC con homoclave', '');
campo($pdf, $ml + 58,  $y, 76, 'CURP',              '');
campo($pdf, $ml + 136, $y, 54, 'Ocupación',         '');
$y += 8;

campo($pdf, $ml,       $y, 56, 'Teléfono de Casa', '');
campo($pdf, $ml + 58,  $y, 56, 'Teléfono celular', '');
campo($pdf, $ml + 116, $y, 74, 'E-mail',           '');
$y += 8;

campo($pdf, $ml,       $y, 56, 'Tipo de Identificación',             '');
campo($pdf, $ml + 58,  $y, 70, 'Folio o número de identificación',   '');
campo($pdf, $ml + 130, $y, 60, 'Cargo o Relación con la solicitante', '');
$y += 8;
$y += 3;

// ── REFERENCIAS PERSONALES ───────────────────────────────────────────────
$y = secHeader($pdf, $ml, $y, $cw, 'REFERENCIAS PERSONALES');

$pdf->SetFont('helvetica', 'I', 6);
$pdf->SetTextColor(80, 80, 80);
$pdf->SetXY($ml, $y);
$pdf->Cell($cw, 3.5, 'Mayores de 18 años y no todos deben ser familiares', 0, 0, 'L');
$y += 4;

$refCols = [['Nombre completo', 75], ['Teléfono (casa/oficina)', 40], ['Horario para que le llamen', 40], ['Parentesco', 35]];
$pdf->SetFont('helvetica', 'B', 6.5);
$pdf->SetFillColor(215, 215, 215);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($ml, $y);
foreach ($refCols as [$lbl, $w]) {
    $pdf->Cell($w, 5, $lbl, 1, 0, 'C', true);
}
$pdf->Ln();
$y += 5;

$pdf->SetFont('helvetica', '', 7);
$pdf->SetFillColor(255, 255, 255);
foreach ($d['refs'] as $ref) {
    $pdf->SetXY($ml, $y);
    $vals = [$ref['nombre'], $ref['tel'], $ref['horario'], $ref['parentesco']];
    foreach ($refCols as $ri => [$lbl, $w]) {
        $pdf->Cell($w, 6, $vals[$ri], 1, 0, 'L');
    }
    $pdf->Ln();
    $y += 6;
}
$y += 3;

$pdf->SetFont('helvetica', '', 6.5);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($ml, $y);
$pdf->Cell(108, 4.5, '¿Mis datos personales pueden utilizarse con fines mercadotécnicos o publicitarios?', 0, 0, 'L');
cb($pdf, $ml + 110, $y, 'Si', $d['mercado_si']);
cb($pdf, $ml + 118, $y, 'No', !$d['mercado_si']);
$y += 5;
$pdf->SetXY($ml, $y);
$pdf->Cell(108, 4.5, '¿Autorizo Llamadas a mi oficina con fines mercadotécnicos o publicitarios?', 0, 0, 'L');
cb($pdf, $ml + 110, $y, 'Si', $d['llamadas_si']);
cb($pdf, $ml + 118, $y, 'No', !$d['llamadas_si']);
$pdf->SetXY($ml + 130, $y);
$pdf->Cell(60, 4.5, 'Horario:_______________', 0, 0, 'L');
$y += 6;
$y += 4;

// ── AVISO DE PRIVACIDAD ──────────────────────────────────────────────────
$pdf->SetFont('helvetica', 'BU', 8.5);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($ml, $y);
$pdf->Cell($cw, 5.5, 'AVISO DE PRIVACIDAD', 0, 0, 'C');
$y += 6.5;

$aviso = '"EL CLIENTE" reconoce que "WISER" puso a su disposición el aviso de privacidad a través de formatos impresos, digitales, visuales, sonoros o cualquier otra tecnología, incluyendo el que contiene el texto que se encuentra disponible en https://wiserfinanciera.com/avisos-de-privacidad/, con anterioridad a haber obtenido sus datos personales a través del presente formato, consintiendo "EL CLIENTE" expresamente en que "WISER" dé tratamiento a dichos datos personales con sujeción a las finalidades, términos y demás condiciones establecidas en dicho Aviso de Privacidad, en el entendido de que dichos Datos Personales serán tratados de manera confidencial y serán usados para la operación y registro de los productos que "EL CLIENTE" hubiese contratado, así como para ofrecerle, en su caso, otros bienes, servicios y/o productos bancarios o financieros de "WISER" y promociones de otros bienes o servicios relacionados con dichos productos o servicios crediticios o financieros.';
$pdf->SetFont('helvetica', '', 6.2);
$pdf->SetXY($ml, $y);
$pdf->MultiCell($cw, 3.3, $aviso, 0, 'J');
$y += 20;

$pdf->SetXY($ml, $y);
$pdf->Cell(55, 4.5, '', 'T', 0, 'C');
$y += 5.5;
$pdf->SetFont('helvetica', '', 8);
$pdf->SetXY($ml, $y);
$pdf->Cell(55, 4, '"EL CLIENTE"', 0, 0, 'C');
$y += 10;

// ── ACEPTACIÓN SOLICITUD ─────────────────────────────────────────────────
$pdf->SetFont('helvetica', 'BU', 8.5);
$pdf->SetXY($ml, $y);
$pdf->Cell($cw, 5.5, 'ACEPTACIÓN SOLICITUD', 0, 0, 'C');
$y += 6.5;

$acep = "Declaro bajo protesta de decir la verdad que la información aquí asentada es cierta y que el origen de los fondos en los productos y servicios depositados en Wiser Financiera SAPI de CV SOFOM ENR, proceden de fuentes lícitas, así mismo conozco que el permitir a un tercero el uso de la cuenta sin haberlo declarado, u ocultando o falseando información, o actuando como prestanombres de un tercero, puede dar lugar a que hagan uso indebido de la cuenta, lo que a su vez podría llegar a constituir la comisión de un delito.\n\nCon la firma de esta Solicitud expreso mi conocimiento y conformidad con lo estipulado en las declaraciones y cláusulas del contrato integrado a este documento (inscrito en el Registro de Contratos de Adhesión de la CONDUSEF con el número 16470-439-041284/01-02968-1024 así como de su Carátula.";
$pdf->SetFont('helvetica', '', 6.2);
$pdf->SetXY($ml, $y);
$pdf->MultiCell($cw, 3.3, $acep, 0, 'J');
$y += 22;

$pdf->SetXY($ml, $y);
$pdf->Cell(55, 4.5, '', 'T', 0, 'C');
$y += 5.5;
$pdf->SetFont('helvetica', '', 8);
$pdf->SetXY($ml, $y);
$pdf->Cell(55, 4, '"EL CLIENTE"', 0, 0, 'C');

footerPDF($pdf, $ml, $cw);

// ═══════════════════════════════════════════════════════════════════════════
//  PÁGINA 3
// ═══════════════════════════════════════════════════════════════════════════
$pdf->AddPage();
pdfHeader($pdf, $ml, $cw, $logoPath, $folio, $clienteId, $fecha);
$y = 31;

// ── FIRMA ELECTRÓNICA AVANZADA ───────────────────────────────────────────
$y = secHeader($pdf, $ml, $y, $cw, 'FIRMA ELECTRONICA AVANZADA');

$pdf->SetFont('helvetica', '', 7);
$pdf->SetXY($ml, $y);
$pdf->Cell(70, 4, 'Cuenta con Firma Electrónica Avanzada:', 0, 0, 'L');
$y += 5;

cb($pdf, $ml, $y, 'Si', $d['fea'] === 'SI');
$pdf->SetFont('helvetica', '', 5.8);
$pdf->SetTextColor(90, 90, 90);
$pdf->SetXY($ml + 10, $y);
$pdf->Cell(45, 3, 'No. Certificado Firma Electrónica Avanzada', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 7.5);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($ml + 10, $y + 3);
$pdf->Cell(110, 5, $d['fea_cert'], 'B', 0, 'L');
$y += 9;

cb($pdf, $ml, $y, 'No', $d['fea'] === 'NO');
$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($ml + 10, $y);
$pdf->Cell(85, 4, 'Yo ------------- manifiesto que NO cuento con número de serie de Firma', 0, 0, 'L');
$pdf->SetXY($ml + 10, $y + 4);
$pdf->Cell(65, 4, 'Electrónica Avanzada', 0, 0, 'L');
$pdf->SetXY($ml + 80, $y + 8);
$pdf->Cell(60, 4, $d['fea_nombre_decl'], 'B', 0, 'L');
$y += 14;
$y += 4;

// Caja Nombre y Firma del Ejecutivo
$pdf->SetDrawColor(180, 180, 180);
$pdf->SetFillColor(248, 248, 248);
$pdf->Rect($ml, $y, $cw, 16, 'DF');
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($ml + 2, $y + 2);
$pdf->Cell($cw - 4, 5, 'Nombre y Firma del Ejecutivo:', 0, 0, 'L');
$y += 20;
$y += 4;

// ── DOCUMENTACIÓN REQUERIDA ──────────────────────────────────────────────
$pdf->SetFont('helvetica', 'BU', 8.5);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY($ml, $y);
$pdf->Cell($cw, 6, 'DOCUMENTACIÓN REQUERIDA', 0, 0, 'L');
$y += 8;

$docs = [
    'Identificación personal (INE, Pasaporte, Licencia para Conducir, FM2, Cédula profesional)',
    'Constancia de la Clave Única de Registro de Población',
    'Comprobante de domicilio  (con antigüedad de expedición no mayor a dos meses)',
    'Constancia de Situación Fiscal (con fecha de expedición en el mes de la solicitud de crédito)',
    "En caso de que la persona física actúe como apoderado de otra persona.- copia simple de la carta poder o de la copia certificada\ndel documento expedido por fedatario público, que acredite las facultades conferidas al apoderado, así como una\nidentificación oficial y comprobante de domicilio de este.",
    'No. Certificado Firma Electrónica Avanzada',
    'Últimos tres estados de cuenta o declaraciones fiscales',
];

$cbSz = 4;
foreach ($docs as $docText) {
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect($ml, $y, $cbSz, $cbSz, 'D');
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY($ml + $cbSz + 2, $y - 0.5);
    $lineCount = substr_count($docText, "\n") + 1;
    $pdf->MultiCell($cw - $cbSz - 2, 4, $docText, 0, 'L');
    $y += $lineCount * 4 + 3;
}

footerPDF($pdf, $ml, $cw);

// ─── Salida ─────────────────────────────────────────────────────────────────
$pdf->Output('Solicitud_Credito_Demo.pdf', 'I');
