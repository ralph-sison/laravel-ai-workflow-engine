import 'package:flutter/material.dart';

class StatusBadge extends StatelessWidget {
  final String status;

  const StatusBadge(this.status, {super.key});

  @override
  Widget build(BuildContext context) {
    final (color, bg) = _colors(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        status,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }

  (Color, Color) _colors(String s) => switch (s) {
        'active' || 'success' => (
            const Color(0xFF166534),
            const Color(0xFFDCFCE7)
          ),
        'running' => (const Color(0xFF1E40AF), const Color(0xFFDBEAFE)),
        'failed' => (const Color(0xFF991B1B), const Color(0xFFFEE2E2)),
        'paused' || 'draft' => (
            const Color(0xFF92400E),
            const Color(0xFFFEF3C7)
          ),
        'manual' => (const Color(0xFF374151), const Color(0xFFF3F4F6)),
        'webhook' => (const Color(0xFF5B21B6), const Color(0xFFEDE9FE)),
        'schedule' => (const Color(0xFF1E40AF), const Color(0xFFDBEAFE)),
        _ => (const Color(0xFF374151), const Color(0xFFF3F4F6)),
      };
}
