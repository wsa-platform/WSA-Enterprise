import 'package:flutter/material.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/m22_strings.dart';
import 'package:wsa_admin/presentation/widgets/admin_data_list.dart';
import 'package:wsa_admin/presentation/widgets/crud_helpers.dart';
import 'package:wsa_admin/presentation/widgets/module_screen_layout.dart';
import 'package:wsa_admin/presentation/widgets/paginated_data_list.dart';

class CommunicationsScreen extends StatefulWidget {
  const CommunicationsScreen({super.key, required this.client});

  final ApiClient client;

  @visibleForTesting
  static Future<CommunicationsData> Function(ApiClient client)? debugLoader;

  @override
  State<CommunicationsScreen> createState() => _CommunicationsScreenState();
}

class _CommunicationsScreenState extends State<CommunicationsScreen> {
  bool _loading = true;
  String? _error;
  Map<String, dynamic> _providers = {};
  List<Map<String, dynamic>> _mailingLists = [];
  int? _draftId;

  final _subject = TextEditingController();
  final _body = TextEditingController();
  final _contactName = TextEditingController();
  final _contactEmail = TextEditingController();
  final _contactPhone = TextEditingController();
  final _searchController = TextEditingController();

  bool _isBulk = false;
  bool _saveContact = false;
  String? _channel;
  String? _mailingListId;
  int? _bulkRecipientCount;
  List<Map<String, dynamic>> _suggestions = [];
  bool _sending = false;

  final _historyKey = GlobalKey<PaginatedDataListState<Map<String, dynamic>>>();
  final _contactsKey = GlobalKey<PaginatedDataListState<Map<String, dynamic>>>();

  @override
  void initState() {
    super.initState();
    _load();
    _searchController.addListener(_onSearchChanged);
  }

