<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\api\library\Character;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

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

    // 获取已提交的数据
    public function getFormList(){
        $user_id = input('user_id', 0);

        $list = db('form_data')->where('user_id', $user_id)
            ->field('id, name, age, sex, height, weight, shape, chair_hardness_cushion')
            ->select();

        $this->success('请求成功', $list);
    }


    // 提交表单
    public function FormSubmit(){
        $postData = input('post.');

        if( db('form_data')->insert($postData)){
            $this->success('提交成功');
        } else {
            $this->error('提交失败');
        }
        
    }

    //  查看表单结果
    public function getResult(){
        $id = input('id');

        $formData = db('form_data')->where('id', $id)->find();
        $formData['carType'] = db('car_type')->where('id', $formData['car_type_id'])->value('name'); // 车型
        $formData['chairColor'] = db('chair_color')->where('id', $formData['chair_color_id'])->value('name'); // 座椅颜色
        $formData['chairMaterial'] = db('chair_material')->where('id', $formData['chair_material_id'])->value('name'); // 座椅材质

        if($formData['file'] == '') $this->error('缺少文件');


        $filepath = '.'.$formData['file'];

        $ext = pathinfo($filepath, PATHINFO_EXTENSION);
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            $this->error('不支持的格式');
        }

        if ($ext === 'csv') {
            $reader = new Csv();
        } elseif ($ext === 'xls') {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

        if (!$PHPExcel = $reader->load($filepath)) {
            $this->error(__('Unknown data format'));
        }

        $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
        $columns = $currentSheet->getCell('B11')->getValue(); // B11 列

        // $columns == 64 说明文件里上部分是 靠背数据 ，下部分是坐垫数据

        // 靠背值
        $backrestData = array(
            'peak_pressure' => $currentSheet->getCell('B17')->getValue(), // 最大压力
            'average_pressure' => $currentSheet->getCell('B16')->getValue(), // 平均压力
            'contact_area' => $currentSheet->getCell('B19')->getValue(), // 接触面积
        );
        // 坐垫值
        $cushionData = array(
            'peak_pressure' => $currentSheet->getCell('B70')->getValue(), // 最大压力
            'average_pressure' => $currentSheet->getCell('B69')->getValue(), // 平均压力
            'contact_area' => $currentSheet->getCell('B72')->getValue(), // 接触面积
        );

        $backrestArr = $cushiontArr = [];
        // 靠背硬度值数组
        for ($currentRow = 21; $currentRow <= 60; $currentRow++) {
            for ($currentColumn = 1; $currentColumn <= 64; $currentColumn++) {
                $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                if($val != 0) $backrestArr[] = $val;
            }
        }
        // 靠背加载力 加载力：非零值的总和*非零值个数*1.27*1.27
        $backrest_jiazaili = round(array_sum($backrestArr)*count($backrestArr)*1.27*1.27, 2);

        // 坐垫硬度值数组
        for ($currentRow = 74; $currentRow <= 113; $currentRow++) {
            for ($currentColumn = 1; $currentColumn <= 40; $currentColumn++) {
                $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                if($val != 0) $cushiontArr[] = $val;
            }
        }
        // 坐垫加载力
        $cushion_jiazaili = round(array_sum($cushiontArr)*count($cushiontArr)*1.27*1.27, 2);

        // 平均硬度， 该款座椅硬度方面XX（较软/偏硬，根据平均硬度评判）: 取坐垫硬度数值求平均数 与 4.1比较 
        $average_hardness = $columns == 64 ? array_sum($cushiontArr)/count($cushiontArr) : array_sum($backrestArr)/count($backrestArr);
        $average_hardness = $average_hardness > 4.1 ? '较硬' : '较软';
        

        $result['formData'] = $formData;
        $result['result'] = array(
            'backrestData' => $columns == 64 ? $backrestData : $cushionData,
            'cushionData' => $columns == 64 ? $cushionData : $backrestData,
            'backrest_jiazaili' => $columns == 64 ? $backrest_jiazaili : $cushion_jiazaili, // 靠背加载力
            'cushion_jiazaili' => $columns == 64 ? $cushion_jiazaili : $backrest_jiazaili, // 坐垫加载力,
            'average_hardness' => $average_hardness,
        );
        $this->success('', $result);
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
