import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/execution.dart';
import '../models/workflow.dart';
import 'auth_provider.dart';

final workflowsProvider = FutureProvider<List<Workflow>>((ref) async {
  final client = ref.watch(apiClientProvider);
  return client.getWorkflows();
});

final executionsProvider =
    FutureProvider.family<List<Execution>, String>((ref, workflowId) async {
  final client = ref.watch(apiClientProvider);
  return client.getExecutions(workflowId);
});

final executionDetailProvider =
    FutureProvider.family<Execution, ({String workflowId, String executionId})>(
        (ref, args) async {
  final client = ref.watch(apiClientProvider);
  return client.getExecution(args.workflowId, args.executionId);
});

final triggerWorkflowProvider =
    FutureProvider.family<Execution, String>((ref, workflowId) async {
  final client = ref.watch(apiClientProvider);
  return client.executeWorkflow(workflowId);
});
