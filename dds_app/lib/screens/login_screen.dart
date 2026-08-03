import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey   = GlobalKey<FormState>();
  final _usuarioC  = TextEditingController();
  final _passwordC = TextEditingController();
  bool _loading    = false;
  bool _showPass   = false;
  String? _error;

  @override
  void dispose() {
    _usuarioC.dispose();
    _passwordC.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() { _loading = true; _error = null; });

    final err = await context.read<AuthProvider>().login(
      _usuarioC.text.trim(),
      _passwordC.text,
    );

    if (!mounted) return;
    if (err != null) {
      setState(() { _loading = false; _error = err; });
    }
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(32),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // Logo / Ícono
                Container(
                  width: 96, height: 96,
                  decoration: BoxDecoration(
                    color: scheme.primary.withOpacity(.15),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(Icons.print, size: 48, color: scheme.primary),
                ),
                const SizedBox(height: 24),
                Text(
                  'Digital Document Services',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                  textAlign: TextAlign.center,
                ),
                Text(
                  'Portal de Servicio Técnico',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: scheme.onSurface.withOpacity(.6),
                  ),
                ),
                const SizedBox(height: 40),

                // Error
                if (_error != null) ...[
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: scheme.error.withOpacity(.1),
                      border: Border.all(color: scheme.error),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.warning_amber, color: scheme.error, size: 18),
                        const SizedBox(width: 8),
                        Expanded(child: Text(_error!, style: TextStyle(color: scheme.error))),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                ],

                // Formulario
                Form(
                  key: _formKey,
                  child: Column(
                    children: [
                      TextFormField(
                        controller: _usuarioC,
                        decoration: const InputDecoration(
                          labelText: 'Usuario',
                          prefixIcon: Icon(Icons.person_outline),
                        ),
                        textInputAction: TextInputAction.next,
                        autocorrect: false,
                        validator: (v) =>
                            (v == null || v.trim().isEmpty) ? 'Requerido' : null,
                      ),
                      const SizedBox(height: 16),
                      TextFormField(
                        controller: _passwordC,
                        obscureText: !_showPass,
                        decoration: InputDecoration(
                          labelText: 'Contraseña',
                          prefixIcon: const Icon(Icons.lock_outline),
                          suffixIcon: IconButton(
                            icon: Icon(_showPass
                                ? Icons.visibility_off
                                : Icons.visibility),
                            onPressed: () =>
                                setState(() => _showPass = !_showPass),
                          ),
                        ),
                        textInputAction: TextInputAction.done,
                        onFieldSubmitted: (_) => _login(),
                        validator: (v) =>
                            (v == null || v.isEmpty) ? 'Requerido' : null,
                      ),
                      const SizedBox(height: 28),
                      FilledButton(
                        onPressed: _loading ? null : _login,
                        child: _loading
                            ? const SizedBox(
                                height: 22, width: 22,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2, color: Colors.white,
                                ),
                              )
                            : const Text('Iniciar Sesión', style: TextStyle(fontSize: 16)),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 40),
                Text(
                  '© ${DateTime.now().year} Digital Document Services',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: scheme.onSurface.withOpacity(.4),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
