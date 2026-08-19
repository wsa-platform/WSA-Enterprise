import 'package:flutter/material.dart';

import 'package:wsa_admin/data/api/api_client.dart';

import 'package:wsa_admin/l10n/m22_strings.dart';

import 'package:wsa_admin/presentation/widgets/admin_data_list.dart';

import 'package:wsa_admin/presentation/widgets/crud_helpers.dart';

import 'package:wsa_admin/presentation/widgets/module_screen_layout.dart';

import 'package:wsa_admin/presentation/widgets/paginated_data_list.dart';

import 'package:wsa_admin/presentation/widgets/rich_text_editor.dart';



class ContentScreen extends StatefulWidget {

  const ContentScreen({super.key, required this.client});

  final ApiClient client;



  @override

  State<ContentScreen> createState() => _ContentScreenState();

}



class _ContentScreenState extends State<ContentScreen> {

  bool _loading = true;

  String? _error;

  List<Map<String, dynamic>> _courses = [];

  final _listKey = GlobalKey<PaginatedDataListState<Map<String, dynamic>>>();



  @override

  void initState() {

    super.initState();

    _loadCourses();

  }



  Future<void> _loadCourses() async {

    setState(() { _loading = true; _error = null; });

    try {

      final courses = await widget.client.adminModules.trainingCourses();

      if (!mounted) return;

      setState(() {

        _courses = courses.map((r) => Map<String, dynamic>.from(r as Map)).toList();

        _loading = false;

      });

      _listKey.currentState?.reload();

    } catch (e) {

      if (!mounted) return;

      setState(() { _error = e.toString(); _loading = false; });

    }

  }



  Future<void> _addArticle() async {

    final result = await _showArticleDialog();

    if (result == null) return;

    try {

      await widget.client.adminModules.createLibraryItem(result);

      if (mounted) {

        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));

        _listKey.currentState?.reload();

      }

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  Future<void> _editArticle(Map<String, dynamic> item) async {

    final result = await _showArticleDialog(initial: item);

    if (result == null) return;

    try {

      await widget.client.adminModules.updateLibraryItem(item['id'] as int, result);

      if (mounted) {

        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));

        _listKey.currentState?.reload();

      }

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  Future<Map<String, dynamic>?> _showArticleDialog({Map<String, dynamic>? initial}) async {

    final titleController = TextEditingController(text: initial?['title']?.toString() ?? '');

    final titleArController = TextEditingController(text: initial?['title_ar']?.toString() ?? '');

    final slugController = TextEditingController(text: initial?['slug']?.toString() ?? '');

    final editorKey = GlobalKey<RichTextEditorState>();

    var content = initial?['content_ar']?.toString() ?? initial?['content']?.toString();



    return showDialog<Map<String, dynamic>>(

      context: context,

      builder: (ctx) => AlertDialog(

        title: Text(initial == null ? Ar.addArticle : Ar.editArticle),

        content: SizedBox(

          width: 560,

          child: SingleChildScrollView(

            child: Column(

              mainAxisSize: MainAxisSize.min,

              children: [

                TextField(controller: titleController, decoration: const InputDecoration(labelText: Ar.colTitle)),

                const SizedBox(height: 8),

                TextField(controller: titleArController, decoration: const InputDecoration(labelText: 'العنوان (عربي)')),

                const SizedBox(height: 8),

                TextField(controller: slugController, decoration: const InputDecoration(labelText: Ar.colSlug)),

                const SizedBox(height: 12),

                RichTextEditor(key: editorKey, initialContent: content),

              ],

            ),

          ),

        ),

        actions: [

          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text(Ar.cancel)),

          FilledButton(

            onPressed: () {

              final slug = slugController.text.trim().isEmpty

                  ? titleArController.text.trim().replaceAll(' ', '-')

                  : slugController.text.trim();

              Navigator.pop(ctx, {

                'title': titleController.text.trim(),

                'title_ar': titleArController.text.trim(),

                'slug': slug,

                'content_ar': editorKey.currentState?.contentJson ?? content ?? '',

                'publication_status': initial?['publication_status'] ?? 'draft',

                'content_type': 'article',

              });

            },

            child: const Text(Ar.save),

          ),

        ],

      ),

    );

  }



  @override

  Widget build(BuildContext context) {

    final canManage = widget.client.hasAnyPermission(['library.manage', 'training.manage']);



    return ModuleScreenLayout(

      title: Ar.contentOverview,

      loading: _loading,

      error: _error,

      empty: false,

      onRetry: _loadCourses,

      body: Column(

        crossAxisAlignment: CrossAxisAlignment.start,

        children: [

          CrudActionBar(canManage: canManage, onAdd: _addArticle, addLabel: Ar.addArticle),

          PaginatedDataList<Map<String, dynamic>>(

            key: _listKey,

            fetchPage: (page, perPage) => widget.client.adminModules.libraryItemsPage(page: page, perPage: perPage),

            columns: [

              (item) => AdminDataColumn(label: Ar.colTitle, cellBuilder: (_, __) => Text(

                item['title_ar']?.toString() ?? item['title']?.toString() ?? '',

              )),

              (item) => AdminDataColumn(label: Ar.colPublication, cellBuilder: (_, __) => Text(item['publication_status']?.toString() ?? '')),

              (item) => AdminDataColumn(label: Ar.colDate, cellBuilder: (_, __) => Text(item['created_at']?.toString() ?? '')),

              if (canManage)

                (item) => AdminDataColumn(label: Ar.edit, cellBuilder: (_, __) => IconButton(

                  icon: const Icon(Icons.edit),

                  onPressed: () => _editArticle(item),

                )),

            ],

          ),

          const SizedBox(height: 24),

          Text(Ar.metricCourses, style: Theme.of(context).textTheme.titleMedium),

          const SizedBox(height: 12),

          AdminDataList(

            rowCount: _courses.length,

            emptyMessage: Ar.emptyData,

            columns: [

              AdminDataColumn(label: Ar.colTitle, cellBuilder: (_, i) => Text(

                _courses[i]['title_ar']?.toString() ?? _courses[i]['title']?.toString() ?? '',

              )),

              AdminDataColumn(label: Ar.colStatus, cellBuilder: (_, i) => Text(_courses[i]['status']?.toString() ?? '')),

            ],

          ),

        ],

      ),

    );

  }

}

