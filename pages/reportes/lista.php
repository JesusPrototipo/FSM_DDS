<?php
// ============================================================
//  WEBDDS — pages/reportes/lista.php
// ============================================================
require_once dirname(__DIR__, 2) . '/includes/init.php';
Auth::requerir();

$titulo_pagina = 'Reportes';
$pagina_activa = 'reportes';
$subtitulo     = 'Órdenes de Servicio';

require_once INCLUDES . '/header.php';

$pdo = Database::get();

// Parámetros de filtro
$pagina     = max(1, (int)($_GET['pagina']     ?? 1));
$estatus    = $_GET['estatus']    ?? '';
$tecnico_id = (int)($_GET['tecnico_id'] ?? 0);
$q          = trim($_GET['q']          ?? '');
$porPagina  = 20;
$offset     = ($pagina - 1) * $porPagina;

// Construir WHERE
$where  = ['1=1'];
$params = [];
if ($estatus)    { $where[] = 'r.estatus = :estatus';       $params[':estatus']    = $estatus; }
if ($tecnico_id) { $where[] = 'r.tecnico_id = :tid';        $params[':tid']        = $tecnico_id; }
if ($q) {
    $where[]       = '(c.razon LIKE :q1 OR i.modelo LIKE :q2 OR i.serie LIKE :q3)';
    $like          = "%$q%";
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':q3'] = $like;
}
$whereStr = implode(' AND ', $where);

$baseJoin = "FROM reportes_fallas r
             INNER JOIN clientes      c ON r.id_cliente     = c.id
             INNER JOIN departamentos d ON r.id_departamento = d.id
             INNER JOIN impresoras    i ON r.id_impresora   = i.id
             LEFT  JOIN usuarios      u ON r.tecnico_id     = u.id
             WHERE $whereStr";

// Total
$stmtT = $pdo->prepare("SELECT COUNT(*) $baseJoin");
$stmtT->execute($params);
$total = (int)$stmtT->fetchColumn();

// Datos
$stmt = $pdo->prepare(
    "SELECT r.id, r.fecha, r.falla, r.estatus,
            c.razon, d.departamento,
            i.marca, i.modelo, i.serie,
            u.nombre AS tecnico_nombre
     $baseJoin
     ORDER BY r.id DESC
     LIMIT :lim OFFSET :off"
);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset,    PDO::PARAM_INT);
$stmt->execute();
$reportes = $stmt->fetchAll();

// Técnicos para filtro
$tecnicos = $pdo->query(
    "SELECT id, nombre FROM usuarios WHERE activo=1 AND rol IN ('admin','tecnico') ORDER BY nombre"
)->fetchAll();

// Contadores por estatus (para badges en tabs)
$contadores = [];
foreach (['pendiente','en proceso','finalizado','cancelado'] as $est) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM reportes_fallas WHERE estatus = :e");
    $s->execute([':e' => $est]);
    $contadores[$est] = (int)$s->fetchColumn();
}

$totalPaginas = (int)ceil($total / $porPagina);

function urlFiltro(array $extra = []): string {
    $base = array_filter([
        'estatus'    => $_GET['estatus']    ?? '',
        'tecnico_id' => $_GET['tecnico_id'] ?? '',
        'q'          => $_GET['q']          ?? '',
        'pagina'     => '1',
    ]);
    return '?' . http_build_query(array_merge($base, $extra));
}

