<?php
// ============================================================
//  WEBDDS — api/busqueda.php
//  Búsqueda global: clientes, impresoras y reportes en una sola llamada
//  GET ?q=texto  → JSON con resultados agrupados
// ============================================================
require_once dirname(__DIR__) . '/includes/init.php';
Auth::requerir();

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    jsonResponse(['clientes' => [], 'equipos' => [], 'reportes' => []]);
}

$pdo  = Database::get();
$like = "%$q%";

// Clientes
$stmt = $pdo->prepare(
    "SELECT id, razon, reporto, telefono
     FROM clientes
     WHERE razon LIKE :q1 OR reporto LIKE :q2 OR telefono LIKE :q3
     LIMIT 5"
);
$stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like]);
$clientes = $stmt->fetchAll();

// Equipos
$stmt = $pdo->prepare(
    "SELECT i.id, i.marca, i.modelo, i.serie, c.razon
     FROM impresoras i
     INNER JOIN clientes c ON i.cliente_id = c.id
     WHERE i.serie LIKE :q1 OR i.modelo LIKE :q2 OR i.marca LIKE :q3
     LIMIT 5"
);
$stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like]);
$equipos = $stmt->fetchAll();

// Reportes
$stmt = $pdo->prepare(
    "SELECT r.id, r.falla, r.estatus, r.fecha, c.razon
     FROM reportes_fallas r
     INNER JOIN clientes c ON r.id_cliente = c.id
     WHERE r.falla LIKE :q1 OR c.razon LIKE :q2
     ORDER BY r.id DESC
     LIMIT 5"
);
$stmt->execute([':q1' => $like, ':q2' => $like]);
$reportes = $stmt->fetchAll();

jsonResponse([
    'clientes' => $clientes,
    'equipos'  => $equipos,
    'reportes' => $reportes,
]);
