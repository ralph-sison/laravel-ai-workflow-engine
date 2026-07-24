import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/user.dart';
import '../services/api_client.dart';
import '../services/auth_service.dart';

final authServiceProvider = Provider<AuthService>((ref) => AuthService());

final apiClientProvider = Provider<ApiClient>((ref) => ApiClient());

final authProvider = AsyncNotifierProvider<AuthNotifier, User?>(() {
  return AuthNotifier();
});

class AuthNotifier extends AsyncNotifier<User?> {
  @override
  Future<User?> build() async {
    final authService = ref.read(authServiceProvider);
    final token = await authService.getToken();
    if (token == null) return null;

    final client = ref.read(apiClientProvider);
    client.setToken(token);

    try {
      return await client.me();
    } catch (_) {
      await authService.clearToken();
      return null;
    }
  }

  Future<void> login(String email, String password) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() async {
      final client = ref.read(apiClientProvider);
      final authService = ref.read(authServiceProvider);

      final result = await client.login(email: email, password: password);
      await authService.saveToken(result.token);
      client.setToken(result.token);
      return result.user;
    });
  }

  Future<void> logout() async {
    try {
      await ref.read(apiClientProvider).logout();
    } catch (_) {}
    await ref.read(authServiceProvider).clearToken();
    ref.read(apiClientProvider).clearToken();
    state = const AsyncData(null);
  }
}
