import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../models/models.dart';
import '../../widgets/status_badge.dart';

class ReporteDetalleScreen extends StatefulWidget {
  final ApiService api;
  final int id;
  const ReporteDetalleScreen({super.key, required this.api, required this.id});
  @override
  State<ReporteDetalleScreen> createState() => _ReporteDetalleScreenState();
}

class _ReporteDetalleScreenState extends State<ReporteDetalleScreen> {
  Reporte? _reporte;
  bool _loading = true;
  String? _error;
  final _notaC = TextEditingController();

  @override
  void initState() { super.initState(); _cargar(); }
  @override
  void dispose() { _notaC.dispose(); super.dispose(); }

  Future<void> _cargar() async {
    setState(() { _loading = true; _error = null; });
    try {
      final data = await widget.api.getReporteDetalle(widget.id);
      setState(() => _reporte = Reporte.fromMap(data));
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Error de conexión.');
    } finally {
      setState(() => _loading = false);
    }
  }

  Future<void> _cambiarEstatus(String estatus) async {
    final notaC = TextEditingController();
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('Cambiar a "$estatus"'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('¿Confirmas el cambio de estatus?'),
            const SizedBox(height: 12),
            TextField(
              controller: notaC,
              decoration: const InputDecoration(
                labelText: 'Nota (opcional)',
                hintText: 'Describe el motivo…',
              ),
              maxLines: 2,
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancelar')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Confirmar')),
        ],
      ),
    );
    if (confirm != true || !mounted) return;

    try {
      await widget.api.cambiarEstatus(widget.id, estatus, nota: notaC.text.trim());
      _cargar();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Estatus cambiado a "$estatus"'), backgroundColor: Colors.green),
        );
      }
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.message), backgroundColor: Colors.red),
      );
    }
  }

  Future<void> _agregarNota() async {
    final nota = _notaC.text.trim();
    if (nota.isEmpty) return;
    try {
      await widget.api.agregarNota(widget.id, nota);
      _notaC.clear();
      _cargar();
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nota agregada'), backgroundColor: Colors.green),
      );
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
        title: Text('Reporte #${widget.id.toString().padLeft(5, '0')}'),
        actions: [
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
              : _buildBody(),
    );
  }

  Widget _buildBody() {
    final r = _reporte!;
    final scheme = Theme.of(context).colorScheme;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Estatus actual
        Center(child: StatusBadge(r.estatus, large: true)),
        const SizedBox(height: 16),

        // Info cliente + equipo
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _Section('Cliente'),
                _InfoRow(Icons.business, r.razon),
                _InfoRow(Icons.room, r.departamento),
                if (r.contacto != null) _InfoRow(Icons.person, r.contacto!),
                if (r.telefono != null) _InfoRow(Icons.phone, r.telefono!),
                if (r.horario != null) _InfoRow(Icons.schedule, r.horario!),
                const Divider(height: 24),
                _Section('Equipo'),
                _InfoRow(Icons.print, '${r.marca} ${r.modelo}'),
                if (r.serie.isNotEmpty) _InfoRow(Icons.qr_code, 'Serie: ${r.serie}'),
                const Divider(height: 24),
                _Section('Falla Reportada'),
                const SizedBox(height: 4),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: scheme.surfaceContainerHighest,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(r.falla),
                ),
                if (r.tecnicoNombre != null) ...[
                  const Divider(height: 24),
                  _InfoRow(Icons.engineering, 'Técnico: ${r.tecnicoNombre!}'),
                ],
                _InfoRow(Icons.calendar_today, r.fecha),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),

        // Cambiar estatus
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _Section('Cambiar Estatus'),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 8, runSpacing: 8,
                  children: [
                    ('pendiente',  'Pendiente',   Colors.amber,  Icons.schedule),
                    ('en proceso', 'En Proceso',  Colors.cyan,   Icons.build_circle),
                    ('finalizado', 'Finalizado',  Colors.green,  Icons.check_circle),
                    ('cancelado',  'Cancelar',    Colors.grey,   Icons.cancel),
                  ].map((t) {
                    final isActive = r.estatus == t.$1;
                    return ElevatedButton.icon(
                      onPressed: isActive ? null : () => _cambiarEstatus(t.$1),
                      icon: Icon(t.$4, size: 16),
                      label: Text(t.$2),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: isActive ? t.$3 : null,
                        foregroundColor: isActive ? Colors.white : null,
                      ),
                    );
                  }).toList(),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),

        // Bitácora
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _Section('Bitácora'),
                const SizedBox(height: 8),
                // Notas existentes
                ...(() {
                  final obs = r.observaciones ?? '';
                  final lineas = obs
                      .split('\n')
                      .map((l) => l.trim())
                      .where((l) => l.isNotEmpty)
                      .toList()
                      .reversed
                      .toList();
                  if (lineas.isEmpty) {
                    return [
                      const Text(
                        'Sin notas aún.',
                        style: TextStyle(color: Colors.grey),
                      )
                    ];
                  }
                  return lineas.map((l) => Padding(
                    padding: const EdgeInsets.only(bottom: 6),
                    child: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: scheme.surfaceContainerHighest,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(l, style: const TextStyle(fontSize: 13)),
                    ),
                  )).toList();
                })(),
                const SizedBox(height: 12),
                // Agregar nota
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _notaC,
                        decoration: const InputDecoration(
                          hintText: 'Agregar nota a la bitácora…',
                          isDense: true,
                        ),
                        maxLines: 2,
                        minLines: 1,
                      ),
                    ),
                    const SizedBox(width: 8),
                    IconButton.filled(
                      icon: const Icon(Icons.send),
                      onPressed: _agregarNota,
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _Section extends StatelessWidget {
  final String title;
  const _Section(this.title);
  @override
  Widget build(BuildContext context) => Text(
    title,
    style: TextStyle(
      fontWeight: FontWeight.bold,
      fontSize: 12,
      color: Theme.of(context).colorScheme.primary,
      letterSpacing: .5,
    ),
  );
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String text;
  const _InfoRow(this.icon, this.text);
  @override
  Widget build(BuildContext context) => Padding(
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
