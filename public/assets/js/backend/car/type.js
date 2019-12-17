define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'car/type/index' + location.search,
                    add_url: 'car/type/add',
                    edit_url: 'car/type/edit',
                    del_url: 'car/type/del',
                    multi_url: 'car/type/multi',
                    table: 'car_type',
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
                        {field: 'car_brand_id', title: __('Car_brand_id')},
                        {field: 'car_system_id', title: __('Car_system_id')},
                        {field: 'name', title: __('Name')},
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
                $(document).on('change', '#c-car_brand_id', function(){
                    $('#c-car_system_id').selectPageClear();
                })
                $("#c-car_system_id").data("params", function (obj) {
                    return {custom: {car_brand_id: $('#c-car_brand_id').val()}};
                });

                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});