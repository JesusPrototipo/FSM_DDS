<?php
// ============================================================
//  WEBDDS — index.php  (Dashboard)
//  Reemplaza el index.php de Fase 2 con estadísticas reales
// ============================================================
require_once __DIR__ . '/includes/init.php';
Auth::requerir();

$titulo_pagina = 'Inicio';
$pagina_activa = 'inicio';
$subtitulo     = 'Panel de Control';

require_once INCLUDES . '/header.php';

$pdo     = Database::get();
$usuario = Auth::usuario();

// ── Contadores generales ─────────────────────────────────────
$totales = [];
foreach ([
    'clientes'   => "SELECT COUNT(*) FROM clientes",
    'impresoras' => "SELECT COUNT(*) FROM impresoras",
    'pendientes' => "SELECT COUNT(*) FROM reportes_fallas WHERE estatus = 'pendiente'",
    'en_proceso' => "SELECT COUNT(*) FROM reportes_fallas WHERE estatus = 'en proceso'",
    'finalizados'=> "SELECT COUNT(*) FROM reportes_fallas WHERE estatus = 'finalizado'",
    'total_rep'  => "SELECT COUNT(*) FROM reportes_fallas",
] as $k => $sql) {
    $totales[$k] = (int)$pdo->query($sql)->fetchColumn();
}

// ── Reportes de los últimos 6 meses (para gráfica) ──────────
$meses = $pdo->query(
    "SELECT 
        ANY_VALUE(DATE_FORMAT(STR_TO_DATE(fecha, '%Y-%m-%d'), '%b %Y')) AS mes,
        COUNT(*) AS total
     FROM reportes_fallas
     WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(STR_TO_DATE(fecha, '%Y-%m-%d'), '%Y-%m')
     ORDER BY MIN(STR_TO_DATE(fecha, '%Y-%m-%d')) ASC
     LIMIT 6"
)->fetchAll();

// ── Top técnicos del mes ─────────────────────────────────────
$topTecnicos = $pdo->query(
    "SELECT u.nombre, COUNT(r.id) AS total
     FROM reportes_fallas r
     INNER JOIN usuarios u ON r.tecnico_id = u.id
     WHERE r.fecha >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
     GROUP BY u.id, u.nombre
     ORDER BY total DESC
     LIMIT 5"
)->fetchAll();

// ── Equipos con más fallas (top 5) ──────────────────────────
$topEquipos = $pdo->query(
    "SELECT i.marca, i.modelo, i.serie, c.razon, COUNT(r.id) AS total
     FROM reportes_fallas r
     INNER JOIN impresoras i ON r.id_impresora = i.id
     INNER JOIN clientes   c ON r.id_cliente   = c.id
     GROUP BY i.id, i.marca, i.modelo, i.serie, c.razon
     ORDER BY total DESC
     LIMIT 5"
)->fetchAll();

// ── Últimos 5 reportes ───────────────────────────────────────
$ultimosRep = $pdo->query(
    "SELECT r.id, r.fecha, r.falla, r.estatus,
            c.razon, i.modelo, u.nombre AS tecnico
     FROM reportes_fallas r
     INNER JOIN clientes   c ON r.id_cliente   = c.id
     INNER JOIN impresoras i ON r.id_impresora = i.id
     LEFT  JOIN usuarios   u ON r.tecnico_id   = u.id
     ORDER BY r.id DESC LIMIT 5"
)->fetchAll();

$badgeClass = [
    'pendiente'  => 'badge-pendiente',
    'en proceso' => 'badge-en-proceso',
    'finalizado' => 'badge-finalizado',
    'cancelado'  => 'badge-cancelado',
];
?>

<!-- ── Tarjetas de resumen ────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <a href="<?= BASE_PATH ?>/pages/clientes/lista.php" class="text-decoration-none">
            <div class="card text-bg-primary h-100">
                <div class="card-body text-center py-3">
                    <i class="bi bi-people fs-2"></i>
                    <div class="fs-3 fw-bold"><?= $totales['clientes'] ?></div>
                    <div class="small">Clientes</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= BASE_PATH ?>/pages/impresoras/lista.php" class="text-decoration-none">
            <div class="card text-bg-secondary h-100">
                <div class="card-body text-center py-3">
                    <i class="bi bi-printer fs-2"></i>
                    <div class="fs-3 fw-bold"><?= $totales['impresoras'] ?></div>
                    <div class="small">Equipos</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= BASE_PATH ?>/pages/reportes/lista.php?estatus=pendiente"
           class="text-decoration-none">
            <div class="card text-bg-warning h-100">
                <div class="card-body text-center py-3">
                    <i class="bi bi-clock-history fs-2"></i>
                    <div class="fs-3 fw-bold"><?= $totales['pendientes'] ?></div>
                    <div class="small">Pendientes</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= BASE_PATH ?>/pages/reportes/lista.php?estatus=en+proceso"
           class="text-decoration-none">
            <div class="card text-bg-info h-100">
                <div class="card-body text-center py-3">
                    <i class="bi bi-gear-wide-connected fs-2"></i>
                    <div class="fs-3 fw-bold"><?= $totales['en_proceso'] ?></div>
                    <div class="small">En Proceso</div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-3 mb-4">

    <!-- ── Gráfica de reportes por mes ──────────────────────── -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-bar-chart me-1"></i> Reportes — Últimos 6 meses
            </div>
            <div class="card-body">
                <?php if (empty($meses)): ?>
                <p class="text-secondary text-center py-3">Sin datos suficientes aún.</p>
                <?php else: ?>
                <?php
                $maxVal = max(array_column($meses, 'total')) ?: 1;
                ?>
                <div class="d-flex align-items-end gap-2 h-100"
                     style="min-height:160px; padding-bottom:24px; position:relative;">
                    <?php foreach ($meses as $m): ?>
                    <?php $pct = round(($m['total'] / $maxVal) * 100); ?>
                    <div class="d-flex flex-column align-items-center flex-grow-1">
                        <span class="small text-secondary mb-1"><?= $m['total'] ?></span>
                        <div class="bg-primary rounded-top w-100"
                             style="height:<?= max($pct, 4) ?>%; min-height:4px;
                                    transition: height .3s;">
                        </div>
                        <small class="text-secondary mt-1 text-nowrap"
                               style="font-size:.65rem">
                            <?= e($m['mes']) ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Resumen de estatus (donut visual) ────────────────── -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-pie-chart me-1"></i> Estatus General
            </div>
            <div class="card-body">
                <?php
                $estadosData = [
                    'Pendientes'  => [$totales['pendientes'],  'bg-warning'],
                    'En Proceso'  => [$totales['en_proceso'],  'bg-info'],
                    'Finalizados' => [$totales['finalizados'], 'bg-success'],
                    'Total'       => [$totales['total_rep'],   'bg-secondary'],
                ];
                ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($estadosData as $label => [$val, $cls]): ?>
                    <?php $pct = $totales['total_rep'] > 0
                        ? round(($val / $totales['total_rep']) * 100) : 0; ?>
                    <li class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><?= $label ?></span>
                            <strong><?= $val ?> <?= $label !== 'Total' ? "($pct%)" : '' ?></strong>
                        </div>
                        <?php if ($label !== 'Total'): ?>
                        <div class="progress" style="height:8px">
                            <div class="progress-bar <?= $cls ?>"
                                 style="width:<?= $pct ?>%"></div>
                        </div>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

