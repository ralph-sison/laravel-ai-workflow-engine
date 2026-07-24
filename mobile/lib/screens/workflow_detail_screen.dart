import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../models/workflow.dart';
import '../providers/workflow_provider.dart';
import '../widgets/status_badge.dart';

class WorkflowDetailScreen extends ConsumerWidget {
  final String workflowId;
  final Workflow? workflow;

  const WorkflowDetailScreen({
    super.key,
    required this.workflowId,
    this.workflow,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final executionsAsync = ref.watch(executionsProvider(workflowId));

    return Scaffold(
      appBar: AppBar(
        title: Text(workflow?.name ?? 'Workflow'),
        actions: [
          if (workflow?.isActive ?? false)
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: FilledButton.icon(
                onPressed: () => _triggerWorkflow(context, ref),
                icon: const Icon(Icons.play_arrow, size: 18),
                label: const Text('Run'),
              ),
            ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(executionsProvider(workflowId).future),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Workflow info
            if (workflow != null) ...[
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          StatusBadge(workflow!.status),
                          const SizedBox(width: 8),
                          StatusBadge(workflow!.triggerType),
                        ],
                      ),
                      if (workflow!.description != null) ...[
                        const SizedBox(height: 8),
                        Text(workflow!.description!,
                            style: const TextStyle(color: Colors.grey)),
                      ],
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
            ],

            // Execution history
            Text('Execution History',
                style: Theme.of(context)
                    .textTheme
                    .titleMedium
                    ?.copyWith(fontWeight: FontWeight.w600)),
            const SizedBox(height: 8),

            executionsAsync.when(
              data: (executions) {
                if (executions.isEmpty) {
                  return const Padding(
                    padding: EdgeInsets.symmetric(vertical: 16),
                    child: Text('No executions yet.',
                        style: TextStyle(color: Colors.grey)),
                  );
                }
                return Column(
                  children: executions
                      .map(
                        (e) => ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: StatusBadge(e.status),
                          title: Text(
                            e.id.substring(0, 8),
                            style: const TextStyle(
                                fontFamily: 'monospace', fontSize: 13),
                          ),
                          subtitle: Text(
                            e.startedAt,
                            style: const TextStyle(fontSize: 12),
                          ),
                          trailing:
                              const Icon(Icons.chevron_right, size: 18),
                          onTap: () => context.push(
                            '/workflows/$workflowId/executions/${e.id}',
                            extra: e,
                          ),
                        ),
                      )
                      .toList(),
                );
              },
              loading: () =>
                  const Center(child: CircularProgressIndicator()),
              error: (e, _) => Text('Error: $e'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _triggerWorkflow(BuildContext context, WidgetRef ref) async {
    try {
      await ref.read(triggerWorkflowProvider(workflowId).future);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Workflow triggered successfully')),
      );
      ref.invalidate(executionsProvider(workflowId));
    } catch (e) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to trigger: $e')),
      );
    }
  }
}
