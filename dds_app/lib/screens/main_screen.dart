import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../services/api_service.dart';
import 'reportes/reportes_list_screen.dart';
import 'clientes/clientes_list_screen.dart';

class MainScreen extends StatefulWidget {
  final ApiService api;
  const MainScreen({super.key, required this.api});

  @override
  State<MainScreen> createState() => _MainScreenState();
}

class _MainScreenState extends State<MainScreen> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final auth   = context.read<AuthProvider>();
    final scheme = Theme.of(context).colorScheme;

    final screens = [
      ReportesListScreen(api: widget.api),
      ClientesListScreen(api: widget.api),
    ];

    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            Icon(Icons.print, color: scheme.primary, size: 22),
            const SizedBox(width: 8),
            const Text('DDS Portal', style: TextStyle(fontWeight: FontWeight.bold)),
          ],
        ),
        actions: [
          // Nombre y rol del usuario
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  auth.userName,
                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                ),
                Text(
                  auth.userRol.toUpperCase(),
                  style: TextStyle(
                    fontSize: 10,
                    color: scheme.primary,
                    fontWeight: FontWeight.bold,
                    letterSpacing: .5,
                  ),
                ),
              ],
            ),
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Cerrar sesión',
            onPressed: () async {
              final confirm = await showDialog<bool>(
                context: context,
                builder: (_) => AlertDialog(
                  title: const Text('Cerrar sesión'),
                  content: const Text('¿Seguro que quieres salir?'),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: const Text('Cancelar'),
                    ),
                    FilledButton(
                      onPressed: () => Navigator.pop(context, true),
                      child: const Text('Salir'),
                    ),
                  ],
                ),
              );
              if (confirm == true && context.mounted) {
                context.read<AuthProvider>().logout();
              }
            },
          ),
        ],
      ),

      body: IndexedStack(index: _index, children: screens),

      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.assignment_outlined),
            selectedIcon: Icon(Icons.assignment),
            label: 'Reportes',
          ),
          NavigationDestination(
            icon: Icon(Icons.people_outline),
            selectedIcon: Icon(Icons.people),
            label: 'Clientes',
          ),
        ],
      ),
    );
  }
}
