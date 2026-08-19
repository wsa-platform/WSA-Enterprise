import 'package:flutter/material.dart';

import 'package:wsa_admin/data/api/api_client.dart';

import 'package:wsa_admin/l10n/m22_strings.dart';

import 'package:wsa_admin/presentation/widgets/admin_data_list.dart';

import 'package:wsa_admin/presentation/widgets/crud_helpers.dart';

import 'package:wsa_admin/presentation/widgets/image_uploader.dart';

import 'package:wsa_admin/presentation/widgets/module_screen_layout.dart';

import 'package:wsa_admin/presentation/widgets/paginated_data_list.dart';



class ProductsScreen extends StatefulWidget {

  const ProductsScreen({super.key, required this.client});

  final ApiClient client;



  @override

  State<ProductsScreen> createState() => _ProductsScreenState();

}



class _ProductsScreenState extends State<ProductsScreen> {
  final _listKey = GlobalKey<PaginatedDataListState<Map<String, dynamic>>>();

  Map<String, dynamic>? _selectedProduct;

  List<Map<String, dynamic>> _images = [];

  List<Map<String, dynamic>> _movements = [];

  bool _detailsLoading = false;



  Future<void> _add() async {

    final data = await SimpleFormDialog.show(context,

      title: Ar.addProduct,

      fields: const [

        FormFieldDef(key: 'sku', label: Ar.colSku),

        FormFieldDef(key: 'name', label: Ar.colName),

        FormFieldDef(key: 'sale_price', label: Ar.colPrice, numeric: true),

        FormFieldDef(key: 'description', label: Ar.colDescription, required: false),

      ],

    );

    if (data == null) return;

    try {

      await widget.client.adminModules.createProduct({

        ...data,

        'sale_price': double.tryParse(data['sale_price'] ?? '0') ?? 0,

        'cost_price': 0,

        'is_active': true,

      });

      if (mounted) {

        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));

        _listKey.currentState?.reload();

      }

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  Future<void> _delete(Map<String, dynamic> item) async {

    if (!await confirmDelete(context, itemName: item['name']?.toString())) return;

    try {

      await widget.client.adminModules.deleteProduct(item['id'] as int);

      if (mounted) {

        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.deleted)));

        if (_selectedProduct?['id'] == item['id']) setState(() => _selectedProduct = null);

        _listKey.currentState?.reload();

      }

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  Future<void> _openDetails(Map<String, dynamic> product) async {

    setState(() { _selectedProduct = product; _detailsLoading = true; });

    try {

      final images = await widget.client.adminModules.productImages(product['id'] as int);

      final movementsPage = await widget.client.adminModules.inventoryMovementsPage(productId: product['id'] as int);

      if (!mounted) return;

      setState(() {

        _images = images.map((r) => Map<String, dynamic>.from(r as Map)).toList();

        _movements = movementsPage.data;

        _detailsLoading = false;

      });

    } catch (e) {

      if (mounted) {

        setState(() => _detailsLoading = false);

        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

      }

    }

  }



  Future<void> _adjustInventory() async {

    if (_selectedProduct == null) return;

    final data = await SimpleFormDialog.show(context,

      title: Ar.adjustInventory,

      fields: const [

        FormFieldDef(key: 'warehouse_id', label: Ar.colWarehouse, numeric: true),

        FormFieldDef(key: 'quantity', label: Ar.colQuantity, numeric: true),

        FormFieldDef(key: 'reason', label: Ar.colReason, required: false),

      ],

    );

    if (data == null) return;

    try {

      await widget.client.adminModules.adjustInventory({

        'warehouse_id': int.tryParse(data['warehouse_id'] ?? '1') ?? 1,

        'product_id': _selectedProduct!['id'],

        'quantity': double.tryParse(data['quantity'] ?? '0') ?? 0,

        'reason': data['reason'],

      });

      if (mounted) {

        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));

        _openDetails(_selectedProduct!);

      }

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  @override

  Widget build(BuildContext context) {

    final canManage = widget.client.hasPermission('business.manage');



    return ModuleScreenLayout(

      title: Ar.productsOverview,

      loading: false,

      error: null,

      empty: false,

      onRetry: () => _listKey.currentState?.reload(),

      body: Column(

        crossAxisAlignment: CrossAxisAlignment.start,

        children: [

          CrudActionBar(canManage: canManage, onAdd: _add, addLabel: Ar.addProduct),

          PaginatedDataList<Map<String, dynamic>>(

            key: _listKey,

            fetchPage: (page, perPage) => widget.client.adminModules.productsPage(page: page, perPage: perPage),

            columns: [

              (item) => AdminDataColumn(label: Ar.colSku, cellBuilder: (_, __) => Text(item['sku']?.toString() ?? '')),

              (item) => AdminDataColumn(label: Ar.colName, cellBuilder: (_, __) => Text(item['name']?.toString() ?? '')),

              (item) => AdminDataColumn(label: Ar.colPrice, cellBuilder: (_, __) => Text(item['sale_price']?.toString() ?? '')),

              (item) => AdminDataColumn(label: Ar.colStatus, cellBuilder: (_, __) => Text(item['is_active'] == true ? Ar.statusActive : Ar.statusInactive)),

              (item) => AdminDataColumn(label: Ar.edit, cellBuilder: (_, __) => Row(

                mainAxisSize: MainAxisSize.min,

                children: [

                  IconButton(icon: const Icon(Icons.inventory_2_outlined), onPressed: () => _openDetails(item)),

                  if (canManage) IconButton(icon: const Icon(Icons.delete_outline, color: Colors.red), onPressed: () => _delete(item)),

                ],

              )),

            ],

          ),

          if (_selectedProduct != null) ...[

            const SizedBox(height: 24),

            Text('${Ar.editProduct}: ${_selectedProduct!['name']}', style: Theme.of(context).textTheme.titleMedium),

            if (_detailsLoading) const LinearProgressIndicator(),

            if (!_detailsLoading) ...[

              const SizedBox(height: 12),

              Text(Ar.uploadImage, style: Theme.of(context).textTheme.titleSmall),

              const SizedBox(height: 8),

              MultiImageUploader(

                client: widget.client,

                productId: _selectedProduct!['id'] as int,

                initialImages: _images,

                onChanged: () => _openDetails(_selectedProduct!),

              ),

              const SizedBox(height: 16),

              Row(

                children: [

                  Text(Ar.inventoryHistory, style: Theme.of(context).textTheme.titleSmall),

                  const Spacer(),

                  if (canManage)

                    FilledButton.icon(onPressed: _adjustInventory, icon: const Icon(Icons.add), label: const Text(Ar.adjustInventory)),

                ],

              ),

              const SizedBox(height: 8),

              AdminDataList(

                rowCount: _movements.length,

                emptyMessage: Ar.emptyData,

                columns: [

                  AdminDataColumn(label: Ar.colQuantity, cellBuilder: (_, i) => Text(_movements[i]['quantity']?.toString() ?? '')),

                  AdminDataColumn(label: Ar.colReason, cellBuilder: (_, i) => Text(_movements[i]['notes']?.toString() ?? Ar.notAvailable)),

                  AdminDataColumn(label: Ar.colUser, cellBuilder: (_, i) {

                    final user = _movements[i]['user'] as Map<String, dynamic>?;

                    return Text(user?['name']?.toString() ?? Ar.notAvailable);

                  }),

                  AdminDataColumn(label: Ar.colDate, cellBuilder: (_, i) => Text(_movements[i]['created_at']?.toString() ?? '')),

                ],

              ),

            ],

          ],

        ],

      ),

    );

  }

}

