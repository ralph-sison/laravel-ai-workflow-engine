import 'package:dio/dio.dart';
import '../core/constants.dart';
import '../models/execution.dart';
import '../models/user.dart';
import '../models/workflow.dart';

class ApiClient {
  late final Dio _dio;

  ApiClient({String? token}) {
    _dio = Dio(
      BaseOptions(
        baseUrl: AppConstants.baseUrl,
        connectTimeout: AppConstants.connectTimeout,
        receiveTimeout: AppConstants.receiveTimeout,
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          if (token != null) 'Authorization': 'Bearer $token',
        },
      ),
    );
  }

  void setToken(String token) {
    _dio.options.headers['Authorization'] = 'Bearer $token';
  }

  void clearToken() {
    _dio.options.headers.remove('Authorization');
  }

  // Auth

  Future<({String token, User user})> login({
    required String email,
    required String password,
  }) async {
    final res = await _dio.post('/auth/login', data: {
      'email': email,
      'password': password,
    });
    final data = res.data['data'] as Map<String, dynamic>;
    return (
      token: data['token'] as String,
      user: User.fromJson(data['user'] as Map<String, dynamic>),
    );
  }

  Future<void> logout() async {
    await _dio.post('/auth/logout');
  }

  Future<User> me() async {
    final res = await _dio.get('/me');
    return User.fromJson(res.data['data'] as Map<String, dynamic>);
  }

  // Workflows

  Future<List<Workflow>> getWorkflows() async {
    final res = await _dio.get('/workflows');
    final list = res.data['data'] as List<dynamic>;
    return list.map((e) => Workflow.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<List<Execution>> getExecutions(String workflowId) async {
    final res = await _dio.get('/workflows/$workflowId/executions');
    final list = res.data['data'] as List<dynamic>;
    return list.map((e) => Execution.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<Execution> getExecution(String workflowId, String executionId) async {
    final res = await _dio.get('/workflows/$workflowId/executions/$executionId');
    return Execution.fromJson(res.data['data'] as Map<String, dynamic>);
  }

  Future<Execution> executeWorkflow(String workflowId) async {
    final res = await _dio.post('/workflows/$workflowId/execute');
    return Execution.fromJson(res.data['data'] as Map<String, dynamic>);
  }
}
