<?php
// ============================================================
//  WEBDDS — pages/reportes/pdf.php
//  Genera el reporte de servicio en PDF usando HTML + CSS
//  No requiere FPDF ni librerías externas.
//  El navegador imprime/guarda el PDF con Ctrl+P → Guardar como PDF.
//  Los datos largos nunca desbordan: usan word-wrap y celdas flexibles.
// ============================================================
require_once dirname(__DIR__, 2) . '/includes/init.php';
Auth::requerir();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_PATH . '/pages/reportes/lista.php');
    exit;
}

$pdo  = Database::get();
$stmt = $pdo->prepare(
    "SELECT r.*,
            c.razon, c.reporto AS contacto, c.telefono, c.direccion, c.horario,
            d.departamento,
            i.marca, i.modelo, i.serie, i.status AS equipo_status,
            u.nombre AS tecnico_nombre
     FROM reportes_fallas r
     INNER JOIN clientes      c ON r.id_cliente      = c.id
     INNER JOIN departamentos d ON r.id_departamento = d.id
     INNER JOIN impresoras    i ON r.id_impresora    = i.id
     LEFT  JOIN usuarios      u ON r.tecnico_id      = u.id
     WHERE r.id = :id"
);
$stmt->execute([':id' => $id]);
$r = $stmt->fetch();

if (!$r) {
    header('Location: ' . BASE_PATH . '/pages/reportes/lista.php');
    exit;
}

$folio     = str_pad($id, 5, '0', STR_PAD_LEFT);
$statusMap = ['Renta' => 'RENTA', 'Propio' => 'PROPIO', 'Poliza' => 'PÓLIZA',
              'Garantia' => 'GARANTÍA', 'Otras' => 'OTRAS'];

$activitiMap = ['Instalacion' => 'INSTALACION', 'Mantenimiento' => 'MANTENIMIENTO', 'Conexion' => 'CONEXION',
              'Asesoria' => 'ASESORIA', 'Revision' => 'REVISION'];

