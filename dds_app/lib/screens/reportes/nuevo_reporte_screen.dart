import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../models/models.dart';

class NuevoReporteScreen extends StatefulWidget {
  final ApiService api;
  const NuevoReporteScreen({super.key, required this.api});
  @override
  State<NuevoReporteScreen> createState() => _NuevoReporteScreenState();
}

class _NuevoReporteScreenState extends State<NuevoReporteScreen> {
  final _busquedaC = TextEditingController();
  final _fallaC    = TextEditingController();

  List<dynamic> _sugerencias = [];
  bool _buscando = false;

  Cliente?     _clienteSel;
  Departamento? _deptoSel;
  Equipo?      _equipoSel;
  Tecnico?     _tecnicoSel;

  List<Departamento> _deptos   = [];
  List<Equipo>       _equipos  = [];
  List<Tecnico>      _tecnicos = [];

  bool _guardando = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _cargarTecnicos();
  }

  @override
  void dispose() {
    _busquedaC.dispose();
    _fallaC.dispose();
    super.dispose();
  }

  Future<void> _cargarTecnicos() async {
    try {
      final list = await widget.api.getTecnicos();
      setState(() => _tecnicos = list
          .map((e) => Tecnico.fromMap(e as Map<String, dynamic>))
          .toList());
    } catch (_) {}
  }

  Future<void> _buscar(String q) async {
    if (q.length < 2) { setState(() => _sugerencias = []); return; }
    setState(() => _buscando = true);
    try {
      final res = await widget.api.buscarClientes(q);
      setState(() => _sugerencias = res);
    } catch (_) {
      setState(() => _sugerencias = []);
    } finally {
      setState(() => _buscando = false);
    }
  }

  Future<void> _seleccionarCliente(Map<String, dynamic> c) async {
    _busquedaC.text = c['razon'] ?? '';
    setState(() {
      _sugerencias = [];
      _clienteSel  = Cliente.fromMap(c);
      _deptoSel    = null;
      _equipoSel   = null;
      _deptos      = [];
      _equipos     = [];
    });
    FocusScope.of(context).unfocus();

    try {
      final detalle = await widget.api.getClienteDetalle(_clienteSel!.id);
      final lista = ((detalle['departamentos'] as List?) ?? [])
          .map((d) => Departamento.fromMap(d as Map<String, dynamic>))
          .toList();
      setState(() => _deptos = lista);
    } catch (_) {}
  }

  void _seleccionarDepto(Departamento d) {
    setState(() {
      _deptoSel  = d;
      _equipoSel = null;
      _equipos   = d.equipos;
    });
  }

  Future<void> _guardar() async {
    _error = null;
    if (_clienteSel == null) return _setError('Selecciona un cliente.');
    if (_deptoSel == null)   return _setError('Selecciona el departamento.');
    if (_equipoSel == null)  return _setError('Selecciona el equipo.');
    if (_fallaC.text.trim().isEmpty) return _setError('Describe la falla.');

    setState(() => _guardando = true);
    try {
      await widget.api.crearReporte(
        clienteId:      _clienteSel!.id,
        departamentoId: _deptoSel!.id,
        equipoId:       _equipoSel!.id,
        falla:          _fallaC.text.trim(),
        tecnicoId:      _tecnicoSel?.id,
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Reporte creado correctamente'), backgroundColor: Colors.green),
        );
        Navigator.pop(context, true);
      }
    } on ApiException catch (e) {
      _setError(e.message);
    } catch (_) {
      _setError('Error de conexión.');
    } finally {
      if (mounted) setState(() => _guardando = false);
    }
  }

  void _setError(String msg) => setState(() => _error = msg);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Nueva Orden de Servicio')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_error != null)
            Container(
              margin: const EdgeInsets.only(bottom: 12),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.red.shade900.withOpacity(.3),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: Colors.red.shade700),
              ),
              child: Text(_error!, style: const TextStyle(color: Colors.red)),
            ),

          // ── Búsqueda de cliente ───────────────────────────
          Text('Cliente *', style: _label(context)),
          const SizedBox(height: 6),
          TextField(
            controller: _busquedaC,
            decoration: const InputDecoration(
              hintText: 'Escribe el nombre del cliente…',
              prefixIcon: Icon(Icons.search),
            ),
            onChanged: _buscar,
          ),

          // Sugerencias
          if (_buscando)
            const Padding(
              padding: EdgeInsets.all(8),
              child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
            ),
          if (_sugerencias.isNotEmpty)
            Card(
              margin: const EdgeInsets.only(top: 4),
              child: Column(
                children: _sugerencias.map((c) {
                  final m = c as Map<String, dynamic>;
                  return ListTile(
                    dense: true,
                    leading: const Icon(Icons.business, size: 18),
                    title: Text(m['razon']?.toString() ?? ''),
                    subtitle: Text(m['reporto']?.toString() ?? ''),
                    onTap: () => _seleccionarCliente(m),
                  );
                }).toList(),
              ),
            ),

          // Info cliente seleccionado
          if (_clienteSel != null) ...[
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.primaryContainer.withOpacity(.4),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(_clienteSel!.razon, style: const TextStyle(fontWeight: FontWeight.bold)),
                  if (_clienteSel!.telefono.isNotEmpty)
                    Text('Tel: ${_clienteSel!.telefono}', style: const TextStyle(fontSize: 13)),
                  if (_clienteSel!.horario.isNotEmpty)
                    Text('Horario: ${_clienteSel!.horario}', style: const TextStyle(fontSize: 13)),
                ],
              ),
            ),
          ],
          const SizedBox(height: 16),

          // ── Departamento ──────────────────────────────────
          Text('Departamento *', style: _label(context)),
          const SizedBox(height: 6),
          DropdownButtonFormField<Departamento>(
            value: _deptoSel,
            hint: const Text('Selecciona departamento'),
            items: _deptos.map((d) => DropdownMenuItem(
              value: d,
              child: Text(d.nombre),
            )).toList(),
            onChanged: _clienteSel == null ? null : (d) {
              if (d != null) _seleccionarDepto(d);
            },
            decoration: const InputDecoration(),
          ),
          const SizedBox(height: 16),

          // ── Equipo ────────────────────────────────────────
          Text('Equipo *', style: _label(context)),
          const SizedBox(height: 6),
          DropdownButtonFormField<Equipo>(
            value: _equipoSel,
            hint: const Text('Selecciona equipo'),
            items: _equipos.map((e) => DropdownMenuItem(
              value: e,
              child: Text('${e.nombre}${e.serie.isNotEmpty ? " · ${e.serie}" : ""}'),
            )).toList(),
            onChanged: _deptoSel == null ? null : (e) =>
                setState(() => _equipoSel = e),
            decoration: const InputDecoration(),
          ),
          const SizedBox(height: 16),

          // ── Falla ─────────────────────────────────────────
          Text('Falla Reportada *', style: _label(context)),
          const SizedBox(height: 6),
          TextField(
            controller: _fallaC,
            decoration: const InputDecoration(
              hintText: 'Describe el problema reportado…',
            ),
            maxLines: 4,
            minLines: 3,
          ),
          const SizedBox(height: 16),

          // ── Técnico ───────────────────────────────────────
          Text('Técnico Asignado', style: _label(context)),
          const SizedBox(height: 6),
          DropdownButtonFormField<Tecnico>(
            value: _tecnicoSel,
            hint: const Text('Sin asignar'),
            items: _tecnicos.map((t) => DropdownMenuItem(
              value: t,
              child: Text(t.nombre),
            )).toList(),
            onChanged: (t) => setState(() => _tecnicoSel = t),
            decoration: const InputDecoration(),
          ),
          const SizedBox(height: 28),

          FilledButton.icon(
            onPressed: _guardando ? null : _guardar,
            icon: _guardando
                ? const SizedBox(
                    width: 18, height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.check),
            label: const Text('Crear Reporte'),
          ),
        ],
      ),
    );
  }

  TextStyle _label(BuildContext ctx) => TextStyle(
    fontWeight: FontWeight.w600,
    fontSize: 13,
    color: Theme.of(ctx).colorScheme.primary,
  );
}
