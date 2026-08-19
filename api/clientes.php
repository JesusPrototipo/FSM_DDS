<?php
// ============================================================
//  WEBDDS — API /api/clientes.php
//  Reemplaza: APIClientes.php, APIDepas.php, APIMaquinas.php,
//             ApiConsulta.php, guardar_datos.php, Insertar.php
//
//  Rutas:
//    GET    ?accion=lista                     → todos los clientes
//    GET    ?accion=buscar&q=texto            → búsqueda por nombre/tel
//    GET    ?accion=detalle&id=N              → cliente + deptos + equipos
//    POST   accion=crear_cliente             → insert clientes
//    POST   accion=crear_depto              → insert departamentos
//    POST   accion=crear_equipo             → insert impresoras
//    POST   accion=actualizar_cliente       → update clientes
//    POST   accion=eliminar_cliente         → delete clientes (solo admin)
// ============================================================

require_once dirname(__DIR__) . '/includes/init.php';
Auth::requerir();   // Toda la API requiere sesión activa

$pdo    = Database::get();
$accion = $_REQUEST['accion'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// ── GET ──────────────────────────────────────────────────────
if ($method === 'GET') {

    // Lista paginada de clientes
    if ($accion === 'lista') {
        $pagina   = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = 15;
        $offset   = ($pagina - 1) * $porPagina;

        $total = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT id, razon, reporto, telefono, horario
             FROM clientes
             ORDER BY razon ASC
             LIMIT :limite OFFSET :offset"
        );
        $stmt->bindValue(':limite',  $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset',  $offset,    PDO::PARAM_INT);
        $stmt->execute();

        jsonResponse([
            'data'       => $stmt->fetchAll(),
            'total'      => (int)$total,
            'pagina'     => $pagina,
            'por_pagina' => $porPagina,
            'paginas'    => (int)ceil($total / $porPagina),
        ]);
    }

    // Búsqueda por texto
    if ($accion === 'buscar') {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) jsonResponse(['data' => []]);

        $stmt = $pdo->prepare(
            "SELECT id, razon, reporto, telefono
             FROM clientes
             WHERE razon LIKE :q1 OR reporto LIKE :q2 OR telefono LIKE :q3
             ORDER BY razon ASC
             LIMIT 20"
        );
        $like = "%$q%";
        $stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like]);
        jsonResponse(['data' => $stmt->fetchAll()]);
    }

    // Detalle completo: cliente + departamentos + equipos
    if ($accion === 'detalle') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID inválido'], 400);

        // Datos del cliente
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $cliente = $stmt->fetch();
        if (!$cliente) jsonResponse(['error' => 'Cliente no encontrado'], 404);

        // Departamentos e impresoras en una sola consulta
        $stmt = $pdo->prepare(
            "SELECT
                d.id          AS depto_id,
                d.departamento,
                i.id          AS equipo_id,
                i.marca,
                i.modelo,
                i.serie,
                i.status
             FROM departamentos d
             LEFT JOIN impresoras i ON i.departamento_id = d.id
             WHERE d.cliente_id = :id
             ORDER BY d.departamento, i.modelo"
        );
        $stmt->execute([':id' => $id]);
        $filas = $stmt->fetchAll();

        // Agrupar por departamento
        $deptos = [];
        foreach ($filas as $f) {
            $did = $f['depto_id'];
            if (!isset($deptos[$did])) {
                $deptos[$did] = [
                    'id'          => $did,
                    'departamento'=> $f['departamento'],
                    'equipos'     => [],
                ];
            }
            if ($f['equipo_id']) {
                $deptos[$did]['equipos'][] = [
                    'id'     => $f['equipo_id'],
                    'marca'  => $f['marca'],
                    'modelo' => $f['modelo'],
                    'serie'  => $f['serie'],
                    'status' => $f['status'],
                ];
            }
        }

        // Historial de reportes del cliente (últimos 10)
        $stmt = $pdo->prepare(
            "SELECT r.id, r.fecha, r.falla, r.estatus,
                    d.departamento, i.modelo,
                    u.nombre AS tecnico_nombre
             FROM reportes_fallas r
             LEFT JOIN departamentos d ON r.id_departamento = d.id
             LEFT JOIN impresoras   i ON r.id_impresora    = i.id
             LEFT JOIN usuarios     u ON r.tecnico_id      = u.id
             WHERE r.id_cliente = :id
             ORDER BY r.id DESC
             LIMIT 10"
        );
        $stmt->execute([':id' => $id]);
        $reportes = $stmt->fetchAll();

        jsonResponse([
            'cliente'      => $cliente,
            'departamentos'=> array_values($deptos),
            'reportes'     => $reportes,
        ]);
    }

    jsonResponse(['error' => 'Acción no reconocida'], 400);
}

