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
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, 
                            buttons: [{
                                name: 'detail',
                                text: '详情',
                                title: '查看详情',
                                classname: 'btn btn-xs btn-primary btn-dialog',
                                icon: '',
                                url: 'form_data/detail',
                                callback: function (data) {
                                    Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                },
                                visible: function (row) {
                                    //返回true时按钮显示,返回false隐藏
                                    return true;
                                }
                            },
                            {
                                name: 'result',
                                text: '报告',
                                classname: 'btn btn-xs btn-primary btn-addtabs',
                                icon: '',
                                url: '/pc/index.html#/result?id={id}&testid=123456',
                                extend:' target="_blank"',
                                callback: function (data) {
                                    Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                },
                                visible: function (row) {
                                    //返回true时按钮显示,返回false隐藏
                                    return true;
                                }
                            }]
                        }
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
                    $('#c-weight').html(data);
                    $('#c-weight').selectpicker("refresh");
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
                    $('#c-weight').html(data);
                    $('#c-weight').selectpicker("refresh");
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
            getWeightSelectOption(sexDefalt, $('input[name=defaultWeight]').val());
            getShapeSelectOption(sexDefalt, $('input[name=defaultShape]').val());

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