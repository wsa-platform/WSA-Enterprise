import 'package:flutter/material.dart';

void main() => runApp(const WsaEnterpriseApp());

class WsaEnterpriseApp extends StatelessWidget {
  const WsaEnterpriseApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'WSA Enterprise',
      theme: ThemeData(colorScheme: ColorScheme.fromSeed(seedColor: Colors.indigo)),
      home: const Scaffold(
        body: Center(child: Text('WSA Enterprise')),
      ),
    );
  }
}
