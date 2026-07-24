class User {
  final String id;
  final String name;
  final String email;
  final String tenantId;

  const User({
    required this.id,
    required this.name,
    required this.email,
    required this.tenantId,
  });

  factory User.fromJson(Map<String, dynamic> json) => User(
        id: json['id'] as String,
        name: json['name'] as String,
        email: json['email'] as String,
        tenantId: json['tenant_id'] as String,
      );
}
