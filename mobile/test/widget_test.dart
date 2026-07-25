import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flowforge_mobile/main.dart';

void main() {
  testWidgets('App renders without crashing', (WidgetTester tester) async {
    await tester.pumpWidget(const ProviderScope(child: FlowForgeApp()));
    await tester.pump();
    // App boots — router redirects unauthenticated user to login
    expect(find.text('FlowForge'), findsWidgets);
  });
}
