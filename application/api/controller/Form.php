<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\api\library\Character;

/**
 * 首页接口
 */
class Form extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    // 获取表单初始化数据
    public function getInitData()
    {
        // 获取车的品牌、车系、车型
        $carBrandList = db('car_brand')->cache()->select();
        $carSystemList = db('car_system')->order('name asc')->cache()->select();
        $Character = new Character();

        foreach ($carBrandList as $k => &$brand) {
            foreach ($carSystemList as $i => $system) {
                if($system['car_brand_id'] == $brand['id']){
                    $brand['sub'][] = $system;
                    unset($carSystemList[$i]);
                }
            }
        }
        
        $carBrandList = $Character->groupByInitials($carBrandList);

        // 汽车级别
        $carLevel = db('car_level')->cache()->select();
        // 汽车价格
        $carPrice = db('car_price')->cache()->select();
        // 座椅颜色
        $chairColor = db('chair_color')->cache()->select();
        // 座椅材质
        $chairMaterial = db('chair_material')->cache()->select();

        // 靠背尺寸
        $backrestSize = db('chair_backrest')
            ->field('title, field, explain, image')
            ->order('weigh desc')
            ->cache()->select();

        // 坐垫尺寸
        $cushionSize = db('chair_cushion')
            ->field('title, field, explain, image')
            ->order('weigh desc')
            ->cache()->select();

        // 硬度数据
        $hardnessSize = db('chair_hardness')
            ->field('title, field, explain, image, type')
            ->order('weigh desc')
            ->cache()->select();

        $hardnessSize_backrest = [];
        $hardnessSize_cushion = [];
        if(!empty($hardnessSize)){
            foreach ($hardnessSize as $item) {
                if($item['type'] == 1) {
                    array_push($hardnessSize_backrest, $item);
                } else {
                    array_push($hardnessSize_cushion, $item);
                }
            }
        }

        $data['carBrandList'] = $carBrandList;
        $data['carLevel'] = $carLevel;
        $data['carPrice'] = $carPrice;
        $data['chairColor'] = $chairColor;
        $data['chairMaterial'] = $chairMaterial;
        $data['backrestSize'] = $backrestSize;
        $data['cushionSize'] = $cushionSize;
        $data['hardnessSize_backrest'] = $hardnessSize_backrest;
        $data['hardnessSize_cushion'] = $hardnessSize_cushion;


        $this->success('请求成功', $data);
    }

    // 获得车型
    public function getCarType(){
        $car_system_id = input('get.car_system_id/d');

        $list = db('car_type')
            ->where('car_system_id', $car_system_id)
            ->order('name asc')
            ->select();

        $this->success('请求成功', $list);
    }
}
