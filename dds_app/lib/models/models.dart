// ── Reporte ───────────────────────────────────────────────────
class Reporte {
  final int id;
  final String fecha;
  final String falla;
  final String estatus;
  final String razon;
  final String departamento;
  final String marca;
  final String modelo;
  final String serie;
  final String? tecnicoNombre;
  final String? observaciones;
  final String? contacto;
  final String? telefono;
  final String? direccion;
  final String? horario;

  Reporte({
    required this.id, required this.fecha, required this.falla,
    required this.estatus, required this.razon, required this.departamento,
    required this.marca, required this.modelo, required this.serie,
    this.tecnicoNombre, this.observaciones,
    this.contacto, this.telefono, this.direccion, this.horario,
  });

  factory Reporte.fromMap(Map<String, dynamic> m) => Reporte(
    id: int.tryParse(m['id'].toString()) ?? 0,
    fecha: m['fecha']?.toString() ?? '',
    falla: m['falla']?.toString() ?? '',
    estatus: m['estatus']?.toString() ?? 'pendiente',
    razon: m['razon']?.toString() ?? '',
    departamento: m['departamento']?.toString() ?? '',
    marca: m['marca']?.toString() ?? '',
    modelo: m['modelo']?.toString() ?? '',
    serie: m['serie']?.toString() ?? '',
    tecnicoNombre: m['tecnico_nombre']?.toString(),
    observaciones: m['observaciones']?.toString(),
    contacto: m['reporto']?.toString() ?? m['contacto']?.toString(),
    telefono: m['telefono']?.toString(),
    direccion: m['direccion']?.toString(),
    horario: m['horario']?.toString(),
  );
}

// ── Cliente ───────────────────────────────────────────────────
class Cliente {
  final int id;
  final String razon;
  final String reporto;
  final String direccion;
  final String telefono;
  final String horario;

  Cliente({
    required this.id, required this.razon, required this.reporto,
    required this.direccion, required this.telefono, required this.horario,
  });

  factory Cliente.fromMap(Map<String, dynamic> m) => Cliente(
    id: int.tryParse(m['id'].toString()) ?? 0,
    razon: m['razon']?.toString() ?? '',
    reporto: m['reporto']?.toString() ?? '',
    direccion: m['direccion']?.toString() ?? '',
    telefono: m['telefono']?.toString() ?? '',
    horario: m['horario']?.toString() ?? '',
  );
}

// ── Equipo ────────────────────────────────────────────────────
class Equipo {
  final int id;
  final String marca;
  final String modelo;
  final String serie;
  final String status;

  Equipo({
    required this.id, required this.marca, required this.modelo,
    required this.serie, required this.status,
  });

  factory Equipo.fromMap(Map<String, dynamic> m) => Equipo(
    id: int.tryParse(m['id'].toString()) ?? 0,
    marca: m['marca']?.toString() ?? '',
    modelo: m['modelo']?.toString() ?? '',
    serie: m['serie']?.toString() ?? '',
    status: m['status']?.toString() ?? '',
  );

  String get nombre => '$marca $modelo';
}

// ── Departamento ──────────────────────────────────────────────
class Departamento {
  final int id;
  final String nombre;
  final List<Equipo> equipos;

  Departamento({required this.id, required this.nombre, required this.equipos});

  factory Departamento.fromMap(Map<String, dynamic> m) => Departamento(
    id: int.tryParse(m['id'].toString()) ?? 0,
    nombre: m['departamento']?.toString() ?? '',
    equipos: ((m['equipos'] as List?) ?? [])
        .map((e) => Equipo.fromMap(e as Map<String, dynamic>))
        .toList(),
  );
}

// ── Usuario / Técnico ─────────────────────────────────────────
class Tecnico {
  final int id;
  final String nombre;
  final String rol;

  Tecnico({required this.id, required this.nombre, required this.rol});

  factory Tecnico.fromMap(Map<String, dynamic> m) => Tecnico(
    id: int.tryParse(m['id'].toString()) ?? 0,
    nombre: m['nombre']?.toString() ?? '',
    rol: m['rol']?.toString() ?? '',
  );
}
