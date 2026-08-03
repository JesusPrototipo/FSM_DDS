import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../models/models.dart';
import '../../widgets/status_badge.dart';
import 'reporte_detalle_screen.dart';
import 'nuevo_reporte_screen.dart';

class ReportesListScreen extends StatefulWidget {
  final ApiService api;
  const ReportesListScreen({super.key, required this.api});
  @override
  State<ReportesListScreen> createState() => _ReportesListScreenState();
}

class _ReportesListScreenState extends State<ReportesListScreen> {
  final _searchC = TextEditingController();
  String _estatus = '';
  List<Reporte> _reportes = [];
  bool _loading = true;
  String? _error;
  int _pagina = 1;
  int _totalPaginas = 1;

  static const _filtros = [
    ('', 'Todos'),
    ('pendiente', 'Pendiente'),
    ('en proceso', 'En proceso'),
    ('finalizado', 'Finalizado'),
    ('cancelado', 'Cancelado'),
  ];

  @override
  void initState() {
    super.initState();
    _cargar();
  }

  @override
  void dispose() {
    _searchC.dispose();
    super.dispose();
  }

  Future<void> _cargar({bool reset = true}) async {
    if (reset) _pagina = 1;
    setState(() { _loading = true; _error = null; });
    try {
      final data = await widget.api.getReportes(
        pagina: _pagina,
        estatus: _estatus,
        q: _searchC.text.trim(),
      );
      setState(() {
        _reportes = ((data['data'] as List?) ?? [])
            .map((e) => Reporte.fromMap(e as Map<String, dynamic>))
            .toList();
        _totalPaginas = data['paginas'] as int? ?? 1;
      });
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Error de conexión. Verifica tu red WiFi.');
    } finally {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Column(
        children: [
          // Barra de búsqueda
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: SearchBar(
              controller: _searchC,
              hintText: 'Buscar por cliente, serie o modelo…',
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

          // Filtros de estatus
          SizedBox(
            height: 52,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              children: _filtros.map((f) {
                final selected = _estatus == f.$1;
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: FilterChip(
                    label: Text(f.$2),
                    selected: selected,
                    onSelected: (_) {
                      setState(() => _estatus = f.$1);
                      _cargar();
                    },
                  ),
                );
              }).toList(),
            ),
          ),

          // Lista
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? _ErrorView(_error!, onRetry: _cargar)
                    : _reportes.isEmpty
                        ? const _EmptyView('No hay reportes con estos filtros')
                        : RefreshIndicator(
                            onRefresh: _cargar,
                            child: ListView.builder(
                              padding: const EdgeInsets.all(12),
                              itemCount: _reportes.length +
                                  (_totalPaginas > _pagina ? 1 : 0),
                              itemBuilder: (ctx, i) {
                                if (i == _reportes.length) {
                                  return Center(
                                    child: TextButton.icon(
                                      icon: const Icon(Icons.expand_more),
                                      label: const Text('Cargar más'),
                                      onPressed: () {
                                        _pagina++;
                                        _cargar(reset: false);
                                      },
                                    ),
                                  );
                                }
                                return _ReporteCard(
                                  reporte: _reportes[i],
                                  onTap: () async {
                                    await Navigator.push(
                                      context,
                                      MaterialPageRoute(
                                        builder: (_) => ReporteDetalleScreen(
                                          api: widget.api,
                                          id: _reportes[i].id,
                                        ),
                                      ),
                                    );
                                    _cargar();
                                  },
                                );
                              },
                            ),
                          ),
          ),
        ],
      ),

      floatingActionButton: FloatingActionButton.extended(
        icon: const Icon(Icons.add),
        label: const Text('Nuevo Reporte'),
        onPressed: () async {
          await Navigator.push(
            context,
            MaterialPageRoute(
              builder: (_) => NuevoReporteScreen(api: widget.api),
            ),
          );
          _cargar();
        },
      ),
    );
  }
}

class _ReporteCard extends StatelessWidget {
  final Reporte reporte;
  final VoidCallback onTap;
  const _ReporteCard({required this.reporte, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Text(
                    '#${reporte.id.toString().padLeft(5, '0')}',
                    style: const TextStyle(
                      fontWeight: FontWeight.bold, fontSize: 13,
                    ),
                  ),
                  const Spacer(),
                  StatusBadge(reporte.estatus),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                reporte.razon,
                style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15),
              ),
              Text(
                reporte.departamento,
                style: TextStyle(
                  color: Theme.of(context).colorScheme.onSurface.withOpacity(.6),
                  fontSize: 13,
                ),
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.print, size: 14),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      '${reporte.marca} ${reporte.modelo}',
                      style: const TextStyle(fontSize: 13),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  const Icon(Icons.warning_amber_outlined, size: 14),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      reporte.falla,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 13),
                    ),
                  ),
                ],
              ),
              if (reporte.tecnicoNombre != null) ...[
                const SizedBox(height: 4),
                Row(
                  children: [
                    const Icon(Icons.engineering, size: 14),
                    const SizedBox(width: 4),
                    Text(reporte.tecnicoNombre!, style: const TextStyle(fontSize: 13)),
                  ],
                ),
              ],
              const SizedBox(height: 4),
              Row(
                children: [
                  const Icon(Icons.calendar_today, size: 13),
                  const SizedBox(width: 4),
                  Text(reporte.fecha, style: const TextStyle(fontSize: 12)),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final String msg;
  final VoidCallback onRetry;
  const _ErrorView(this.msg, {required this.onRetry});
  @override
  Widget build(BuildContext context) => Center(
    child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const Icon(Icons.cloud_off, size: 48, color: Colors.grey),
        const SizedBox(height: 12),
        Text(msg, textAlign: TextAlign.center),
        const SizedBox(height: 16),
        FilledButton.icon(
          onPressed: onRetry,
          icon: const Icon(Icons.refresh),
          label: const Text('Reintentar'),
        ),
      ],
    ),
  );
}

class _EmptyView extends StatelessWidget {
  final String msg;
  const _EmptyView(this.msg);
  @override
  Widget build(BuildContext context) => Center(
    child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const Icon(Icons.inbox, size: 48, color: Colors.grey),
        const SizedBox(height: 12),
        Text(msg, style: const TextStyle(color: Colors.grey)),
      ],
    ),
  );
}
