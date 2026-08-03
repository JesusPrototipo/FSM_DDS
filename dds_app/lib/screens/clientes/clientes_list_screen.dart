import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../models/models.dart';
import 'cliente_detalle_screen.dart';
import 'cliente_form_screen.dart';

class ClientesListScreen extends StatefulWidget {
  final ApiService api;
  const ClientesListScreen({super.key, required this.api});
  @override
  State<ClientesListScreen> createState() => _ClientesListScreenState();
}

class _ClientesListScreenState extends State<ClientesListScreen> {
  final _searchC = TextEditingController();
  List<Cliente> _clientes = [];
  bool _loading = true;
  String? _error;
  int _pagina = 1;
  int _totalPaginas = 1;
  int _total = 0;

  @override
  void initState() { super.initState(); _cargar(); }
  @override
  void dispose() { _searchC.dispose(); super.dispose(); }

  Future<void> _cargar({bool reset = true}) async {
    if (reset) _pagina = 1;
    setState(() { _loading = true; _error = null; });
    try {
      final data = await widget.api.getClientes(
        pagina: _pagina,
        q: _searchC.text.trim(),
      );
      final lista = ((data['data'] as List?) ?? [])
          .map((e) => Cliente.fromMap(e as Map<String, dynamic>))
          .toList();
      setState(() {
        if (reset) {
          _clientes = lista;
        } else {
          _clientes.addAll(lista);
        }
        _total        = data['total']   as int? ?? 0;
        _totalPaginas = data['paginas'] as int? ?? 1;
      });
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Error de conexión.');
    } finally {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: SearchBar(
              controller: _searchC,
              hintText: 'Buscar cliente…',
              leading: const Icon(Icons.search),
              trailing: _searchC.text.isNotEmpty
                  ? [IconButton(
                      icon: const Icon(Icons.clear),
                      onPressed: () { _searchC.clear(); _cargar(); },
                    )]
                  : null,
              onSubmitted: (_) => _cargar(),
            ),
          ),
          if (_total > 0)
            Padding(
              padding: const EdgeInsets.only(left: 16, bottom: 4),
              child: Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  '$_total clientes',
                  style: const TextStyle(color: Colors.grey, fontSize: 12),
                ),
              ),
            ),
          Expanded(
            child: _loading && _clientes.isEmpty
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? Center(child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(_error!),
                          const SizedBox(height: 12),
                          FilledButton(onPressed: _cargar, child: const Text('Reintentar')),
                        ],
                      ))
                    : _clientes.isEmpty
                        ? const Center(child: Text('No hay clientes', style: TextStyle(color: Colors.grey)))
                        : RefreshIndicator(
                            onRefresh: _cargar,
                            child: ListView.builder(
                              padding: const EdgeInsets.symmetric(horizontal: 12),
                              itemCount: _clientes.length + (_totalPaginas > _pagina ? 1 : 0),
                              itemBuilder: (ctx, i) {
                                if (i == _clientes.length) {
                                  return Center(
                                    child: TextButton.icon(
                                      icon: const Icon(Icons.expand_more),
                                      label: const Text('Cargar más'),
                                      onPressed: () { _pagina++; _cargar(reset: false); },
                                    ),
                                  );
                                }
                                final c = _clientes[i];
                                return Card(
                                  margin: const EdgeInsets.only(bottom: 8),
                                  child: ListTile(
                                    leading: CircleAvatar(
                                      backgroundColor: Theme.of(context).colorScheme.primaryContainer,
                                      child: Text(
                                        c.razon.isNotEmpty ? c.razon[0].toUpperCase() : '?',
                                        style: TextStyle(
                                          color: Theme.of(context).colorScheme.primary,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ),
                                    title: Text(c.razon, style: const TextStyle(fontWeight: FontWeight.w600)),
                                    subtitle: Text(c.reporto.isNotEmpty ? c.reporto : c.telefono),
                                    trailing: const Icon(Icons.chevron_right),
                                    onTap: () async {
                                      await Navigator.push(
                                        context,
                                        MaterialPageRoute(
                                          builder: (_) => ClienteDetalleScreen(api: widget.api, id: c.id),
                                        ),
                                      );
                                      _cargar();
                                    },
                                  ),
                                );
                              },
                            ),
                          ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        icon: const Icon(Icons.person_add),
        label: const Text('Nuevo Cliente'),
        onPressed: () async {
          await Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => ClienteFormScreen(api: widget.api)),
          );
          _cargar();
        },
      ),
    );
  }
}
