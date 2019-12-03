define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'upload'], function ($, undefined, Backend, Table, Form, Upload) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'people/index' + location.search,
                    add_url: 'people/add',
                    edit_url: 'people/edit',
                    del_url: 'people/del',
                    multi_url: 'people/multi',
                    import_url: 'people/import',
                    table: 'people',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search: false,
                showToggle: false,
                showColumns: false,
                // searchFormVisible: true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'name', title: __('Name'), operate: 'like'},
                        {field: 'sex', title: __('Sex'), searchList: {"0":__('Sex 0'),"1":__('Sex 1')}, formatter: Table.api.formatter.normal},
                        {field: 'height', title: __('Height'), operate: false},
                        {field: 'weight1', title: __('Weight1'), operate: false},
                        {field: 'shape', title: __('Shape'), operate: false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });


            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            function getHeightSelectOption(sex){
                Fast.api.ajax({
                    url: 'people/getHeightSelectOption',
                    dataType: 'html',
                    data: {sex: sex},
                }, function (data, ret) {
                    $('#c-height').html(data);
                    $('#c-height').selectpicker("refresh");
                     return false;
                });
            }

            function getWeightSelectOption(sex){

                Fast.api.ajax({
                    url: 'people/getWeightSelectOption',
                    dataType: 'html',
                    data: {sex: sex},
                }, function (data, ret) {
                    $('#c-weight1').html(data);
                    $('#c-weight1').selectpicker("refresh");
                    return false;
                });
            }

            function getShapeSelectOption(sex){

                Fast.api.ajax({
                    url: 'people/getShapeSelectOption',
                    dataType: 'html',
                    data: {sex: sex},
                }, function (data, ret) {
                    $('#c-shape').html(data);
                    $('#c-shape').selectpicker("refresh");
                     return false;
                });
            }
            
            // 选择男女切换身高、体重、体型
            $('#c-sex').change(function(){
                var sexVal = $(this).val();
                if(sexVal == '') return false;

                // 切换身高
                getHeightSelectOption(sexVal);
                // 切换体重
                getWeightSelectOption(sexVal);
                // 切换体型
                getShapeSelectOption(sexVal);
            })

            Controller.api.bindevent();
        },
        edit: function () {
            function getHeightSelectOption(sex, selectValue=null){

                Fast.api.ajax({
                    url: 'people/getHeightSelectOption',
                    dataType: 'html',
                    data: {sex: sex, selectValue: selectValue},
                }, function (data, ret) {
                    $('#c-height').html(data);
                    $('#c-height').selectpicker("refresh");
                     return false;
                });

            }

            function getWeightSelectOption(sex, selectValue=null){

                Fast.api.ajax({
                    url: 'people/getWeightSelectOption',
                    dataType: 'html',
                    data: {sex: sex, selectValue: selectValue},
                }, function (data, ret) {
                    $('#c-weight1').html(data);
                    $('#c-weight1').selectpicker("refresh");
                    return false;
                });
            }

            function getShapeSelectOption(sex, selectValue=null){
                Fast.api.ajax({
                    url: 'people/getShapeSelectOption',
                    dataType: 'html',
                    data: {sex: sex, selectValue: selectValue},
                }, function (data, ret) {
                    $('#c-shape').html(data);
                    $('#c-shape').selectpicker("refresh");
                     return false;
                });
            }
            
            // 选择男女切换身高、体重、体型
            $('#c-sex').change(function(){
                var sexVal = $(this).val();
                if(sexVal == '') return false;

                // 切换身高
                getHeightSelectOption(sexVal);
                // 切换体重
                getWeightSelectOption(sexVal);
                // 切换体型
                getShapeSelectOption(sexVal);
            })

            // 身高、体重、体型 select 初始化
            var sexDefalt = $('#c-sex').val();
            getHeightSelectOption(sexDefalt, $('input[name=defaultHeight]').val());
            getWeightSelectOption(sexDefalt, $('input[name=defaultWeight1]').val());
            getShapeSelectOption(sexDefalt, $('input[name=defaultShape]').val());

            // 导入按钮绑定事件
            require(['upload'], function (Upload) {
                Upload.api.plupload($('.importBtn'), function (data, ret) {
                    Fast.api.ajax({
                        url: 'people/importData',
                        data: {file: data.url},
                    }, function (data, ret) {
                        alert(111)
                    });
                });
            });

            Controller.api.bindevent();
        },
        import: function(){
            alert(1)
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
