class Execution {
  final String id;
  final String workflowId;
  final String status;
  final String triggerType;
  final String startedAt;
  final String? finishedAt;
  final List<ExecutionLog> logs;

  const Execution({
    required this.id,
    required this.workflowId,
    required this.status,
    required this.triggerType,
    required this.startedAt,
    this.finishedAt,
    this.logs = const [],
  });

  factory Execution.fromJson(Map<String, dynamic> json) => Execution(
        id: json['id'] as String,
        workflowId: json['workflow_id'] as String,
        status: json['status'] as String,
        triggerType: json['trigger_type'] as String,
        startedAt: json['started_at'] as String,
        finishedAt: json['finished_at'] as String?,
        logs: (json['logs'] as List<dynamic>?)
                ?.map((e) => ExecutionLog.fromJson(e as Map<String, dynamic>))
                .toList() ??
            [],
      );
}

class ExecutionLog {
  final String id;
  final String stepId;
  final String status;
  final String? error;
  final int? durationMs;
  final int attempt;

  const ExecutionLog({
    required this.id,
    required this.stepId,
    required this.status,
    this.error,
    this.durationMs,
    required this.attempt,
  });

  factory ExecutionLog.fromJson(Map<String, dynamic> json) => ExecutionLog(
        id: json['id'] as String,
        stepId: json['step_id'] as String,
        status: json['status'] as String,
        error: json['error'] as String?,
        durationMs: json['duration_ms'] as int?,
        attempt: (json['attempt'] as num).toInt(),
      );
}