$tservicio = $r['tservicio'] ?? '';
$equipoStatus = $r['equipo_status'] ?? '';
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8">
<title>Reporte #<?= $folio ?> — <?= e($r['razon']) ?></title>
<style>
  /* ── Reset y página ── */
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 9pt;
    color: #111;
    background: #fff;
  }
  @page { size: Letter portrait; margin: 10mm 10mm 14mm 10mm; }
  @media print {
    .no-print { display: none !important; }
    body { font-size: 9pt; }
  }
  @media screen {
    body { max-width: 215mm; margin: 10mm auto; padding: 5mm; background:#e5e5e5; }
    .page { background:#fff; padding: 10mm; box-shadow: 0 2px 8px rgba(0,0,0,.2); }
  }

  /* ── Encabezado ── */
  .hdr { display:flex; justify-content:space-between; align-items:center;
         border-bottom: 2px solid #333; padding-bottom: 6px; margin-bottom: 8px; }
  .hdr-brand { font-size: 13pt; font-weight: bold; letter-spacing: 1px; }
  .hdr-sub   { font-size: 8pt; color: #555; }
  .hdr-folio { text-align:right; }
  .hdr-folio .num { font-size: 14pt; font-weight: bold; }
  .hdr-folio .lbl { font-size: 7pt; color:#555; text-transform:uppercase; }

  /* ── Tablas generales ── */
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #aaa; padding: 3px 5px; vertical-align: top; }
  th { background: #ddd; font-weight: bold; text-align: center; font-size: 8pt; }
  td { font-size: 8.5pt; word-wrap: break-word; overflow-wrap: break-word; }
  .lbl { font-weight: bold; color: #444; font-size: 7.5pt; white-space: nowrap; width: 1%; }
  .section-title {
    background: #333; color: #fff; text-align: center;
    font-weight: bold; font-size: 8pt; padding: 3px;
    letter-spacing: .5px;
  }

  /* ── Status checkboxes ── */
  .status-row td { text-align:center; font-size:8pt; border: 1px solid #aaa; }
  .status-row .active { font-weight:bold; text-decoration:underline; }

  /* ── Secciones de texto libre ── */
  .text-area { min-height: 28mm; border:1px solid #aaa; padding:3px; font-size:8.5pt; }
  .text-area-lg { min-height: 40mm; }

  /* ── Firmas ── */
  .firmas { display:flex; justify-content:space-around; margin-top:8px; }
  .firma  { text-align:center; width:40%; }
  .firma-line { border-top: 1px solid #333; margin-top: 14mm; padding-top: 3px;
                font-size: 8pt; }

  /* ── Pie ── */
  .footer-pdf { text-align:center; margin-top:8px; font-size:7.5pt; color:#555;
                border-top:1px solid #ccc; padding-top:4px; }

  /* ── Botón imprimir (solo pantalla) ── */
  .btn-print {
    display:block; margin: 8px auto 16px; padding: 8px 28px;
    background:#0d6efd; color:#fff; border:none; border-radius:4px;
    font-size:11pt; cursor:pointer;
  }
</style>
</head>
<body>

<!-- Botón solo visible en pantalla -->
<button class="btn-print no-print" onclick="window.print()">
    🖨 Imprimir / Guardar PDF
</button>

<div class="page">

  <!-- ── Encabezado ────────────────────────────────────────── -->
  <div class="hdr">
    <div>
      <img src="../../img/BanerDDSN.png" alt="Banner DDS" width="750" height="90">
    </div>
    
  </div>

  <!-- ── Título ────────────────────────────────────────────── -->
  <div class="section-title" style="margin-bottom:15px;">
    REPORTE DE SERVICIO TÉCNICO
  </div>

  <!-- ── Información general ────────────────────────────────────────────── -->
  <table style="margin-bottom:-1px;">
    <tr>
      <th colspan="8" class="section-title" style="background:#555;">INFORMACIÓN GENERAL</th>
    </tr>
    <tr>
      <td class="lbl">Folio</td>
      <td style="width:10%;font-weight:bold;">#<?= $folio ?></td>
      <td class="lbl">Asignado</td>
      <td style="width:30%;"><?= e($r['tecnico_nombre'] ?? '—') ?></td>
      <td class="lbl">Recibida</td>
      <td style="width:15%;"><?= e($r['fecha']) ?></td>
      <td class="lbl">Realizada</td>
      <td>&nbsp;</td>
    </tr>
  </table>

  <table style="margin-bottom:20px;">
    <tr>      
      <td class="lbl">Actividad</td>
          <td>
            <?php foreach (['Instalacion','Mantenimiento','Conexion','Asesoria','Revision'] as $v): ?>
              <span style="<?= $tservicio === $v ? 'font-weight:bold;text-decoration:underline;' : 'color:#999;' ?>
                            margin-right:6px; font-size:7.5pt;">
                <?= $activitiMap[$v] ?>
              </span>
            <?php endforeach; ?>
          </td>

        <td class="lbl">LLegada</td>
        <td style="width:90px;">&nbsp;</td>
        <td class="lbl" >Salida</td>
        <td style="width:90px;">&nbsp;</td>
      
    </tr>
  </table>

  <!-- ── Cliente y Tiempos ─────────────────────────────────── -->
  <table style="margin-bottom:-1px;">
    <tr>
      <th colspan="2" style="width:61.7%;">INFORMACIÓN DEL CLIENTE</th>
      <th colspan="2" style="width:45%;">INFORMACIÓN DEL EQUIPO</th>
    </tr>
    <tr>
      <td class="lbl" style="width:11.5%;">Razón Social</td>
      <td><?= e($r['razon']) ?></td>
      <td class="lbl">Marca</td>
      <td><?= e($r['marca']) ?></td>
    </tr>
    <tr>
      <td class="lbl">Contacto</td>
      <td><?= e($r['contacto']) ?></td>
      <td class="lbl">Modelo</td>
      <td><?= e($r['modelo']) ?></td>
    </tr>
    <tr>
      <td class="lbl">Teléfono</td>
      <td><?= e($r['telefono']) ?></td>
      <td class="lbl">Serie</td>
      <td><?= e($r['serie'] ?: '—') ?></td>
    </tr>
    <tr>
      <td class="lbl">Dirección</td>
      <td style="max-width:90mm;"><?= e($r['direccion']) ?></td>
      <td class="lbl">Estatus</td>
      <td>
        <?php foreach (['Renta','Propio','Poliza','Garantia','Otras'] as $s): ?>
          <span style="<?= $equipoStatus === $s ? 'font-weight:bold;text-decoration:underline;' : 'color:#999;' ?>
                        margin-right:6px; font-size:7.5pt;">
            <?= $statusMap[$s] ?>
          </span>
        <?php endforeach; ?>
      </td>
    </tr>

    
    
    <tr>
      <td class="lbl">Departamento</td>
      <td><?= e($r['departamento']) ?></td>
      <td class="lbl">Horario</td>
      <td style="width:190px;"><?= e($r['horario']) ?></td>

    </tr>
  </table>

  <table style="margin-bottom:20px;">
    <tr>
      <td class="lbl" style="width:11.5%;">Departamento</td>
      <td style="width:5px;"><?= e($r['departamento']) ?></td>
      <td class="lbl">Horario</td>
      <td style="width:190px;"><?= e($r['horario']) ?></td>
      
    </tr>
  </table>

  <!-- ── Consumibles y Contadores ──────────────────────────── -->
  <table style="margin-bottom:4px; font-size:7.5pt;">
    <tr>
      <th colspan="5" style="width:55%;">CONSUMIBLES</th>
      <th colspan="1" style="width:20%;"> </th>
      <th colspan="2" style="width:25%;">CONTADORES</th>
    </tr>
    <tr>
      <td class="lbl" style="width:12%;">Tipo</td>
      <td style="text-align:center;width:11%;">K / T1</td>
      <td style="text-align:center;width:11%;">C / T2</td>
      <td style="text-align:center;width:11%;">M / T3</td>
      <td style="text-align:center;width:11%;">Y / T4</td>
      <td class="lbl" style="width:13%;"> </td>
      <td class="lbl" style="width:12%;">B/N</td>
      <td style="width:19%;"></td>
    </tr>
    <?php foreach (['Toner','Tambor','Rodillos'] as $i => $tipo): ?>
    <tr>
      <td class="lbl"><?= $tipo ?></td>
      <td></td><td></td><td></td><td></td>
      <td></td>
      <td class="lbl"><?= ['Color','BN Tab','Color Tab'][$i] ?></td>
      <td></td>
    </tr>
    <?php endforeach; ?>
    <tr>
      <td class="lbl">Fusor</td><td></td>
      <td class="lbl" style="text-align:right;">Residuos</td><td></td><td></td>
      <td></td><td class="lbl">Total</td>
      <td></td>
    </tr>
  </table>

  <!-- ── Problema y Solución ───────────────────────────────── -->
  <table style="margin-bottom:4px;">
    <tr>
      <th style="width:50%;">PROBLEMA REPORTADO</th>
      <th style="width:50%;">PIEZAS / CONSUMIBLES UTILIZADOS</th>
    </tr>
    <tr>
      <td style="min-height:28mm; height:28mm; vertical-align:top;">
        <?= nl2br(e($r['falla'])) ?>
      </td>
      <td style="min-height:28mm; height:28mm;"></td>
    </tr>
  </table>

  <table style="margin-bottom:8px;">
    <tr>
      <th>SOLUCIÓN DEL PROBLEMA</th>
    </tr>
    <tr>
      <td style="min-height:36mm; height:36mm; vertical-align:top;">
        <?= nl2br(e($r['observaciones'] ?? '')) ?>
      </td>
    </tr>
  </table>

  <!-- ── Firmas ────────────────────────────────────────────── -->
  <div class="firmas">
    <div class="firma">
      <div class="firma-line">Firma del Técnico</div>
    </div>
    <div class="firma">
      <div class="firma-line">Firma del Cliente</div>
    </div>
  </div>

  <!-- ── Pie de página ─────────────────────────────────────── -->
  <div class="footer-pdf">
    <img src="../../img/BanerBajo2T.png" alt="Descripción de la imagen" width="700" height="60">
    
  </div>

</div><!-- /page -->
</body>
</html>
