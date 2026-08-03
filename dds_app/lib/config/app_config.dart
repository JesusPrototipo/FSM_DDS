// ============================================================
//  DDS App — Configuración central
//  ANTES DE COMPILAR: cambia serverIp por la IP de tu PC
//
//  Cómo encontrar tu IP en Windows:
//  1. Abre CMD
//  2. Escribe: ipconfig
//  3. Busca "Dirección IPv4" bajo tu adaptador WiFi
//     Ejemplo: 192.168.1.105
// ============================================================

class AppConfig {
  // ▼▼▼ CAMBIA ESTO ▼▼▼
  static const String serverIp    = '192.168.1.20';
  static const String serverPort  = '8012';
  static const String projectPath = '/DDS2';
  // ▲▲▲ CAMBIA ESTO ▲▲▲

  static String get baseUrl => 'http://$serverIp:$serverPort$projectPath';
  static String get apiUrl  => '$baseUrl/api';
}
