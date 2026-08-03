import 'package:flutter/material.dart';

class StatusBadge extends StatelessWidget {
  final String estatus;
  final bool large;
  const StatusBadge(this.estatus, {super.key, this.large = false});

  @override
  Widget build(BuildContext context) {
    final (color, icon) = switch (estatus.toLowerCase()) {
      'pendiente'  => (Colors.amber.shade700,  Icons.schedule),
      'en proceso' => (Colors.cyan.shade600,   Icons.build_circle),
      'finalizado' => (Colors.green.shade600,  Icons.check_circle),
      'cancelado'  => (Colors.grey.shade600,   Icons.cancel),
      _            => (Colors.grey.shade500,   Icons.help_outline),
    };

    final fontSize = large ? 13.0 : 11.0;
    final padding  = large
        ? const EdgeInsets.symmetric(horizontal: 12, vertical: 6)
        : const EdgeInsets.symmetric(horizontal: 8,  vertical: 3);

    return Container(
      padding: padding,
      decoration: BoxDecoration(
        color: color.withOpacity(0.2),
        border: Border.all(color: color, width: 1),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: fontSize + 2, color: color),
          const SizedBox(width: 4),
          Text(
            estatus.toUpperCase(),
            style: TextStyle(
              fontSize: fontSize,
              fontWeight: FontWeight.bold,
              color: color,
              letterSpacing: .5,
            ),
          ),
        ],
      ),
    );
  }
}