</div>

<div class="row g-3 mb-4">

    <!-- ── Top técnicos del mes ─────────────────────────────── -->
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-trophy me-1"></i> Técnicos activos este mes
            </div>
            <div class="card-body p-0">
                <?php if (empty($topTecnicos)): ?>
                <p class="text-secondary text-center py-3 small">
                    Sin reportes este mes.
                </p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($topTecnicos as $i => $t): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <span class="text-secondary me-2"><?= $i + 1 ?>.</span>
                            <?= e($t['nombre']) ?>
                        </span>
                        <span class="badge bg-primary"><?= $t['total'] ?> rep.</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Equipos con más fallas ───────────────────────────── -->
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-exclamation-triangle me-1"></i> Equipos con más fallas
            </div>
            <div class="card-body p-0">
                <?php if (empty($topEquipos)): ?>
                <p class="text-secondary text-center py-3 small">Sin datos aún.</p>
                <?php else: ?>
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Equipo</th>
                            <th>Cliente</th>
                            <th class="text-center">Reportes</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($topEquipos as $eq): ?>
                    <tr>
                        <td>
                            <div class="small fw-semibold">
                                <?= e($eq['marca'] . ' ' . $eq['modelo']) ?>
                            </div>
                            <code class="smaller"><?= e($eq['serie'] ?: '—') ?></code>
                        </td>
                        <td class="small"><?= e($eq['razon']) ?></td>
                        <td class="text-center">
                            <span class="badge bg-danger"><?= $eq['total'] ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ── Últimos reportes ──────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock me-1"></i> Últimos reportes</span>
        <a href="<?= BASE_PATH ?>/pages/reportes/lista.php"
           class="btn btn-sm btn-outline-secondary">Ver todos</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Equipo</th>
                    <th>Técnico</th>
                    <th>Estatus</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ultimosRep as $r): ?>
            <tr>
                <td class="text-secondary small">
                    <?= str_pad($r['id'], 5, '0', STR_PAD_LEFT) ?>
                </td>
                <td><?= e($r['razon']) ?></td>
                <td class="small"><?= e($r['modelo']) ?></td>
                <td class="small"><?= e($r['tecnico'] ?? '—') ?></td>
                <td>
                    <span class="badge <?= $badgeClass[$r['estatus']] ?? 'bg-secondary' ?>">
                        <?= e($r['estatus']) ?>
                    </span>
                </td>
                <td>
                    <a href="<?= BASE_PATH ?>/pages/reportes/detalle.php?id=<?= $r['id'] ?>"
                       class="btn btn-xs btn-outline-primary btn-sm">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Accesos rápidos ───────────────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-lightning me-1"></i> Acciones rápidas
    </div>
    <div class="card-body d-flex flex-wrap gap-2">
        <?php if (Auth::tieneRol(ROL_ADMIN, ROL_GERENCIA, ROL_TECNICO)): ?>
        <a href="<?= BASE_PATH ?>/pages/reportes/nuevo.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Nuevo Reporte
        </a>
        <?php endif; ?>
        <?php if (Auth::tieneRol(ROL_ADMIN, ROL_GERENCIA, ROL_ADMINISTRATIVO, ROL_VENDEDOR)): ?>
        <a href="<?= BASE_PATH ?>/pages/clientes/nuevo.php" class="btn btn-outline-primary">
            <i class="bi bi-person-plus me-1"></i> Nuevo Cliente
        </a>
        <?php endif; ?>
        <a href="<?= BASE_PATH ?>/pages/reportes/lista.php?estatus=pendiente"
           class="btn btn-outline-warning">
            <i class="bi bi-clock me-1"></i> Ver Pendientes
        </a>
        <a href="<?= BASE_PATH ?>/api/reportes.php?accion=exportar_csv"
           class="btn btn-outline-secondary">
            <i class="bi bi-download me-1"></i> Exportar Reportes CSV
        </a>
    </div>
</div>

<?php require_once INCLUDES . '/footer.php'; ?>