$badgeClass = [
    'pendiente'  => 'badge-pendiente',
    'en proceso' => 'badge-en-proceso',
    'finalizado' => 'badge-finalizado',
    'cancelado'  => 'badge-cancelado',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        Reportes <span class="badge bg-secondary ms-1"><?= $total ?></span>
    </h5>
    <?php if (Auth::tieneRol(ROL_ADMIN, ROL_CALLCENTER, ROL_TECNICO)): ?>
    <a href="<?= BASE_PATH ?>/pages/reportes/nuevo.php" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Nuevo Reporte
    </a>
    <?php endif; ?>
</div>

<!-- Tabs de estatus -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= !$estatus ? 'active' : '' ?>"
           href="<?= urlFiltro(['estatus' => '']) ?>">
            Todos <span class="badge bg-secondary"><?= array_sum($contadores) ?></span>
        </a>
    </li>
    <?php foreach ([
        'pendiente'  => 'Pendientes',
        'en proceso' => 'En Proceso',
        'finalizado' => 'Finalizados',
        'cancelado'  => 'Cancelados',
    ] as $est => $label): ?>
    <li class="nav-item">
        <a class="nav-link <?= $estatus === $est ? 'active' : '' ?>"
           href="<?= urlFiltro(['estatus' => $est]) ?>">
            <?= $label ?>
            <span class="badge <?= $badgeClass[$est] ?> ms-1">
                <?= $contadores[$est] ?>
            </span>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<!-- Filtros adicionales -->
<form method="GET" class="row g-2 mb-3">
    <?php if ($estatus): ?>
    <input type="hidden" name="estatus" value="<?= e($estatus) ?>">
    <?php endif; ?>

    <div class="col-md-5">
        <div class="input-group input-group-sm">
            <input type="text" name="q" class="form-control"
                   placeholder="Cliente, modelo o serie…"
                   value="<?= e($q) ?>">
            <button class="btn btn-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
            <?php if ($q): ?>
            <a href="<?= urlFiltro(['q' => '']) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-x"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-4">
        <select name="tecnico_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">— Todos los técnicos —</option>
            <?php foreach ($tecnicos as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $tecnico_id === (int)$t['id'] ? 'selected' : '' ?>>
                <?= e($t['nombre']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-3 d-flex gap-1">
        <?php if ($q || $tecnico_id || $estatus): ?>
        <a href="?" class="btn btn-outline-secondary btn-sm w-100">
            <i class="bi bi-x-circle me-1"></i> Limpiar filtros
        </a>
        <?php endif; ?>
    </div>
</form>

<!-- Tabla -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th style="width:55px">#</th>
                    <th>Cliente</th>
                    <th>Equipo</th>
                    <th>Falla</th>
                    <th>Técnico</th>
                    <th>Fecha</th>
                    <th>Estatus</th>
                    <th style="width:70px"></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($reportes)): ?>
                <tr>
                    <td colspan="8" class="text-center text-secondary py-4">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        No hay reportes con los filtros seleccionados.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($reportes as $r): ?>
                <tr>
                    <td class="text-secondary small"><?= $r['id'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= e($r['razon']) ?></div>
                        <div class="text-secondary small"><?= e($r['departamento']) ?></div>
                    </td>
                    <td>
                        <div><?= e($r['marca'] . ' ' . $r['modelo']) ?></div>
                        <code class="small"><?= e($r['serie'] ?: '—') ?></code>
                    </td>
                    <td class="small text-truncate" style="max-width:200px"
                        title="<?= e($r['falla']) ?>">
                        <?= e($r['falla']) ?>
                    </td>
                    <td class="small"><?= e($r['tecnico_nombre'] ?? '—') ?></td>
                    <td class="small text-nowrap"><?= e($r['fecha']) ?></td>
                    <td>
                        <span class="badge <?= $badgeClass[$r['estatus']] ?? 'bg-secondary' ?>">
                            <?= e($r['estatus']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= BASE_PATH ?>/pages/reportes/detalle.php?id=<?= $r['id'] ?>"
                           class="btn btn-sm btn-outline-primary" title="Ver reporte">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Paginación -->
<?php if ($totalPaginas > 1): ?>
<nav class="mt-3">
    <ul class="pagination justify-content-center mb-0">
        <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= urlFiltro(['pagina' => $pagina - 1]) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php for ($i = max(1,$pagina-2); $i <= min($totalPaginas,$pagina+2); $i++): ?>
        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
            <a class="page-link" href="<?= urlFiltro(['pagina' => $i]) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= urlFiltro(['pagina' => $pagina + 1]) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>
<p class="text-center text-secondary small mt-2">
    Mostrando <?= count($reportes) ?> de <?= $total ?> reportes
</p>
<?php endif; ?>

<?php require_once INCLUDES . '/footer.php'; ?>
