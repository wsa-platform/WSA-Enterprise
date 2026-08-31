import 'package:flutter/material.dart';

import 'package:provider/provider.dart';

import 'package:wsa_admin/core/auth/auth_controller.dart';

import 'package:wsa_admin/core/theme/theme_manager.dart';

import 'package:wsa_admin/data/api/api_client.dart';

import 'package:wsa_admin/l10n/strings.dart';

import 'package:wsa_admin/presentation/widgets/crud_helpers.dart';

import 'package:wsa_admin/theme/admin_theme.dart';



class SettingsScreen extends StatefulWidget {

  const SettingsScreen({super.key, required this.client, required this.auth});



  final ApiClient client;

  final AuthController auth;



  @override

  State<SettingsScreen> createState() => _SettingsScreenState();

}



class _SettingsScreenState extends State<SettingsScreen> {

  bool _loading = true;

  Map<String, dynamic> _orgSettings = {};

  bool _notifyEmail = true;

  bool _notifyPush = true;

  bool _notifyInApp = true;
  Map<String, dynamic> _providers = {};
  String? _testingProvider;



  @override

  void initState() {

    super.initState();

    _load();

  }



  Future<void> _load() async {

    setState(() => _loading = true);

    try {

      final userSettings = await widget.client.adminModules.userSettings();

      Map<String, dynamic> orgSettings = {};

      if (widget.client.hasPermission('access.manage')) {

        try { orgSettings = await widget.client.adminModules.organizationSettings(); } catch (_) {}

      }

      Map<String, dynamic> providers = {};

      try {
        final status = await widget.client.auth.providersStatus();
        providers = Map<String, dynamic>.from(status['providers'] as Map? ?? {});
      } catch (_) {}

      if (!mounted) return;

      setState(() {

        _orgSettings = orgSettings;

        _notifyEmail = _settingBool(userSettings, 'notifications.email', true);

        _notifyPush = _settingBool(userSettings, 'notifications.push', true);

        _notifyInApp = _settingBool(userSettings, 'notifications.in_app', true);
        _providers = providers;

        _loading = false;

      });

    } catch (_) {

      if (mounted) setState(() => _loading = false);

    }

  }



  String? _settingValue(Map<String, dynamic> settings, String key) {

    final val = settings[key];

    if (val is Map) return val['value']?.toString();

    return val?.toString();

  }



  bool _settingBool(Map<String, dynamic> settings, String key, bool fallback) {

    final val = _settingValue(settings, key);

    if (val == null) return fallback;

    return val == 'true' || val == '1';

  }



