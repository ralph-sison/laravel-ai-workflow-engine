import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../providers/auth_provider.dart';
import '../screens/dashboard_screen.dart';
import '../screens/execution_detail_screen.dart';
import '../screens/login_screen.dart';
import '../screens/workflow_detail_screen.dart';
import '../screens/workflows_screen.dart';
import '../models/execution.dart';
import '../models/workflow.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authProvider);

  return GoRouter(
    initialLocation: '/dashboard',
    redirect: (context, state) {
      final isLoggedIn = authState.value != null;
      final isLoggingIn = state.uri.path == '/login';

      if (!isLoggedIn && !isLoggingIn) return '/login';
      if (isLoggedIn && isLoggingIn) return '/dashboard';
      return null;
    },
    routes: [
      GoRoute(
        path: '/login',
        builder: (context, _) => const LoginScreen(),
      ),
      GoRoute(
        path: '/dashboard',
        builder: (context, _) => const DashboardScreen(),
      ),
      GoRoute(
        path: '/workflows',
        builder: (context, _) => const WorkflowsScreen(),
      ),
      GoRoute(
        path: '/workflows/:id',
        builder: (_, state) => WorkflowDetailScreen(
          workflowId: state.pathParameters['id']!,
          workflow: state.extra as Workflow?,
        ),
      ),
      GoRoute(
        path: '/workflows/:workflowId/executions/:executionId',
        builder: (_, state) => ExecutionDetailScreen(
          workflowId: state.pathParameters['workflowId']!,
          executionId: state.pathParameters['executionId']!,
          execution: state.extra as Execution?,
        ),
      ),
    ],
  );
});
