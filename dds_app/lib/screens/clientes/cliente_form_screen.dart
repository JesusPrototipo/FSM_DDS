import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../models/models.dart';

class ClienteFormScreen extends StatefulWidget {
  final ApiService api;
  final Cliente? cliente; // null = nuevo, distinto de null = editar
  const ClienteFormScreen({super.key, required this.api, this.cliente});
  @override
  State<ClienteFormScreen> createState() => _ClienteFormScreenState();
}

class _ClienteFormScreenState extends State<ClienteFormScreen> {
  final _formKey   = GlobalKey<FormState>();
  late final _razonC     = TextEditingController(text: widget.cliente?.razon     ?? '');
  late final _reportoC   = TextEditingController(text: widget.cliente?.reporto   ?? '');
  late final _direccionC = TextEditingController(text: widget.cliente?.direccion ?? '');
  late final _telefonoC  = TextEditingController(text: widget.cliente?.telefono  ?? '');
  late final _horarioC   = TextEditingController(text: widget.cliente?.horario   ?? '');

  bool _loading = false;
  String? _error;

  bool get _esNuevo => widget.cliente == null;

  @override
  void dispose() {
    for (final c in [_razonC, _reportoC, _direccionC, _telefonoC, _horarioC]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _guardar() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() { _loading = true; _error = null; });

    try {
      if (_esNuevo) {
        await widget.api.crearCliente(
          razon:     _razonC.text.trim(),
          reporto:   _reportoC.text.trim(),
          direccion: _direccionC.text.trim(),
          telefono:  _telefonoC.text.trim(),
          horario:   _horarioC.text.trim(),
        );
      } else {
        await widget.api.actualizarCliente(
          id:        widget.cliente!.id,
          razon:     _razonC.text.trim(),
          reporto:   _reportoC.text.trim(),
          direccion: _direccionC.text.trim(),
          telefono:  _telefonoC.text.trim(),
          horario:   _horarioC.text.trim(),
        );
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_esNuevo ? 'Cliente creado' : 'Cliente actualizado'),
            backgroundColor: Colors.green,
          ),
        );
        Navigator.pop(context, true);
      }
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Error de conexión.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_esNuevo ? 'Nuevo Cliente' : 'Editar Cliente'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (_error != null) ...[
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.red.withOpacity(.1),
                  border: Border.all(color: Colors.red),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(_error!, style: const TextStyle(color: Colors.red)),
              ),
              const SizedBox(height: 12),
            ],

            TextFormField(
              controller: _razonC,
              decoration: const InputDecoration(
                labelText: 'Razón Social *',
                prefixIcon: Icon(Icons.business),
              ),
              textCapitalization: TextCapitalization.words,
              textInputAction: TextInputAction.next,
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
            ),
            const SizedBox(height: 14),

            TextFormField(
              controller: _reportoC,
              decoration: const InputDecoration(
                labelText: 'Contacto',
                prefixIcon: Icon(Icons.person),
              ),
              textCapitalization: TextCapitalization.words,
              textInputAction: TextInputAction.next,
            ),
            const SizedBox(height: 14),

            TextFormField(
              controller: _direccionC,
              decoration: const InputDecoration(
                labelText: 'Dirección *',
                prefixIcon: Icon(Icons.location_on),
              ),
              textInputAction: TextInputAction.next,
              maxLines: 2,
              minLines: 1,
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
            ),
            const SizedBox(height: 14),

            TextFormField(
              controller: _telefonoC,
              decoration: const InputDecoration(
                labelText: 'Teléfono',
                prefixIcon: Icon(Icons.phone),
              ),
              keyboardType: TextInputType.phone,
              textInputAction: TextInputAction.next,
            ),
            const SizedBox(height: 14),

            TextFormField(
              controller: _horarioC,
              decoration: const InputDecoration(
                labelText: 'Horario de Atención',
                prefixIcon: Icon(Icons.schedule),
                hintText: 'Ej: 8am - 6pm',
              ),
              textInputAction: TextInputAction.done,
              onFieldSubmitted: (_) => _guardar(),
            ),
            const SizedBox(height: 28),

            FilledButton.icon(
              onPressed: _loading ? null : _guardar,
              icon: _loading
                  ? const SizedBox(
                      width: 18, height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : const Icon(Icons.check),
              label: Text(_esNuevo ? 'Crear Cliente' : 'Guardar Cambios'),
            ),
          ],
        ),
      ),
    );
  }
}
