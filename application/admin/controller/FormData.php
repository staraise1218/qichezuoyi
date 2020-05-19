<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 测试数据
 *
 * @icon fa fa-circle-o
 */
class FormData extends Backend
{
    
    /**
     * FormData模型对象
     * @var \app\admin\model\FormData
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\FormData;
        $this->view->assign("sexList", $this->model->getSexList());
        $this->view->assign("ageList", $this->model->getAgeList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    
    public function detail()
    {
        $id = $this->request->param('ids');
        $row = $this->model->get($id);

        $formData['carType'] = db('car_type')->where('id', $row->car_type_id)->value('name'); // 车型
        $formData['level'] = db('car_level')->where('id', $row->car_level_id)->value('name'); 
        $formData['chairColor'] = db('chair_color')->where('id', $row->chair_color_id)->value('name'); // 座椅颜色
        $formData['chairMaterial'] = db('chair_material')->where('id', $row->chair_material_id)->value('name'); // 座椅材质

        $chair_backrest_size_arr = json_decode(htmlspecialchars_decode($row->chair_backrest_size), true);
        $chair_backrest_enums = db('chair_backrest')->select();
        $chair_backrest_enums = array_column($chair_backrest_enums, 'title', 'field');

        $chair_cushion_size_arr = json_decode(htmlspecialchars_decode($row->chair_cushion_size), true);
        $chair_cushion_enums = db('chair_cushion')->select();
        $chair_cushion_enums = array_column($chair_cushion_enums, 'title', 'field');

        $chair_hardness_backrest_arr = json_decode(htmlspecialchars_decode($row->chair_hardness_backrest), true);
        $chair_hardness_backrest_enums = db('chair_hardness')->where('type', '1')->select();
        $chair_hardness_backrest_enums = array_column($chair_hardness_backrest_enums, 'title', 'field');


        $chair_hardness_cushion_arr = json_decode(htmlspecialchars_decode($row->chair_hardness_cushion), true);
        $chair_hardness_cushion_enums = db('chair_hardness')->where('type', '2')->select();
        $chair_hardness_cushion_enums = array_column($chair_hardness_cushion_enums, 'title', 'field');

        $this->assign('row', $row);
        $this->assign('formData', $formData);
        $this->assign('chair_backrest_size_arr', $chair_backrest_size_arr);
        $this->assign('chair_backrest_enums', $chair_backrest_enums);
        $this->assign('chair_cushion_size_arr', $chair_cushion_size_arr);
        $this->assign('chair_cushion_enums', $chair_cushion_enums);
        $this->assign('chair_hardness_backrest_arr', $chair_hardness_backrest_arr);
        $this->assign('chair_hardness_backrest_enums', $chair_hardness_backrest_enums);
        $this->assign('chair_hardness_cushion_arr', $chair_hardness_cushion_arr);
        $this->assign('chair_hardness_cushion_enums', $chair_hardness_cushion_enums);


        return $this->view->fetch();
    }
}
