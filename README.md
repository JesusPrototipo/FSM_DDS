# WEBDDS — Portal de Servicio Técnico
**Digital Document Services**

---

## Requisitos
- PHP 8.1 o superior
- MySQL / MariaDB 10.4+
- Servidor web: Apache (XAMPP/WAMP) o Nginx
- Extensión PDO habilitada (viene por defecto en PHP moderno)

---

## Instalación paso a paso

### 1. Colocar archivos
Copia la carpeta `WEBDDS` dentro de tu servidor web:
- **XAMPP Windows:** `C:/xampp/htdocs/WEBDDS`
- **WAMP Windows:** `C:/wamp64/www/WEBDDS`
- **Linux/Mac:** `/var/www/html/WEBDDS`

### 2. Crear la base de datos
1. Abre phpMyAdmin → crea la BD `digitaldocument`
2. Importa el archivo original `digitaldocument.sql`
3. Luego ejecuta `migration_v2.sql` (corrige `stratus→status` y crea tabla `usuarios`)

> ⚠️ El orden importa: primero el SQL original, luego la migración.

### 3. Configurar la conexión
Edita **únicamente** `includes/config.php`:

```php
define('DB_HOST',  'localhost');
define('DB_NAME',  'digitaldocument');
define('DB_USER',  'root');
define('DB_PASS',  '');           // Tu contraseña
define('APP_URL',  'http://localhost/WEBDDS');
define('BASE_PATH','/WEBDDS');
```

### 4. Primer acceso
- Abre: `http://localhost/WEBDDS/login.php`
- Usuario: `admin`
- Contraseña: `Admin2025!`
- **Cambia la contraseña inmediatamente** desde Gestión de Usuarios

---

## Estructura de carpetas

```
WEBDDS/
├── includes/           ← Núcleo del sistema (no mover)
│   ├── config.php      ← Configuración central (editar aquí)
│   ├── Database.php    ← Conexión PDO única
│   ├── Auth.php        ← Sesiones y roles
│   ├── init.php        ← Autoload
│   ├── header.php      ← Navbar reutilizable
│   └── footer.php      ← Footer reutilizable
│
├── api/                ← APIs internas (no acceder directo)
│   ├── clientes.php
│   └── reportes.php
│
├── pages/
│   ├── clientes/
│   │   ├── lista.php   ← Tabla con búsqueda y paginación
│   │   ├── nuevo.php   ← Wizard 3 pasos
│   │   └── detalle.php ← Info + equipos + historial
│   │
│   └── reportes/
│       ├── lista.php   ← Filtros por estatus/técnico
│       ├── nuevo.php   ← Crear reporte con autocompletado
│       ├── detalle.php ← Gestión + bitácora + estatus
│       └── pdf.php     ← Reporte imprimible (sin librerías)
│
├── assets/
│   ├── css/app.css
│   └── js/app.js
│
├── index.php           ← Dashboard (requiere login)
├── login.php
├── logout.php
└── migration_v2.sql    ← Ejecutar UNA sola vez
```

---

## Roles de usuario

| Rol        | Puede hacer                                              |
|------------|----------------------------------------------------------|
| admin      | Todo: usuarios, clientes, reportes, cambiar estatus      |
| tecnico    | Ver/crear reportes, cambiar estatus, agregar notas       |
| callcenter | Crear reportes, agregar clientes, ver clientes           |
| vendedor   | Agregar clientes y equipos, ver clientes                 |

---

## Gestión de usuarios
Por ahora se crean directamente en la BD. El hash de contraseña
se genera con PHP:

```php
echo password_hash('MiContrasena123!', PASSWORD_BCRYPT);
```

Pega el resultado en el campo `password` de la tabla `usuarios`.

---

## PDF
El sistema genera el PDF como página HTML optimizada para impresión.
- Abre el reporte → botón **"Ver PDF"**
- En el navegador: **Ctrl+P → Guardar como PDF**
- No requiere FPDF ni ninguna librería externa

---

## Fases completadas

| Fase | Contenido |
|------|-----------|
| 2    | Cimientos: config, DB, Auth, login, dashboard |
| 3    | Módulo Clientes completo (CRUD + wizard + detalle) |
| 4    | Módulo Reportes completo (lista + nuevo + detalle + PDF) |

---

© 2025 Digital Document Services
