import 'package:flutter/foundation.dart';
import '../services/api_service.dart';

class AuthProvider extends ChangeNotifier {
  final ApiService _api;

  bool _isLoading = true;
  bool _isLoggedIn = false;
  int?    _userId;
  String? _userName;
  String? _userRol;

  bool   get isLoading  => _isLoading;
  bool   get isLoggedIn => _isLoggedIn;
  int?   get userId     => _userId;
  String get userName   => _userName ?? '';
  String get userRol    => _userRol  ?? '';

  AuthProvider(this._api) {
    _checkSession();
  }

  Future<void> _checkSession() async {
    try {
      final data = await _api.checkSession();
      _setUser(data);
    } catch (_) {
      _isLoggedIn = false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Devuelve null si OK, o el mensaje de error
  Future<String?> login(String usuario, String password) async {
    try {
      final data = await _api.login(usuario, password);
      _setUser(data);
      notifyListeners();
      return null;
    } on ApiException catch (e) {
      return e.message;
    } catch (_) {
      return 'Error de conexión con el servidor.';
    }
  }

  Future<void> logout() async {
    try { await _api.logout(); } catch (_) {}
    _isLoggedIn = false;
    _userId = null;
    _userName = null;
    _userRol = null;
    notifyListeners();
  }

  void _setUser(Map<String, dynamic> data) {
    _isLoggedIn = true;
    _userId   = data['id']     as int?;
    _userName = data['nombre'] as String?;
    _userRol  = data['rol']    as String?;
  }
}
