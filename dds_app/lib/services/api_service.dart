import 'package:dio/dio.dart';
import 'package:dio_cookie_manager/dio_cookie_manager.dart';
import 'package:cookie_jar/cookie_jar.dart';
import 'package:path_provider/path_provider.dart';
import '../config/app_config.dart';

class ApiException implements Exception {
  final String message;
  final int? statusCode;
  ApiException(this.message, {this.statusCode});
  @override
  String toString() => message;
}

class ApiService {
  late final Dio _dio;
  late final PersistCookieJar _cookieJar;

  ApiService._();

  static Future<ApiService> create() async {
    final s = ApiService._();
    await s._init();
    return s;
  }

  Future<void> _init() async {
    final dir = await getApplicationDocumentsDirectory();
    _cookieJar = PersistCookieJar(
      ignoreExpires: true,
      storage: FileStorage('${dir.path}/.dds_cookies/'),
    );

    _dio = Dio(BaseOptions(
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 20),
      headers: {'Accept': 'application/json'},
      contentType: Headers.formUrlEncodedContentType,
      validateStatus: (s) => s != null && s < 600,
    ));

    _dio.interceptors.add(CookieManager(_cookieJar));
  }

  Future<void> clearCookies() async => _cookieJar.deleteAll();

  // ── Helper response ────────────────────────────────────────
  Map<String, dynamic> _map(Response res) {
    if (res.statusCode == 401) {
      throw ApiException('Sesión expirada.', statusCode: 401);
    }
    if (res.data is Map<String, dynamic>) {
      final d = res.data as Map<String, dynamic>;
      if (d.containsKey('error')) {
        throw ApiException(d['error'].toString(), statusCode: res.statusCode);
      }
      return d;
    }
    throw ApiException('Respuesta inesperada del servidor.');
  }

  List<dynamic> _list(Response res) {
    if (res.statusCode == 401) throw ApiException('Sesión expirada.', statusCode: 401);
    if (res.data is List) return res.data as List<dynamic>;
    if (res.data is Map && (res.data as Map).containsKey('error')) {
      throw ApiException((res.data as Map)['error'].toString());
    }
    throw ApiException('Respuesta inesperada.');
  }

  // ── AUTH ──────────────────────────────────────────────────
  Future<Map<String, dynamic>> login(String usuario, String password) async {
    final res = await _dio.post(
      '${AppConfig.apiUrl}/auth.php',
      data: {'usuario': usuario, 'password': password},
    );
    return _map(res);
  }

  Future<Map<String, dynamic>> checkSession() async {
    final res = await _dio.get('${AppConfig.apiUrl}/auth.php');
    return _map(res);
  }

  Future<void> logout() async {
    await _dio.delete('${AppConfig.apiUrl}/auth.php');
    await clearCookies();
  }

  // ── REPORTES ──────────────────────────────────────────────
  Future<Map<String, dynamic>> getReportes({
    int pagina = 1,
    String estatus = '',
    String q = '',
  }) async {
    final res = await _dio.get(
      '${AppConfig.apiUrl}/reportes.php',
      queryParameters: {
        'accion': 'lista',
        'pagina': pagina,
        if (estatus.isNotEmpty) 'estatus': estatus,
        if (q.isNotEmpty) 'q': q,
      },
    );
    return _map(res);
  }

  Future<Map<String, dynamic>> getReporteDetalle(int id) async {
    final res = await _dio.get(
      '${AppConfig.apiUrl}/reportes.php',
      queryParameters: {'accion': 'detalle', 'id': id},
    );
    return _map(res);
  }

  Future<int> crearReporte({
    required int clienteId,
    required int departamentoId,
    required int equipoId,
    required String falla,
    int? tecnicoId,
  }) async {
    final res = await _dio.post(
      '${AppConfig.apiUrl}/reportes.php',
      data: {
        'accion': 'crear',
        'cliente_id': clienteId,
        'departamento_id': departamentoId,
        'equipo_id': equipoId,
        'falla': falla,
        'fecha': DateTime.now().toIso8601String().split('T')[0],
        if (tecnicoId != null) 'tecnico_id': tecnicoId,
      },
    );
    return _map(res)['id'] as int;
  }

  Future<void> cambiarEstatus(int id, String estatus, {String nota = ''}) async {
    final res = await _dio.post(
      '${AppConfig.apiUrl}/reportes.php',
      data: {'accion': 'cambiar_estatus', 'id': id, 'estatus': estatus, 'nota': nota},
    );
    _map(res);
  }

  Future<void> agregarNota(int id, String nota) async {
    final res = await _dio.post(
      '${AppConfig.apiUrl}/reportes.php',
      data: {'accion': 'agregar_nota', 'id': id, 'nota': nota},
    );
    _map(res);
  }

  Future<List<dynamic>> getTecnicos() async {
    final res = await _dio.get(
      '${AppConfig.apiUrl}/reportes.php',
      queryParameters: {'accion': 'tecnicos'},
    );
    return _list(res);
  }

  // ── CLIENTES ──────────────────────────────────────────────
  Future<Map<String, dynamic>> getClientes({int pagina = 1, String q = ''}) async {
    final res = await _dio.get(
      '${AppConfig.apiUrl}/clientes.php',
      queryParameters: {
        'accion': 'lista',
        'pagina': pagina,
        if (q.isNotEmpty) 'q': q,
      },
    );
    return _map(res);
  }

  Future<List<dynamic>> buscarClientes(String q) async {
    final res = await _dio.get(
      '${AppConfig.apiUrl}/clientes.php',
      queryParameters: {'accion': 'buscar', 'q': q},
    );
    return (_map(res)['data'] as List?) ?? [];
  }

  Future<Map<String, dynamic>> getClienteDetalle(int id) async {
    final res = await _dio.get(
      '${AppConfig.apiUrl}/clientes.php',
      queryParameters: {'accion': 'detalle', 'id': id},
    );
    return _map(res);
  }

  Future<int> crearCliente({
    required String razon,
    required String reporto,
    required String direccion,
    required String telefono,
    required String horario,
  }) async {
    final res = await _dio.post(
      '${AppConfig.apiUrl}/clientes.php',
      data: {
        'accion': 'crear_cliente',
        'razon': razon, 'reporto': reporto,
        'direccion': direccion, 'telefono': telefono, 'horario': horario,
      },
    );
    return _map(res)['id'] as int;
  }

  Future<void> actualizarCliente({
    required int id,
    required String razon,
    required String reporto,
    required String direccion,
    required String telefono,
    required String horario,
  }) async {
    final res = await _dio.post(
      '${AppConfig.apiUrl}/clientes.php',
      data: {
        'accion': 'actualizar_cliente',
        'id': id, 'razon': razon, 'reporto': reporto,
        'direccion': direccion, 'telefono': telefono, 'horario': horario,
      },
    );
    _map(res);
  }

  Future<int> crearDepto(int clienteId, String nombre) async {
    final res = await _dio.post(
      '${AppConfig.apiUrl}/clientes.php',
      data: {'accion': 'crear_depto', 'cliente_id': clienteId, 'departamento': nombre},
    );
    return _map(res)['id'] as int;
  }

  Future<void> actualizarDepto(int id, String nombre) async {
    final res = await _dio.post(
      '${AppConfig.apiUrl}/clientes.php',
      data: {'accion': 'actualizar_depto', 'id': id, 'departamento': nombre},
    );
    _map(res);
  }

  Future<int> crearEquipo({
    required int departamentoId,
    required int clienteId,
    required String marca,
    required String modelo,
    required String serie,
    required String status,
  }) async {
    final res = await _dio.post(
      '${AppConfig.apiUrl}/clientes.php',
      data: {
        'accion': 'crear_equipo',
        'departamento_id': departamentoId, 'cliente_id': clienteId,
        'marca': marca, 'modelo': modelo, 'serie': serie, 'status': status,
      },
    );
    return _map(res)['id'] as int;
  }
}
