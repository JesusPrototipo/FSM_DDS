import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../models/models.dart';
import '../../widgets/status_badge.dart';
import 'cliente_form_screen.dart';
import 'agregar_depto_screen.dart';
import '../reportes/nuevo_reporte_screen.dart';

class ClienteDetalleScreen extends StatefulWidget {
  final ApiService api;
  final int id;
  const ClienteDetalleScreen({super.key, required this.api, required this.id});
  @override
  State<ClienteDetalleScreen> createState() => _ClienteDetalleScreenState();
}

class _ClienteDetalleScreenState extends State<ClienteDetalleScreen> {
  Cliente? _cliente;
  List<Departamento> _deptos  = [];
  List<dynamic>      _reportes = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() { super.initState(); _cargar(); }

  Future<void> _cargar() async {
    setState(() { _loading = true; _error = null; });
    try {
      final data = await widget.api.getClienteDetalle(widget.id);
      setState(() {
        _cliente  = Cliente.fromMap(data['cliente'] as Map<String, dynamic>);
        _deptos   = ((data['departamentos'] as List?) ?? [])
            .map((d) => Departamento.fromMap(d as Map<String, dynamic>))
            .toList();
        _reportes = (data['reportes'] as List?) ?? [];
      });
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Error de conexión.');
    } finally {
      setState(() => _loading = false);
    }
  }

  Future<void> _editarDepto(Departamento d) async {
    final ctrl = TextEditingController(text: d.nombre);
    final ok = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Editar Departamento'),
        content: TextField(
          controller: ctrl,
          decoration: const InputDecoration(labelText: 'Nombre del departamento'),
          autofocus: true,
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancelar')),
          FilledButton(onPressed: () => Navigator.pop(context, true),  child: const Text('Guardar')),
        ],
      ),
    );
    if (ok != true || ctrl.text.trim().isEmpty || !mounted) return;
    try {
      await widget.api.actualizarDepto(d.id, ctrl.text.trim());
      _cargar();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.message), backgroundColor: Colors.red),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_cliente?.razon ?? 'Cliente'),
        actions: [
          if (_cliente != null)
            IconButton(
              icon: const Icon(Icons.edit),
              tooltip: 'Editar cliente',
              onPressed: () async {
                await Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => ClienteFormScreen(api: widget.api, cliente: _cliente),
                  ),
                );
                _cargar();
              },
            ),
          IconButton(icon: const Icon(Icons.refresh), onPressed: _cargar),
        ],
      ),
      body: _loading
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
              : RefreshIndicator(
                  onRefresh: _cargar,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      // Info del cliente
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _lbl(context, 'Información del Cliente'),
                              const SizedBox(height: 8),
                              _row(Icons.business,   _cliente!.razon),
                              _row(Icons.person,      _cliente!.reporto),
                              _row(Icons.location_on, _cliente!.direccion),
                              _row(Icons.phone,       _cliente!.telefono),
                              _row(Icons.schedule,    _cliente!.horario),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),

                      // Departamentos y equipos
                      Card(
                        child: Column(
                          children: [
                            ListTile(
                              title: Text('Departamentos y Equipos',
                                  style: _lblStyle(context)),
                              trailing: IconButton(
                                icon: const Icon(Icons.add_circle_outline),
                                tooltip: 'Agregar departamento o equipo',
                                onPressed: () async {
                                  await Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (_) => AgregarDeptoScreen(
                                        api: widget.api,
                                        clienteId: widget.id,
                                        deptos: _deptos,
                                      ),
                                    ),
                                  );
                                  _cargar();
                                },
                              ),
                            ),
                            if (_deptos.isEmpty)
                              const Padding(
                                padding: EdgeInsets.all(16),
                                child: Text('Sin departamentos.',
                                    style: TextStyle(color: Colors.grey)),
                              )
                            else
                              ..._deptos.map((d) => ExpansionTile(
                                leading: const Icon(Icons.business),
                                title: Text(d.nombre),
                                subtitle: Text(
                                  '${d.equipos.length} equipo${d.equipos.length != 1 ? 's' : ''}',
                                  style: const TextStyle(fontSize: 12),
                                ),
                                trailing: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    IconButton(
                                      icon: const Icon(Icons.edit, size: 18),
                                      onPressed: () => _editarDepto(d),
                                    ),
                                    const Icon(Icons.expand_more),
                                  ],
                                ),
                                children: d.equipos.isEmpty
                                    ? [const ListTile(
                                        dense: true,
                                        title: Text('Sin equipos',
                                            style: TextStyle(color: Colors.grey, fontSize: 13)),
                                      )]
                                    : d.equipos.map((eq) => ListTile(
                                        dense: true,
                                        leading: const Icon(Icons.print, size: 18),
                                        title: Text(eq.nombre),
                                        subtitle: Text(
                                          eq.serie.isNotEmpty ? 'Serie: ${eq.serie}' : eq.status,
                                          style: const TextStyle(fontSize: 12),
                                        ),
                                        trailing: Chip(
                                          label: Text(eq.status, style: const TextStyle(fontSize: 11)),
                                          padding: EdgeInsets.zero,
                                        ),
                                        onTap: () async {
                                          await Navigator.push(
                                            context,
                                            MaterialPageRoute(
                                              builder: (_) => NuevoReporteScreen(api: widget.api),
                                            ),
                                          );
                                        },
                                      )).toList(),
                              )),
                          ],
                        ),
                      ),
                      const SizedBox(height: 12),

                      // Historial de reportes
                      if (_reportes.isNotEmpty) ...[
                        Card(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Padding(
                                padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                                child: Text('Últimos Reportes', style: _lblStyle(context)),
                              ),
                              ..._reportes.take(5).map((r) {
                                final m = r as Map<String, dynamic>;
                                return ListTile(
                                  dense: true,
                                  leading: StatusBadge(m['estatus']?.toString() ?? ''),
                                  title: Text(
                                    m['falla']?.toString() ?? '',
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(fontSize: 13),
                                  ),
                                  subtitle: Text(
                                    '${m['modelo'] ?? ''} · ${m['fecha'] ?? ''}',
                                    style: const TextStyle(fontSize: 12),
                                  ),
                                );
                              }),
                            ],
                          ),
                        ),
                      ],
                    ],
                  ),
                ),

      floatingActionButton: _cliente == null ? null : FloatingActionButton.extended(
        icon: const Icon(Icons.add),
        label: const Text('Nuevo Reporte'),
        onPressed: () async {
          await Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => NuevoReporteScreen(api: widget.api)),
          );
          _cargar();
        },
      ),
    );
  }

  TextStyle _lblStyle(BuildContext ctx) => TextStyle(
    fontWeight: FontWeight.bold,
    fontSize: 13,
    color: Theme.of(ctx).colorScheme.primary,
  );

  Widget _lbl(BuildContext ctx, String t) => Text(t, style: _lblStyle(ctx));

  Widget _row(IconData icon, String text) {
    if (text.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(top: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 16, color: Colors.grey),
          const SizedBox(width: 8),
          Expanded(child: Text(text, style: const TextStyle(fontSize: 14))),
        ],
      ),
    );
  }
}
