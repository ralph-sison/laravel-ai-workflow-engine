import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../providers/workflow_provider.dart';
import '../widgets/workflow_card.dart';

class WorkflowsScreen extends ConsumerWidget {
  const WorkflowsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final workflowsAsync = ref.watch(workflowsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Workflows')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(workflowsProvider.future),
        child: workflowsAsync.when(
          data: (workflows) {
            if (workflows.isEmpty) {
              return const Center(
                child: Text('No workflows yet.',
                    style: TextStyle(color: Colors.grey)),
              );
            }
            return ListView.builder(
              padding: const EdgeInsets.symmetric(vertical: 8),
              itemCount: workflows.length,
              itemBuilder: (_, i) => WorkflowCard(
                workflow: workflows[i],
                onTap: () => context.push(
                  '/workflows/${workflows[i].id}',
                  extra: workflows[i],
                ),
              ),
            );
          },
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => Center(child: Text('Error: $e')),
        ),
      ),
    );
  }
}
