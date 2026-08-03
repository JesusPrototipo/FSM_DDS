<?php
// ============================================================
//  WEBDDS — pages/impresoras/lista.php
// ============================================================
require_once dirname(__DIR__, 2) . '/includes/init.php';
Auth::requerir();

$titulo_pagina = 'Impresoras';
$pagina_activa = 'impresoras';
$subtitulo     = 'Equipos registrados';

require_once INCLUDES . '/header.php';

$pdo       = Database::get();
$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
$q         = trim($_GET['q'] ?? '');
$porPagina = 20;
$offset    = ($pagina - 1) * $porPagina;

$where  = ['1=1'];
$params = [];
if ($q) {
    $where[]       = '(i.serie LIKE :q1 OR i.modelo LIKE :q2 OR c.razon LIKE :q3 OR i.marca LIKE :q4)';
    $like          = "%$q%";
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':q3'] = $like;
    $params[':q4'] = $like;
}
$whereStr = implode(' AND ', $where);

$base = "FROM impresoras i
         INNER JOIN clientes      c ON i.cliente_id      = c.id
         INNER JOIN departamentos d ON i.departamento_id = d.id
         WHERE $whereStr";

$total = $pdo->prepare("SELECT COUNT(*) $base");
$total->execute($params);
$total = (int)$total->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT i.id, i.marca, i.modelo, i.serie, i.status,
            c.id AS cliente_id, c.razon, d.departamento
     $base
     ORDER BY c.razon, i.modelo
     LIMIT :lim OFFSET :off"
);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset,    PDO::PARAM_INT);
$stmt->execute();
$equipos = $stmt->fetchAll();

$totalPaginas = (int)ceil($total / $porPagina);

function urlPag(int $p, string $q): string {
    $params = ['pagina' => $p];
    if ($q !== '') $params['q'] = $q;
    return '?' . http_build_query($params);
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        Impresoras <span class="badge bg-secondary ms-1"><?= $total ?></span>
    </h5>
</div>

<!-- Búsqueda -->
<form method="GET" class="mb-3">
    <div class="input-group search-bar">
        <input type="text" name="q" class="form-control"
               placeholder="Serie, modelo, marca o cliente…"
               value="<?= e($q) ?>" autofocus>
        <button class="btn btn-secondary" type="submit">
            <i class="bi bi-search"></i>
        </button>
        <?php if ($q): ?>
        <a href="?" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
        <?php endif; ?>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Marca / Modelo</th>
                    <th>No. de Serie</th>
                    <th>Status</th>
                    <th>Cliente</th>
                    <th>Departamento</th>
                    <th style="width:70px"></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($equipos)): ?>
                <tr>
                    <td colspan="6" class="text-center text-secondary py-4">
                        <i class="bi bi-printer fs-3 d-block mb-2"></i>
                        <?= $q ? 'Sin resultados para "' . e($q) . '"' : 'No hay equipos registrados.' ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($equipos as $eq): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($eq['marca'] . ' ' . $eq['modelo']) ?></div>
                    </td>
                    <td><code><?= e($eq['serie'] ?: '—') ?></code></td>
                    <td>
                        <span class="badge bg-secondary"><?= e($eq['status']) ?></span>
                    </td>
                    <td>
                        <a href="<?= BASE_PATH ?>/pages/clientes/detalle.php?id=<?= $eq['cliente_id'] ?>">
                            <?= e($eq['razon']) ?>
                        </a>
                    </td>
                    <td class="text-secondary small"><?= e($eq['departamento']) ?></td>
                    <td>
                        <a href="<?= BASE_PATH ?>/pages/impresoras/detalle.php?id=<?= $eq['id'] ?>"
                           class="btn btn-sm btn-outline-primary" title="Ver bitácora">
                            <i class="bi bi-journal-text"></i>
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
            <a class="page-link" href="<?= urlPag($pagina - 1, $q) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php for ($i = max(1,$pagina-2); $i <= min($totalPaginas,$pagina+2); $i++): ?>
        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
            <a class="page-link" href="<?= urlPag($i, $q) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= urlPag($pagina + 1, $q) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>
<p class="text-center text-secondary small mt-2">
    Mostrando <?= count($equipos) ?> de <?= $total ?> equipos
</p>
<?php endif; ?>

<?php require_once INCLUDES . '/footer.php'; ?>