  @override
  void dispose() {
    _subject.dispose();
    _body.dispose();
    _contactName.dispose();
    _contactEmail.dispose();
    _contactPhone.dispose();
    _searchController.removeListener(_onSearchChanged);
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final status = await widget.client.adminModules.communicationsProviders();
      final providers = Map<String, dynamic>.from(status['providers'] as Map? ?? {});
      final lists = await widget.client.adminModules.mailingLists();
      if (!mounted) return;
      setState(() {
        _providers = providers;
        _mailingLists = lists.map((l) => Map<String, dynamic>.from(l as Map)).toList();
        _channel ??= providers.keys.isNotEmpty ? providers.keys.first : null;
        _loading = false;
      });
      _historyKey.currentState?.reload();
    } catch (error) {
      if (!mounted) return;
      setState(() { _error = error.toString(); _loading = false; });
    }
  }

  void _onSearchChanged() {
    final q = _searchController.text.trim();
    if (q.length < 2) {
      setState(() => _suggestions = []);
      return;
    }
    widget.client.adminModules.searchContacts(q).then((results) {
      if (mounted) setState(() => _suggestions = results);
    }).catchError((_) {});
  }

  void _selectContact(Map<String, dynamic> contact) {
    _contactName.text = contact['name']?.toString() ?? '';
    _contactEmail.text = contact['email']?.toString() ?? '';
    _contactPhone.text = contact['phone']?.toString() ?? '';
    _searchController.clear();
    setState(() => _suggestions = []);
  }

  void _clearForm() {
    _subject.clear();
    _body.clear();
    _contactName.clear();
    _contactEmail.clear();
    _contactPhone.clear();
    _searchController.clear();
    setState(() {
      _draftId = null;
      _mailingListId = null;
      _bulkRecipientCount = null;
      _saveContact = false;
      _suggestions = [];
    });
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.commCleared)));
  }

  String? _validate() {
    if (_subject.text.trim().isEmpty) return Ar.commValidationSubject;
    if (_body.text.trim().isEmpty) return Ar.commValidationBody;
    if (_channel == null || !_providers.containsKey(_channel)) return Ar.commValidationChannel;
    if (_isBulk) {
      if (_mailingListId == null) return Ar.commValidationMailingList;
    } else {
      if (_channel == 'email' && _contactEmail.text.trim().isEmpty) return Ar.commValidationEmail;
      if ((_channel == 'sms' || _channel == 'whatsapp') && _contactPhone.text.trim().isEmpty) {
        return Ar.commValidationPhone;
      }
    }
    return null;
  }

  Map<String, dynamic> _composeBody() {
    final body = <String, dynamic>{
      'subject': _subject.text.trim(),
      'body': _body.text.trim(),
      'channel': _channel,
      'is_bulk': _isBulk,
    };
    if (_isBulk) {
      body['recipient_mode'] = 'bulk';
      body['mailing_list_id'] = int.tryParse(_mailingListId ?? '');
    } else {
      body['recipient_mode'] = 'individual';
      body['recipients'] = [
        {
          'name': _contactName.text.trim(),
          if (_contactEmail.text.trim().isNotEmpty) 'email': _contactEmail.text.trim(),
          if (_contactPhone.text.trim().isNotEmpty) 'phone': _contactPhone.text.trim(),
        },
      ];
    }
    return body;
  }

  Future<void> _preview() async {
    final validation = _validate();
    if (validation != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(validation)));
      return;
    }
    try {
      final preview = await widget.client.adminModules.previewMessage(_composeBody());
      if (!mounted) return;
      await showDialog<void>(
        context: context,
        builder: (_) => AlertDialog(
          title: const Text(Ar.commPreview),
          content: SingleChildScrollView(
            child: Directionality(
              textDirection: TextDirection.rtl,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text('${Ar.commSubject}: ${preview['subject']}'),
                  const SizedBox(height: 8),
                  Text('${Ar.colChannel}: ${preview['channel']}'),
                  const SizedBox(height: 8),
                  Text(preview['body']?.toString() ?? '', textDirection: TextDirection.rtl),
                  const SizedBox(height: 8),
                  Text('${Ar.commTotalRecipients}: ${preview['recipient_count']}'),
                  if (preview['mailing_list_member_count'] != null)
                    Text('${Ar.commRecipientCount}: ${preview['mailing_list_member_count']}'),
                ],
              ),
            ),
          ),
          actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text(Ar.cancel))],
        ),
      );
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  Future<void> _saveDraft() async {
    final validation = _validate();
    if (validation != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(validation)));
      return;
    }
    try {
      if (_draftId != null) {
        await widget.client.adminModules.updateMessage(_draftId!, _composeBody());
      } else {
        final created = await widget.client.adminModules.composeMessage(_composeBody());
        _draftId = created['id'] as int?;
      }
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.commDraftSaved)));
      _historyKey.currentState?.reload();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  Future<bool> _confirmBulkSend(int count) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text(Ar.commConfirmBulkSend),
        content: Text('${Ar.commConfirmBulkMessage} $count ${Ar.commTotalRecipients}؟'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text(Ar.cancel)),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text(Ar.commSendNow)),
        ],
      ),
    );
    return confirmed == true;
  }

  Future<void> _send() async {
    final validation = _validate();
    if (validation != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(validation)));
      return;
    }

    if (_isBulk) {
      final preview = await widget.client.adminModules.previewMessage(_composeBody());
      final count = preview['recipient_count'] as int? ?? 0;
      if (count == 0) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.emptyData)));
        return;
      }
      if (!await _confirmBulkSend(count)) return;
    }

    setState(() => _sending = true);
    try {
      Map<String, dynamic> message;
      if (_draftId != null) {
        message = await widget.client.adminModules.updateMessage(_draftId!, _composeBody());
      } else {
        message = await widget.client.adminModules.composeMessage(_composeBody());
        _draftId = message['id'] as int?;
      }
      final id = message['id'] as int? ?? _draftId;
      if (id == null) throw Exception(Ar.unknownError);

      final contactPayload = (!_isBulk && _saveContact)
          ? {
              'save_contact': true,
              'contact_name': _contactName.text.trim(),
              'contact_email': _contactEmail.text.trim(),
              'contact_phone': _contactPhone.text.trim(),
            }
          : null;

      final sent = await widget.client.adminModules.sendMessage(id, contact: contactPayload);
      final stats = Map<String, dynamic>.from(sent['delivery_stats'] as Map? ?? {});
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text('${Ar.commSentCount}: ${stats['sent'] ?? 0} — ${Ar.commFailedCount}: ${stats['failed'] ?? 0}'),
      ));
      _clearForm();
      _historyKey.currentState?.reload();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  Future<void> _updateBulkCount() async {
    if (_mailingListId == null) {
      setState(() => _bulkRecipientCount = null);
      return;
    }
    try {
      final preview = await widget.client.adminModules.previewMessage({
        ..._composeBody(),
        'subject': _subject.text.trim().isEmpty ? '—' : _subject.text.trim(),
        'body': _body.text.trim().isEmpty ? '—' : _body.text.trim(),
      });
      if (mounted) setState(() => _bulkRecipientCount = preview['recipient_count'] as int?);
    } catch (_) {}
  }

  String _channelLabel(String key) => switch (key) {
    'email' => Ar.commChannelEmail,
    'sms' => Ar.commChannelSms,
    'whatsapp' => Ar.commChannelWhatsapp,
    _ => key,
  };

  String _statusLabel(String? status) => switch (status) {
    'draft' => Ar.commStatusDraft,
    'sent' => Ar.commStatusSent,
    'failed' => Ar.commStatusFailed,
    'partially_sent' => Ar.commStatusPartial,
    'pending' => Ar.commStatusPending,
    _ => status ?? '',
  };

  Future<void> _addMailingList() async {
    final data = await SimpleFormDialog.show(context,
      title: Ar.commAddMailingList,
      fields: const [
        FormFieldDef(key: 'name', label: Ar.colName),
        FormFieldDef(key: 'description', label: Ar.colDescription, required: false),
      ],
    );
    if (data == null) return;
    try {
      await widget.client.adminModules.createMailingList(data);
      await _load();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  Future<void> _deleteMailingList(int id) async {
    if (!await confirmDelete(context)) return;
    try {
      await widget.client.adminModules.deleteMailingList(id);
      await _load();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  Future<void> _manageMailingListMembers(Map<String, dynamic> list) async {
    final listId = list['id'] as int;
    await showDialog<void>(
      context: context,
      builder: (_) => _MailingListMembersDialog(client: widget.client, listId: listId, listName: list['name']?.toString() ?? ''),
    );
    await _load();
  }

  Future<void> _addContact() async {
    final data = await SimpleFormDialog.show(context,
      title: Ar.commAddContact,
      fields: const [
        FormFieldDef(key: 'name', label: Ar.commContactName, required: false),
        FormFieldDef(key: 'email', label: Ar.email, required: false),
        FormFieldDef(key: 'phone', label: Ar.phoneNumber, required: false),
      ],
    );
    if (data == null) return;
    try {
      await widget.client.adminModules.createContact(data);
      _contactsKey.currentState?.reload();
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  Future<void> _editContact(Map<String, dynamic> contact) async {
    final data = await SimpleFormDialog.show(context,
      title: Ar.edit,
      initialValues: {
        'name': contact['name']?.toString() ?? '',
        'email': contact['email']?.toString() ?? '',
        'phone': contact['phone']?.toString() ?? '',
      },
      fields: const [
        FormFieldDef(key: 'name', label: Ar.commContactName, required: false),
        FormFieldDef(key: 'email', label: Ar.email, required: false),
        FormFieldDef(key: 'phone', label: Ar.phoneNumber, required: false),
      ],
    );
    if (data == null) return;
    try {
      await widget.client.adminModules.updateContact(contact['id'] as int, data);
      _contactsKey.currentState?.reload();
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  Future<void> _deleteContact(int id) async {
    if (!await confirmDelete(context)) return;
    try {
      await widget.client.adminModules.deleteContact(id);
      _contactsKey.currentState?.reload();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return ModuleScreenLayout(
      title: Ar.commCenterTitle,
      loading: _loading,
      error: _error,
      empty: false,
      onRetry: _load,
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (_providers.isEmpty)
              Card(
                color: Colors.orange.shade50,
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Text(Ar.commNoProviders, style: TextStyle(color: Colors.orange.shade900)),
                ),
              )
            else
              Wrap(
                spacing: 8,
                runSpacing: 4,
                children: _providers.entries.map((e) {
                  final info = Map<String, dynamic>.from(e.value as Map);
                  return Chip(
                    avatar: Icon(Icons.check_circle, size: 16, color: Colors.green.shade700),
                    label: Text('${_channelLabel(e.key)}: ${info['provider'] ?? e.key}'),
                    backgroundColor: Colors.green.shade50,
                  );
                }).toList(),
              ),
            const SizedBox(height: 16),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Directionality(
                  textDirection: TextDirection.rtl,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      SegmentedButton<bool>(
                        segments: const [
                          ButtonSegment(value: false, label: Text(Ar.commModeIndividual), icon: Icon(Icons.person_outline)),
                          ButtonSegment(value: true, label: Text(Ar.commModeBulk), icon: Icon(Icons.groups_outlined)),
                        ],
                        selected: {_isBulk},
                        onSelectionChanged: (s) => setState(() => _isBulk = s.first),
                      ),
                      const SizedBox(height: 16),
                      TextField(
                        controller: _subject,
                        decoration: const InputDecoration(labelText: Ar.commSubject, border: OutlineInputBorder()),
                        textDirection: TextDirection.rtl,
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _body,
                        decoration: const InputDecoration(labelText: Ar.commBody, border: OutlineInputBorder(), alignLabelWithHint: true),
                        textDirection: TextDirection.rtl,
                        minLines: 4,
                        maxLines: 8,
                      ),
                      const SizedBox(height: 12),
                      if (_providers.isNotEmpty)
                        DropdownButtonFormField<String>(
                          value: _channel,
                          decoration: const InputDecoration(labelText: Ar.commSelectChannel, border: OutlineInputBorder()),
                          items: _providers.keys.map((k) => DropdownMenuItem(value: k, child: Text(_channelLabel(k)))).toList(),
                          onChanged: (v) => setState(() => _channel = v),
                        ),
                      if (_channel != null && _providers[_channel] != null) ...[
                        const SizedBox(height: 8),
                        ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: const Icon(Icons.cloud_outlined),
                          title: Text(Ar.commSelectProvider),
                          subtitle: Text('${_providers[_channel!]['provider']} — ${Ar.providerConnected}'),
                        ),
                      ],
                      const SizedBox(height: 12),
                      if (!_isBulk) ...[
                        TextField(
                          controller: _searchController,
                          decoration: const InputDecoration(
                            labelText: Ar.commSearchContact,
                            border: OutlineInputBorder(),
                            prefixIcon: Icon(Icons.search),
                          ),
                          textDirection: TextDirection.rtl,
                        ),
                        if (_suggestions.isNotEmpty)
                          Material(
                            elevation: 2,
                            child: Column(
                              children: _suggestions.map((c) => ListTile(
                                title: Text(c['name']?.toString() ?? c['email']?.toString() ?? ''),
                                subtitle: Text('${c['email'] ?? ''} ${c['phone'] ?? ''}'.trim()),
                                onTap: () => _selectContact(c),
                              )).toList(),
                            ),
                          ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: _contactName,
                          decoration: const InputDecoration(labelText: Ar.commContactName, border: OutlineInputBorder()),
                          textDirection: TextDirection.rtl,
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: _contactEmail,
                          decoration: const InputDecoration(labelText: Ar.email, border: OutlineInputBorder()),
                          keyboardType: TextInputType.emailAddress,
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: _contactPhone,
                          decoration: const InputDecoration(labelText: Ar.phoneNumber, border: OutlineInputBorder()),
                          keyboardType: TextInputType.phone,
                        ),
                        CheckboxListTile(
                          contentPadding: EdgeInsets.zero,
                          title: const Text(Ar.commSaveContact),
                          value: _saveContact,
                          onChanged: (v) => setState(() => _saveContact = v ?? false),
                        ),
                      ] else ...[
                        Row(
                          children: [
                            Expanded(
                              child: DropdownButtonFormField<String>(
                                value: _mailingListId,
                                decoration: const InputDecoration(labelText: Ar.commSelectMailingList, border: OutlineInputBorder()),
                                items: _mailingLists.map((l) => DropdownMenuItem(
                                  value: '${l['id']}',
                                  child: Text('${l['name']} (${l['members_count'] ?? 0})'),
                                )).toList(),
                                onChanged: (v) {
                                  setState(() => _mailingListId = v);
                                  _updateBulkCount();
                                },
                              ),
                            ),
                            IconButton(onPressed: _addMailingList, icon: const Icon(Icons.add), tooltip: Ar.commAddMailingList),
                          ],
                        ),
                        if (_bulkRecipientCount != null)
                          Padding(
                            padding: const EdgeInsets.only(top: 8),
                            child: Text('${Ar.commRecipientCount}: $_bulkRecipientCount', style: Theme.of(context).textTheme.titleSmall),
                          ),
                      ],
                      const SizedBox(height: 16),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        alignment: WrapAlignment.end,
                        children: [
                          OutlinedButton.icon(onPressed: _preview, icon: const Icon(Icons.visibility_outlined), label: const Text(Ar.commPreview)),
                          OutlinedButton.icon(onPressed: _saveDraft, icon: const Icon(Icons.save_outlined), label: const Text(Ar.commSaveDraft)),
                          OutlinedButton.icon(onPressed: _clearForm, icon: const Icon(Icons.clear_outlined), label: const Text(Ar.commClear)),
                          FilledButton.icon(
                            onPressed: _sending ? null : _send,
                            icon: _sending ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.send),
                            label: const Text(Ar.commSendNow),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(height: 24),
            Text(Ar.commHistory, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 8),
            SizedBox(
              height: 360,
              child: PaginatedDataList<Map<String, dynamic>>(
                key: _historyKey,
                fetchPage: (page, perPage) => widget.client.adminModules.communicationMessagesPage(page: page, perPage: perPage),
                columns: [
                  (item) => AdminDataColumn(label: Ar.commSubject, cellBuilder: (_, __) => Text(item['subject']?.toString() ?? '', textDirection: TextDirection.rtl)),
                  (item) => AdminDataColumn(label: Ar.colChannel, cellBuilder: (_, __) => Text(_channelLabel(item['channel']?.toString() ?? ''))),
                  (item) => AdminDataColumn(label: Ar.colStatus, cellBuilder: (_, __) => Text(_statusLabel(item['status']?.toString()))),
                  (item) => AdminDataColumn(label: Ar.commSentCount, cellBuilder: (_, __) => Text('${item['sent_count'] ?? 0}')),
                  (item) => AdminDataColumn(label: Ar.commFailedCount, cellBuilder: (_, __) => Text('${item['failed_count'] ?? 0}')),
                  (item) => AdminDataColumn(label: Ar.colDate, cellBuilder: (_, __) => Text(item['created_at']?.toString() ?? '')),
                ],
              ),
            ),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(child: Text(Ar.commMailingLists, style: Theme.of(context).textTheme.titleMedium)),
                TextButton.icon(onPressed: _addMailingList, icon: const Icon(Icons.add), label: const Text(Ar.commAddMailingList)),
              ],
            ),
            const SizedBox(height: 8),
            if (_mailingLists.isEmpty)
              Card(child: Padding(padding: const EdgeInsets.all(16), child: Text(Ar.emptyData)))
            else
              ..._mailingLists.map((list) => Card(
                child: ListTile(
                  title: Text(list['name']?.toString() ?? '', textDirection: TextDirection.rtl),
                  subtitle: Text('${list['members_count'] ?? 0} ${Ar.commMembers}'),
                  trailing: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      IconButton(
                        icon: const Icon(Icons.group_outlined),
                        tooltip: Ar.commManageMembers,
                        onPressed: () => _manageMailingListMembers(list),
                      ),
                      IconButton(
                        icon: const Icon(Icons.delete_outline),
                        tooltip: Ar.commDeleteMailingList,
                        onPressed: () => _deleteMailingList(list['id'] as int),
                      ),
                    ],
                  ),
                ),
              )),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(child: Text(Ar.commContacts, style: Theme.of(context).textTheme.titleMedium)),
                TextButton.icon(onPressed: _addContact, icon: const Icon(Icons.person_add_outlined), label: const Text(Ar.commAddContact)),
              ],
            ),
            const SizedBox(height: 8),
            SizedBox(
              height: 320,
              child: PaginatedDataList<Map<String, dynamic>>(
                key: _contactsKey,
                fetchPage: (page, perPage) => widget.client.adminModules.contactsPage(page: page, perPage: perPage),
                columns: [
                  (item) => AdminDataColumn(label: Ar.commContactName, cellBuilder: (_, __) => Text(item['name']?.toString() ?? '—', textDirection: TextDirection.rtl)),
                  (item) => AdminDataColumn(label: Ar.email, cellBuilder: (_, __) => Text(item['email']?.toString() ?? '—')),
                  (item) => AdminDataColumn(label: Ar.phoneNumber, cellBuilder: (_, __) => Text(item['phone']?.toString() ?? '—')),
                  (item) => AdminDataColumn(label: Ar.colDate, cellBuilder: (_, __) => Text(item['last_contacted_at']?.toString() ?? '—')),
                  (item) => AdminDataColumn(
                    label: Ar.actions,
                    cellBuilder: (_, __) => Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        IconButton(icon: const Icon(Icons.edit_outlined, size: 20), onPressed: () => _editContact(item)),
                        IconButton(icon: const Icon(Icons.delete_outline, size: 20), onPressed: () => _deleteContact(item['id'] as int)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class CommunicationsData {
  CommunicationsData({required this.providers});

  final Map<String, dynamic> providers;

  factory CommunicationsData.empty() => CommunicationsData(providers: {});

  static Future<CommunicationsData> load(ApiClient client) async {
    final status = await client.adminModules.communicationsProviders();
    return CommunicationsData(providers: Map<String, dynamic>.from(status['providers'] as Map? ?? {}));
  }
}

class _MailingListMembersDialog extends StatefulWidget {
  const _MailingListMembersDialog({required this.client, required this.listId, required this.listName});

  final ApiClient client;
  final int listId;
  final String listName;

  @override
  State<_MailingListMembersDialog> createState() => _MailingListMembersDialogState();
}

class _MailingListMembersDialogState extends State<_MailingListMembersDialog> {
  final _listKey = GlobalKey<PaginatedDataListState<Map<String, dynamic>>>();

  Future<void> _addMember() async {
    final data = await SimpleFormDialog.show(context,
      title: Ar.commAddMember,
      fields: const [
        FormFieldDef(key: 'email', label: Ar.email, required: false),
        FormFieldDef(key: 'phone', label: Ar.phoneNumber, required: false),
      ],
    );
    if (data == null) return;
    try {
      await widget.client.adminModules.addMailingListMembers(widget.listId, [data]);
      _listKey.currentState?.reload();
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  Future<void> _removeMember(int memberId) async {
    try {
      await widget.client.adminModules.removeMailingListMember(widget.listId, memberId);
      _listKey.currentState?.reload();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text('${Ar.commManageMembers}: ${widget.listName}', textDirection: TextDirection.rtl),
      content: SizedBox(
        width: 480,
        height: 360,
        child: PaginatedDataList<Map<String, dynamic>>(
          key: _listKey,
          fetchPage: (page, perPage) => widget.client.adminModules.mailingListMembersPage(widget.listId, page: page, perPage: perPage),
          columns: [
            (item) => AdminDataColumn(label: Ar.email, cellBuilder: (_, __) => Text(item['email']?.toString() ?? '—')),
            (item) => AdminDataColumn(label: Ar.phoneNumber, cellBuilder: (_, __) => Text(item['phone']?.toString() ?? '—')),
            (item) => AdminDataColumn(
              label: Ar.actions,
              cellBuilder: (_, __) => IconButton(
                icon: const Icon(Icons.remove_circle_outline, size: 20),
                onPressed: () => _removeMember(item['id'] as int),
              ),
            ),
          ],
        ),
      ),
      actions: [
        TextButton(onPressed: _addMember, child: const Text(Ar.commAddMember)),
        TextButton(onPressed: () => Navigator.pop(context), child: const Text(Ar.close)),
      ],
    );
  }
}
