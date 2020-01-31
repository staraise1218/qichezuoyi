define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'form_data/index' + location.search,
                    add_url: 'form_data/add',
                    edit_url: 'form_data/edit',
                    del_url: 'form_data/del',
                    multi_url: 'form_data/multi',
                    table: 'form_data',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name')},
                        {field: 'age', title: __('Age')},
                        {field: 'sex', title: __('Sex'), searchList: {"1":__('Sex 1'),"0":__('Sex 0')}, formatter: Table.api.formatter.normal},
                        {field: 'height', title: __('Height')},
                        {field: 'weight', title: __('Weight')},
                        {field: 'shape', title: __('Shape')},
                        {field: 'car_brand_id', visible: false, title: __('Car_brand_id')},
                        {field: 'car_system_id', visible: false, title: __('Car_system_id')},
                        {field: 'car_type_id', visible: false, title: __('Car_type_id')},
                        {field: 'car_level_id', visible: false, title: __('Car_level_id')},
                        {field: 'chair_price', visible: false, title: __('Chair_price'), operate:'BETWEEN'},
                        {field: 'chair_color_id', visible: false, title: __('Chair_color_id')},
                        {field: 'chair_material_id', visible: false, title: __('Chair_material_id')},
                        {field: 'chair_backrest_size', visible: false, title: __('Chair_backrest_size')},
                        {field: 'chair_cushion_size', visible: false, title: __('Chair_cushion_size')},
                        {field: 'chair_hardness_backrest', visible: false, title: __('Chair_hardness_backrest')},
                        {field: 'chair_hardness_cushion', visible: false, title: __('Chair_hardness_cushion')},
                        {field: 'user_id', visible: false, title: __('User_id')},
                        {field: 'file', visible: false, title: __('File')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});