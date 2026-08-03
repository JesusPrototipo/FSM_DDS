import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../models/models.dart';

class AgregarDeptoScreen extends StatefulWidget {
  final ApiService api;
  final int clienteId;
  final List<Departamento> deptos;
  const AgregarDeptoScreen({
    super.key,
    required this.api,
    required this.clienteId,
    required this.deptos,
  });
  @override
  State<AgregarDeptoScreen> createState() => _AgregarDeptoScreenState();
}

class _AgregarDeptoScreenState extends State<AgregarDeptoScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabs;

  // Departamento
  final _deptoC  = TextEditingController();
  bool _savingD  = false;
  String? _errorD;
  late List<Departamento> _deptos;

  // Equipo
  Departamento? _deptoSel;
  final _modeloC = TextEditingController();
  final _serieC  = TextEditingController();
  String _marca  = 'Xerox';
  String _status = 'Renta';
  bool _savingE  = false;
  String? _errorE;

  static const _marcas  = ['Xerox','Sharp','Samsung','HP','Kyocera','Canon'];
  static const _statuses = ['Renta','Propio','Poliza','Garantia'];

  @override
  void initState() {
    super.initState();
    _tabs   = TabController(length: 2, vsync: this);
    _deptos = List.from(widget.deptos);
  }

  @override
  void dispose() {
    _tabs.dispose();
    _deptoC.dispose();
    _modeloC.dispose();
    _serieC.dispose();
    super.dispose();
  }

  Future<void> _guardarDepto() async {
    final nombre = _deptoC.text.trim();
    if (nombre.isEmpty) {
      setState(() => _errorD = 'Escribe el nombre del departamento.');
      return;
    }
    setState(() { _savingD = true; _errorD = null; });
    try {
      final id = await widget.api.crearDepto(widget.clienteId, nombre);
      final nuevo = Departamento(id: id, nombre: nombre, equipos: []);
      setState(() {
        _deptos.add(nuevo);
        _deptoC.clear();
      });
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Departamento "$nombre" creado'), backgroundColor: Colors.green),
      );
    } on ApiException catch (e) {
      setState(() => _errorD = e.message);
    } catch (_) {
      setState(() => _errorD = 'Error de conexión.');
    } finally {
      if (mounted) setState(() => _savingD = false);
    }
  }

  Future<void> _guardarEquipo() async {
    if (_deptoSel == null) { setState(() => _errorE = 'Selecciona un departamento.'); return; }
    if (_modeloC.text.trim().isEmpty) { setState(() => _errorE = 'Escribe el modelo.'); return; }
    setState(() { _savingE = true; _errorE = null; });
    try {
      await widget.api.crearEquipo(
        departamentoId: _deptoSel!.id,
        clienteId:      widget.clienteId,
        marca:          _marca,
        modelo:         _modeloC.text.trim(),
        serie:          _serieC.text.trim(),
        status:         _status,
      );
      _modeloC.clear(); _serieC.clear();
      setState(() { _deptoSel = null; _marca = 'Xerox'; _status = 'Renta'; });
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Equipo agregado correctamente'), backgroundColor: Colors.green),
      );
    } on ApiException catch (e) {
      setState(() => _errorE = e.message);
    } catch (_) {
      setState(() => _errorE = 'Error de conexión.');
    } finally {
      if (mounted) setState(() => _savingE = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Agregar'),
        bottom: TabBar(
          controller: _tabs,
          tabs: const [
            Tab(icon: Icon(Icons.business), text: 'Departamento'),
            Tab(icon: Icon(Icons.print),    text: 'Equipo'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabs,
        children: [_tabDepto(), _tabEquipo()],
      ),
    );
  }

  Widget _tabDepto() {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (_deptos.isNotEmpty) ...[
          const Text('Departamentos actuales:',
              style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
          const SizedBox(height: 6),
          Wrap(
            spacing: 6, runSpacing: 6,
            children: _deptos.map((d) => Chip(
              avatar: const Icon(Icons.business, size: 16),
              label: Text(d.nombre),
            )).toList(),
          ),
          const Divider(height: 28),
        ],
        const Text('Nuevo Departamento',
            style: TextStyle(fontWeight: FontWeight.w600)),
        const SizedBox(height: 10),
        if (_errorD != null)
          _errorBox(_errorD!),
        TextField(
          controller: _deptoC,
          decoration: const InputDecoration(
            labelText: 'Nombre del departamento *',
            hintText: 'Ej: Oficinas, Copiado, Recepción…',
            prefixIcon: Icon(Icons.business),
          ),
          textCapitalization: TextCapitalization.words,
          textInputAction: TextInputAction.done,
          onSubmitted: (_) => _guardarDepto(),
        ),
        const SizedBox(height: 20),
        FilledButton.icon(
          onPressed: _savingD ? null : _guardarDepto,
          icon: _savingD
              ? const SizedBox(width: 18, height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
              : const Icon(Icons.add),
          label: const Text('Agregar Departamento'),
        ),
      ],
    );
  }

  Widget _tabEquipo() {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (_deptos.isEmpty)
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.amber.withOpacity(.15),
              border: Border.all(color: Colors.amber),
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Row(children: [
              Icon(Icons.warning_amber, color: Colors.amber),
              SizedBox(width: 8),
              Expanded(child: Text(
                'Primero agrega al menos un departamento en la pestaña anterior.',
              )),
            ]),
          )
        else ...[
          if (_errorE != null) _errorBox(_errorE!),

          DropdownButtonFormField<Departamento>(
            value: _deptoSel,
            hint: const Text('Departamento *'),
            decoration: const InputDecoration(labelText: 'Departamento *'),
            items: _deptos.map((d) => DropdownMenuItem(
              value: d, child: Text(d.nombre),
            )).toList(),
            onChanged: (d) => setState(() => _deptoSel = d),
          ),
          const SizedBox(height: 14),

          DropdownButtonFormField<String>(
            value: _marca,
            decoration: const InputDecoration(labelText: 'Marca *'),
            items: _marcas.map((m) => DropdownMenuItem(
              value: m, child: Text(m),
            )).toList(),
            onChanged: (v) => setState(() => _marca = v!),
          ),
          const SizedBox(height: 14),

          TextField(
            controller: _modeloC,
            decoration: const InputDecoration(
              labelText: 'Modelo *',
              hintText: 'AltaLink B8145',
            ),
            textInputAction: TextInputAction.next,
          ),
          const SizedBox(height: 14),

          TextField(
            controller: _serieC,
            decoration: const InputDecoration(
              labelText: 'No. de Serie',
              hintText: 'HQH258539',
            ),
            textInputAction: TextInputAction.done,
          ),
          const SizedBox(height: 14),

          DropdownButtonFormField<String>(
            value: _status,
            decoration: const InputDecoration(labelText: 'Status'),
            items: _statuses.map((s) => DropdownMenuItem(
              value: s, child: Text(s),
            )).toList(),
            onChanged: (v) => setState(() => _status = v!),
          ),
          const SizedBox(height: 24),

          FilledButton.icon(
            onPressed: _savingE ? null : _guardarEquipo,
            icon: _savingE
                ? const SizedBox(width: 18, height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : const Icon(Icons.add),
            label: const Text('Agregar Equipo'),
          ),
        ],
      ],
    );
  }

  Widget _errorBox(String msg) => Container(
    margin: const EdgeInsets.only(bottom: 12),
    padding: const EdgeInsets.all(10),
    decoration: BoxDecoration(
      color: Colors.red.withOpacity(.1),
      border: Border.all(color: Colors.red),
      borderRadius: BorderRadius.circular(8),
    ),
    child: Text(msg, style: const TextStyle(color: Colors.red, fontSize: 13)),
  );
}