  Future<void> _saveUserSettings(ThemeManager themeManager) async {

    try {

      await widget.client.adminModules.updateUserSettings({

        'notifications.email': _notifyEmail,

        'notifications.push': _notifyPush,

        'notifications.in_app': _notifyInApp,

        'appearance.theme': themeManager.themeMode == ThemeMode.dark ? 'dark' : 'light',

        'appearance.primary_color': _colorToHex(themeManager.primaryColor),

        'appearance.secondary_color': _colorToHex(themeManager.secondaryColor),

        'appearance.sidebar_color': _colorToHex(themeManager.sidebarColor),

      });

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.settingsSaved)));

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  String _colorToHex(Color color) =>

      '#${color.toARGB32().toRadixString(16).padLeft(8, '0').substring(2)}';



  Future<void> _editProfile() async {

    final user = widget.client.user;

    final data = await SimpleFormDialog.show(context,

      title: Ar.settingsEditProfile,

      initialValues: {

        'name': user?['name']?.toString() ?? '',

        'email': user?['email']?.toString() ?? '',

      },

      fields: const [

        FormFieldDef(key: 'name', label: Ar.colName),

        FormFieldDef(key: 'email', label: Ar.colEmail),

      ],

    );

    if (data == null) return;

    try {

      await widget.client.adminModules.updateProfile(data);

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  Future<void> _changePassword() async {

    final data = await SimpleFormDialog.show(context,

      title: Ar.settingsChangePassword,

      fields: const [

        FormFieldDef(key: 'password', label: Ar.password, obscure: true),

        FormFieldDef(key: 'password_confirmation', label: 'تأكيد كلمة المرور', obscure: true),

      ],

    );

    if (data == null) return;

    try {

      await widget.client.adminModules.updateProfile(data);

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }

  Future<void> _testProvider(String key) async {
    setState(() => _testingProvider = key);
    try {
      final result = await widget.client.auth.testProvider(key);
      if (!mounted) return;
      final providers = Map<String, dynamic>.from(_providers);
      providers[key] = result;
      setState(() => _providers = providers);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(result['success'] == true ? Ar.providerTestSuccess : (result['error']?.toString() ?? Ar.providerTestFailed)),
      ));
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    } finally {
      if (mounted) setState(() => _testingProvider = null);
    }
  }

  String _providerLabel(Map<String, dynamic> info) {
    final label = info['label']?.toString();
    if (label != null && label.isNotEmpty) return label;
    if (info['configured'] != true) return Ar.providerUnconfigured;
    if (info['connected'] == true) return Ar.providerConnected;
    return Ar.providerDisconnected;
  }

  Color _providerColor(Map<String, dynamic> info) {
    if (info['connected'] == true) return Colors.green;
    if (info['configured'] != true) return Colors.grey;
    return Colors.orange;
  }

  @override

  Widget build(BuildContext context) {

    final user = widget.client.user;

    final userName = user?['name']?.toString() ?? Ar.notAvailable;

    final userEmail = user?['email']?.toString() ?? Ar.notAvailable;

    final themeManager = context.watch<ThemeManager>();



    if (_loading) {

      return const Center(child: CircularProgressIndicator());

    }



    return ListView(

      padding: const EdgeInsets.all(24),

      children: [

        Text(Ar.navSettings, style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold)),

        const SizedBox(height: 24),

        Text(Ar.settingsAccount, style: Theme.of(context).textTheme.titleMedium),

        const SizedBox(height: 12),

        Card(

          child: Column(

            children: [

              ListTile(

                leading: CircleAvatar(child: Text(userName.isNotEmpty ? userName.characters.first : '?')),

                title: Text(userName),

                subtitle: Text(userEmail),

                trailing: IconButton(icon: const Icon(Icons.edit), onPressed: _editProfile),

              ),

              const Divider(height: 1),

              ListTile(

                leading: const Icon(Icons.lock_outline),

                title: const Text(Ar.settingsChangePassword),

                onTap: _changePassword,

              ),

              const Divider(height: 1),

              ListTile(

                leading: const Icon(Icons.logout),

                title: const Text(Ar.logout),

                onTap: () async => widget.auth.logout(),

              ),

            ],

          ),

        ),

        const SizedBox(height: 24),

        Text(Ar.settingsAppearance, style: Theme.of(context).textTheme.titleMedium),

        const SizedBox(height: 12),

        Card(

          child: Column(

            children: [

              ListTile(

                title: const Text(Ar.settingsTheme),

                trailing: DropdownButton<ThemeMode>(

                  value: themeManager.themeMode,

                  items: const [

                    DropdownMenuItem(value: ThemeMode.light, child: Text(Ar.settingsThemeLight)),

                    DropdownMenuItem(value: ThemeMode.dark, child: Text(Ar.settingsThemeDark)),

                  ],

                  onChanged: (v) { if (v != null) themeManager.setThemeMode(v); },

                ),

              ),

              ListTile(

                title: const Text(Ar.settingsPrimaryColor),

                trailing: _ColorDot(color: themeManager.primaryColor, presets: const [

                  AdminTheme.defaultPrimary,

                  Color(0xFF1565C0),

                  Color(0xFF6A1B9A),

                ], onPick: themeManager.setPrimaryColor),

              ),

              ListTile(

                title: const Text(Ar.settingsTheme),

                subtitle: const Text('اللون الثانوي'),

                trailing: _ColorDot(color: themeManager.secondaryColor, presets: const [

                  AdminTheme.defaultSecondary,

                  Color(0xFF2E7D32),

                  Color(0xFF00838F),

                ], onPick: themeManager.setSecondaryColor),

              ),

              const Divider(height: 1),

              const ListTile(

                leading: Icon(Icons.language),

                title: Text(Ar.settingsLanguage),

                subtitle: Text(Ar.settingsRtlHint),

              ),

            ],

          ),

        ),

        const SizedBox(height: 24),

        Text(Ar.settingsNotifications, style: Theme.of(context).textTheme.titleMedium),

        const SizedBox(height: 12),

        Card(

          child: Column(

            children: [

              SwitchListTile(

                title: const Text(Ar.settingsNotifyEmail),

                value: _notifyEmail,

                onChanged: (v) => setState(() => _notifyEmail = v),

              ),

              SwitchListTile(

                title: const Text(Ar.settingsNotifyPush),

                value: _notifyPush,

                onChanged: (v) => setState(() => _notifyPush = v),

              ),

              SwitchListTile(

                title: const Text(Ar.settingsNotifyInApp),

                value: _notifyInApp,

                onChanged: (v) => setState(() => _notifyInApp = v),

              ),

            ],

          ),

        ),

        const SizedBox(height: 16),

        FilledButton(onPressed: () => _saveUserSettings(themeManager), child: const Text(Ar.save)),
        if (_providers.isNotEmpty) ...[
          const SizedBox(height: 24),
          Text(Ar.externalProviders, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 12),
          Card(
            child: Column(
              children: _providers.entries.map((e) {
                final info = Map<String, dynamic>.from(e.value as Map);
                final testing = _testingProvider == e.key;
                final lastTest = info['last_test_at']?.toString();
                final lastError = info['last_test_error']?.toString();
                return Column(
                  children: [
                    ListTile(
                      title: Text(e.key),
                      subtitle: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('${info['provider'] ?? 'none'} • ${info['config_status'] ?? ''}'),
                          if (lastTest != null && lastTest.isNotEmpty)
                            Text('${Ar.providerLastTest}: $lastTest', style: Theme.of(context).textTheme.bodySmall),
                          if (lastError != null && lastError.isNotEmpty)
                            Text(lastError, style: TextStyle(color: Theme.of(context).colorScheme.error, fontSize: 12)),
                        ],
                      ),
                      trailing: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            _providerLabel(info),
                            style: TextStyle(color: _providerColor(info), fontWeight: FontWeight.w600),
                          ),
                          const SizedBox(width: 8),
                          IconButton(
                            tooltip: Ar.providerTest,
                            onPressed: testing ? null : () => _testProvider(e.key),
                            icon: testing
                                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                                : const Icon(Icons.link),
                          ),
                        ],
                      ),
                    ),
                    if (e.key != _providers.keys.last) const Divider(height: 1),
                  ],
                );
              }).toList(),
            ),
          ),
        ],
        if (_orgSettings.isNotEmpty) ...[

          const SizedBox(height: 24),

          Text(Ar.settingsSystem, style: Theme.of(context).textTheme.titleMedium),

          const SizedBox(height: 12),

          Card(

            child: Column(

              children: _orgSettings.entries.map((e) => ListTile(

                title: Text(e.key),

                subtitle: Text(e.value.toString()),

              )).toList(),

            ),

          ),

        ],

      ],

    );

  }

}



class _ColorDot extends StatelessWidget {

  const _ColorDot({required this.color, required this.presets, required this.onPick});



  final Color color;

  final List<Color> presets;

  final ValueChanged<Color> onPick;



  @override

  Widget build(BuildContext context) {

    return PopupMenuButton<Color>(

      itemBuilder: (_) => presets.map((c) => PopupMenuItem(value: c, child: Row(

        children: [

          CircleAvatar(radius: 10, backgroundColor: c),

          const SizedBox(width: 8),

          Text(_colorToHex(c)),

        ],

      ))).toList(),

      onSelected: onPick,

      child: CircleAvatar(backgroundColor: color),

    );

  }



  String _colorToHex(Color c) => '#${c.toARGB32().toRadixString(16).padLeft(8, '0').substring(2)}';

}

