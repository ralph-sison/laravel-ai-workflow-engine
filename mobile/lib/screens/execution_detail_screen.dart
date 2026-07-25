import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/execution.dart';
import '../providers/workflow_provider.dart';
import '../widgets/status_badge.dart';

class ExecutionDetailScreen extends ConsumerWidget {
  final String workflowId;
  final String executionId;
  final Execution? execution;

  const ExecutionDetailScreen({
    super.key,
    required this.workflowId,
    required this.executionId,
    this.execution,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final executionAsync = ref.watch(
      executionDetailProvider((
        workflowId: workflowId,
        executionId: executionId,
      )),
    );

    return Scaffold(
      appBar: AppBar(title: const Text('Execution Detail')),
      body: executionAsync.when(
        data: (exec) => _buildBody(context, exec),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Error: $e')),
      ),
    );
  }

  Widget _buildBody(BuildContext context, Execution exec) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Summary card
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    StatusBadge(exec.status),
                    const SizedBox(width: 8),
                    StatusBadge(exec.triggerType),
                  ],
                ),
                const SizedBox(height: 12),
                _InfoRow(label: 'ID', value: exec.id),
                _InfoRow(label: 'Started', value: exec.startedAt),
                if (exec.finishedAt != null)
                  _InfoRow(label: 'Finished', value: exec.finishedAt!),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),

        // Step logs
        Text(
          'Step Logs',
          style: Theme.of(context)
              .textTheme
              .titleMedium
              ?.copyWith(fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 8),

        if (exec.logs.isEmpty)
          const Text('No step logs available.',
              style: TextStyle(color: Colors.grey))
        else
          ...exec.logs.map((log) => _StepLogTile(log: log)),
      ],
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;

  const _InfoRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 72,
            child: Text(label,
                style: const TextStyle(
                    fontSize: 12,
                    color: Colors.grey,
                    fontWeight: FontWeight.w500)),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontSize: 12, fontFamily: 'monospace'),
            ),
          ),
        ],
      ),
    );
  }
}

class _StepLogTile extends StatelessWidget {
  final ExecutionLog log;

  const _StepLogTile({required this.log});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                StatusBadge(log.status),
                const Spacer(),
                if (log.durationMs != null)
                  Text(
                    '${log.durationMs}ms',
                    style: const TextStyle(fontSize: 11, color: Colors.grey),
                  ),
              ],
            ),
            if (log.error != null) ...[
              const SizedBox(height: 8),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEE2E2),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  log.error!,
                  style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF991B1B),
                      fontFamily: 'monospace'),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
