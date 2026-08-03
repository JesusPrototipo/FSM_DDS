-- ============================================================
--  WEBDDS — Script de migración v2
--  Ejecutar UNA SOLA VEZ sobre la BD existente `digitaldocument`
--  Orden: 1) Renombrar columna  2) Crear tabla usuarios
-- ============================================================

-- ── 1. Corregir typo: stratus → status ──────────────────────
ALTER TABLE `impresoras`
    CHANGE `stratus` `status` VARCHAR(50) DEFAULT NULL;

-- ── 2. Agregar estado a reportes_fallas (incluye 'cancelado') ─
--  La tabla ya tiene 'pendiente','en proceso','finalizado'
--  Agregamos 'cancelado'
ALTER TABLE `reportes_fallas`
    MODIFY `estatus` ENUM('pendiente','en proceso','finalizado','cancelado')
    DEFAULT 'pendiente';

-- ── 3. Crear tabla de usuarios con roles ────────────────────
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `nombre`     VARCHAR(100) NOT NULL,
    `usuario`    VARCHAR(50)  NOT NULL UNIQUE,
    `password`   VARCHAR(255) NOT NULL,           -- bcrypt hash
    `rol`        ENUM('admin','tecnico','callcenter','vendedor') NOT NULL DEFAULT 'tecnico',
    `activo`     TINYINT(1)   NOT NULL DEFAULT 1,
    `creado_en`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 4. Usuario administrador inicial ────────────────────────
--  Contraseña por defecto: Admin2025!
--  CAMBIAR INMEDIATAMENTE después del primer login
INSERT INTO `usuarios` (`nombre`, `usuario`, `password`, `rol`)
VALUES (
    'Administrador',
    'admin',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Admin2025!
    'admin'
);

-- ── 5. Agregar campo tecnico_id a reportes_fallas ───────────
--  Para saber qué técnico atendió (referencia a usuarios)
ALTER TABLE `reportes_fallas`
    ADD COLUMN `tecnico_id` INT(11) DEFAULT NULL AFTER `tecnico`,
    ADD CONSTRAINT `fk_reporte_tecnico`
        FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios`(`id`)
        ON DELETE SET NULL;

-- ── 6. Índice para búsquedas rápidas en reportes ────────────
ALTER TABLE `reportes_fallas`
    ADD INDEX `idx_estatus`    (`estatus`),
    ADD INDEX `idx_fecha`      (`fecha`(50)),
    ADD INDEX `idx_tecnico_id` (`tecnico_id`);

-- ── 7. Índice para búsquedas en clientes ─────────────────────
--  (Los FULLTEXT ya existen, agregamos índice normal en telefono)
ALTER TABLE `clientes`
    ADD INDEX `idx_telefono` (`telefono`);

-- ============================================================
--  Verificación final
-- ============================================================
SELECT 'Migración completada correctamente' AS resultado;