// ── POST ─────────────────────────────────────────────────────
if ($method === 'POST') {

    // ── Crear cliente ────────────────────────────────────────
    if ($accion === 'crear_cliente') {
        Auth::requerirRol([ROL_ADMIN, ROL_ADMINISTRATIVO, ROL_GERENCIA, ROL_VENDEDOR, ROL_TECNICO]);

        $campos = ['razon','reporto','direccion','ciudad','telefono','horario'];
        $datos  = [];
        foreach ($campos as $c) {
            $val = trim($_POST[$c] ?? '');
            if (in_array($c, ['razon','direccion']) && $val === '') {
                jsonResponse(['error' => "El campo '$c' es obligatorio."], 422);
            }
            $datos[$c] = $val;
        }

        // Verificar duplicado por dirección
        $check = $pdo->prepare("SELECT id FROM clientes WHERE direccion = :dir LIMIT 1");
        $check->execute([':dir' => $datos['direccion']]);
        if ($check->fetch()) {
            jsonResponse(['error' => 'Ya existe un cliente con esa dirección.'], 409);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO clientes (razon, reporto, direccion, ciudad, telefono, horario)
             VALUES (:razon, :reporto, :direccion, :ciudad, :telefono, :horario)"
        );
        $stmt->execute([
            ':razon'     => $datos['razon'],
            ':reporto'   => $datos['reporto'],
            ':direccion' => $datos['direccion'],
            ':ciudad'    => $datos['ciudad'],
            ':telefono'  => $datos['telefono'],
            ':horario'   => $datos['horario'],
        ]);
        $nuevoId = $pdo->lastInsertId();
        jsonResponse(['ok' => true, 'id' => (int)$nuevoId]);
    }

    // ── Crear departamento ───────────────────────────────────
    if ($accion === 'crear_depto') {
        Auth::requerirRol([ROL_ADMIN, ROL_ADMINISTRATIVO, ROL_GERENCIA, ROL_VENDEDOR, ROL_TECNICO]);

        $clienteId   = (int)($_POST['cliente_id']   ?? 0);
        $departamento = trim($_POST['departamento'] ?? '');

        if (!$clienteId || !$departamento) {
            jsonResponse(['error' => 'Datos incompletos.'], 422);
        }

        // Verificar que el cliente existe
        $check = $pdo->prepare("SELECT id FROM clientes WHERE id = :id");
        $check->execute([':id' => $clienteId]);
        if (!$check->fetch()) jsonResponse(['error' => 'Cliente no existe.'], 404);

        // Verificar que no exista el mismo depto en ese cliente
        $check2 = $pdo->prepare(
            "SELECT id FROM departamentos WHERE cliente_id = :cid AND departamento = :dep"
        );
        $check2->execute([':cid' => $clienteId, ':dep' => $departamento]);
        if ($check2->fetch()) {
            jsonResponse(['error' => 'Ese departamento ya existe para este cliente.'], 409);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO departamentos (cliente_id, departamento) VALUES (:cid, :dep)"
        );
        $stmt->execute([':cid' => $clienteId, ':dep' => $departamento]);
        jsonResponse(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
    }

    // ── Crear equipo / impresora ─────────────────────────────
    if ($accion === 'crear_equipo') {
        Auth::requerirRol([ROL_ADMIN, ROL_ADMINISTRATIVO, ROL_GERENCIA, ROL_VENDEDOR, ROL_TECNICO]);

        $deptoId   = (int)($_POST['departamento_id'] ?? 0);
        $clienteId = (int)($_POST['cliente_id']      ?? 0);
        $serie     = trim($_POST['serie']            ?? '');

        if (!$deptoId || !$clienteId) {
            jsonResponse(['error' => 'Datos incompletos.'], 422);
        }

        // Verificar serie duplicada (solo si se proporcionó)
        if ($serie !== '') {
            $check = $pdo->prepare("SELECT id FROM impresoras WHERE serie = :serie");
            $check->execute([':serie' => $serie]);
            if ($check->fetch()) {
                jsonResponse(['error' => 'Ya existe un equipo con ese número de serie.'], 409);
            }
        }

        $stmt = $pdo->prepare(
            "INSERT INTO impresoras
                (departamento_id, cliente_id, marca, modelo, serie, status)
             VALUES
                (:did, :cid, :marca, :modelo, :serie, :status)"
        );
        $stmt->execute([
            ':did'    => $deptoId,
            ':cid'    => $clienteId,
            ':marca'  => trim($_POST['marca']   ?? ''),
            ':modelo' => trim($_POST['modelo']  ?? ''),
            ':serie'  => $serie ?: null,
            ':status' => trim($_POST['status']  ?? 'Renta'),
        ]);
        jsonResponse(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
    }

    // ── Actualizar departamento ─────────────────────────────
    if ($accion === 'actualizar_depto') {
        Auth::requerirRol([ROL_ADMIN, ROL_ADMINISTRATIVO, ROL_GERENCIA, ROL_VENDEDOR, ROL_TECNICO]);

        $id           = (int)($_POST['id']           ?? 0);
        $departamento = trim($_POST['departamento']  ?? '');

        if (!$id || !$departamento) {
            jsonResponse(['error' => 'El nombre del departamento es obligatorio.'], 422);
        }

        // Verificar que no exista el mismo nombre en el mismo cliente
        $check = $pdo->prepare(
            "SELECT id FROM departamentos
             WHERE cliente_id = (SELECT cliente_id FROM departamentos WHERE id = :id)
             AND departamento = :dep
             AND id != :id2"
        );
        $check->execute([':id' => $id, ':dep' => $departamento, ':id2' => $id]);
        if ($check->fetch()) {
            jsonResponse(['error' => 'Ya existe un departamento con ese nombre en este cliente.'], 409);
        }

        $stmt = $pdo->prepare(
            "UPDATE departamentos SET departamento = :dep WHERE id = :id"
        );
        $stmt->execute([':dep' => $departamento, ':id' => $id]);
        jsonResponse(['ok' => true]);
    }

    // ── Actualizar cliente ───────────────────────────────────
    if ($accion === 'actualizar_cliente') {
        Auth::requerirRol([ROL_ADMIN, ROL_ADMINISTRATIVO, ROL_GERENCIA, ROL_VENDEDOR, ROL_TECNICO]);

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID inválido.'], 400);

        $stmt = $pdo->prepare(
            "UPDATE clientes
             SET razon=:razon, reporto=:reporto, direccion=:direccion, ciudad=:ciudad,
                 telefono=:telefono, horario=:horario
             WHERE id=:id"
        );
        $stmt->execute([
            ':razon'     => trim($_POST['razon']     ?? ''),
            ':reporto'   => trim($_POST['reporto']   ?? ''),
            ':direccion' => trim($_POST['direccion'] ?? ''),
            ':ciudad'    => trim($_POST['ciudad']    ?? ''),
            ':telefono'  => trim($_POST['telefono']  ?? ''),
            ':horario'   => trim($_POST['horario']   ?? ''),
            ':id'        => $id,
        ]);
        jsonResponse(['ok' => true]);
    }

    // ── Eliminar cliente (solo admin) ────────────────────────
    if ($accion === 'eliminar_cliente') {
        Auth::requerirRol([ROL_ADMIN]);

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID inválido.'], 400);

        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        jsonResponse(['ok' => true]);
    }

    jsonResponse(['error' => 'Acción no reconocida'], 400);
}

jsonResponse(['error' => 'Método no permitido'], 405);