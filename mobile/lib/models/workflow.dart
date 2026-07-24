class Workflow {
  final String id;
  final String name;
  final String? description;
  final String status;
  final String triggerType;
  final String? lastRunAt;
  final int version;

  const Workflow({
    required this.id,
    required this.name,
    this.description,
    required this.status,
    required this.triggerType,
    this.lastRunAt,
    required this.version,
  });

  factory Workflow.fromJson(Map<String, dynamic> json) => Workflow(
        id: json['id'] as String,
        name: json['name'] as String,
        description: json['description'] as String?,
        status: json['status'] as String,
        triggerType: json['trigger_type'] as String,
        lastRunAt: json['last_run_at'] as String?,
        version: (json['version'] as num).toInt(),
      );

  bool get isActive => status == 'active';
}
