import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../providers/auth_provider.dart';
import '../providers/workflow_provider.dart';
import '../widgets/status_badge.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    final workflowsAsync = ref.watch(workflowsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Sign out',
            onPressed: () async {
              await ref.read(authProvider.notifier).logout();
              if (context.mounted) context.go('/login');
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(workflowsProvider.future),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // User greeting
            authState.whenData((user) => user).value != null
                ? Text(
                    'Hello, ${authState.value!.name.split(' ').first}',
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  )
                : const SizedBox.shrink(),
            const SizedBox(height: 20),

            // Stats cards
            workflowsAsync.when(
              data: (workflows) {
                final active =
                    workflows.where((w) => w.isActive).length;
                return Row(
                  children: [
                    _StatCard(
                        label: 'Total', value: workflows.length.toString()),
                    const SizedBox(width: 12),
                    _StatCard(
                        label: 'Active',
                        value: active.toString(),
                        highlight: true),
                  ],
                );
              },
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (e, _) => Text('Error: $e'),
            ),
            const SizedBox(height: 24),

            // Recent workflows header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('Workflows',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w600,
                        )),
                TextButton(
                  onPressed: () => context.go('/workflows'),
                  child: const Text('View all'),
                ),
              ],
            ),
            const SizedBox(height: 8),

            workflowsAsync.when(
              data: (workflows) {
                final recent = workflows.take(5).toList();
                if (recent.isEmpty) {
                  return const Text('No workflows yet.',
                      style: TextStyle(color: Colors.grey));
                }
                return Column(
                  children: recent
                      .map((w) => ListTile(
                            contentPadding: EdgeInsets.zero,
                            title: Text(w.name,
                                style: const TextStyle(
                                    fontSize: 14, fontWeight: FontWeight.w500)),
                            trailing: StatusBadge(w.status),
                            onTap: () =>
                                context.push('/workflows/${w.id}', extra: w),
                          ))
                      .toList(),
                );
              },
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (e, _) => Text('Error: $e'),
            ),
          ],
        ),
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String label;
  final String value;
  final bool highlight;

  const _StatCard({
    required this.label,
    required this.value,
    this.highlight = false,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label,
                  style: Theme.of(context)
                      .textTheme
                      .labelSmall
                      ?.copyWith(color: Colors.grey)),
              const SizedBox(height: 4),
              Text(
                value,
                style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                      color: highlight
                          ? const Color(0xFF6366F1)
                          : null,
                    ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
