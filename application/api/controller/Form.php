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
        if(cache(md5($filepath))) {
            $result['formData'] = $formData;

            $cacheData = cache(md5($filepath));
            $result['result'] = unserialize($cacheData);
            $this->success('', $result);
        }

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
        $topColumns = $currentSheet->getCell('B11')->getValue(); // B11 列

        // $topColumns == 64 说明文件里上部分是 靠背数据 ，下部分是坐垫数据
         // 靠背硬度值数组
        $backrestArr = $cushiontArr = [];
        $backrestRowArr = $cushiontRowArr = []; // 所有数据 按行二维数组
        if($topColumns == 64) {
            // 靠背信息
            $backrestInfo = $this->getRowData($currentSheet, 60, 21, 1, 64);
            // 靠背值
            $backrestData = $this->getCellValue($currentSheet, 'B17', 'B16', 'B19');
        
            // 坐垫信息
            $cushiontInfo = $this->getRowData($currentSheet, 113, 74, 1, 40);
            // 坐垫值
            $cushionData = $this->getCellValue($currentSheet, 'B70', 'B69', 'B72');
            
           
        } else {
            // 坐垫信息
            $cushiontInfo = $this->getRowData($currentSheet, 60, 21, 1, 40);
            // 坐垫值
            $cushionData = $this->getCellValue($currentSheet, 'B17', 'B16', 'B19');


            // 靠背信息
            $backrestInfo = $this->getRowData($currentSheet, 113, 74, 1, 64);
            // 靠背值
            $backrestData = $this->getCellValue($currentSheet, 'B70', 'B69', 'B72');
        }

        // 靠背信息
        $backrestRowArr = $backrestInfo['rowArr']; // 行二维数组
        $backrestPoints = $backrestInfo['points']; // 所有点坐标及压力值
        $backrestNonZeroArr = $backrestInfo['nonZeroArr']; // 非零值
        // 坐垫信息
        $cushionRowArr = $cushiontInfo['rowArr']; // 行二维数组
        $cushionPoints = $cushiontInfo['points']; // 所有点坐标及压力值
        $cushionNonZeroArr = $cushiontInfo['nonZeroArr']; // 非零值

        // 计算水平每行最大值
        // $horizontalMaxValueArr

        // 靠背加载力 加载力：非零值的总和*非零值个数*1.27*1.27
        $backrest_jiazaili = round(array_sum($backrestNonZeroArr)*count($backrestNonZeroArr)*1.27*1.27, 2);
        // 坐垫加载力
        $cushion_jiazaili = round(array_sum($cushionNonZeroArr)*count($cushionNonZeroArr)*1.27*1.27, 2);

        // 平均硬度， 该款座椅硬度方面XX（较软/偏硬，根据平均硬度评判）: 取坐垫硬度数值求平均数 与 4.1比较 
        $average_hardness = (array_sum($cushionNonZeroArr)/count($cushionNonZeroArr)) > 4.1 ? '较硬' : '较软';


        $result['formData'] = $formData;
        $result['result'] = array(
            'backrestData' => $backrestData,
            'cushionData' => $cushionData,
            'backrest_jiazaili' => $backrest_jiazaili, // 靠背加载力
            'cushion_jiazaili' => $cushion_jiazaili, // 坐垫加载力,
            'average_hardness' => $average_hardness, // 平均硬度
            'backrestPoints' => $backrestPoints, // 靠背所有坐标点及压力值
            'cushionPoints' => $cushionPoints, // 坐垫所在有坐标点及压力值
            'backrestRowArr' => $backrestRowArr, // 靠背行数组
            'cushionRowArr' => $cushionRowArr, // 坐垫行数组
        );

        cache(md5($filepath), serialize($result['result']));
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

    // 获取起始行之间的行，二维数组，并返回所有左边点
    private function getRowData($currentSheet, $start_row, $end_row, $start_column, $end_column)
    {
        // y 坐标值
        $y = 1;
        for ($currentRow = $start_row; $currentRow >= $end_row; $currentRow--) {
            $row = [];
            $x = 1;
            for ($currentColumn = $start_column; $currentColumn <= $end_column; $currentColumn++) {
                // 单元格值
                $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                // 非零值
                if($val != 0) $nonZeroArr[] = $val;

                // 组合行数据
                $row[] = $val;
                // 单元格坐标点和值
                $points[] = ['x' => $x, 'y' => $y, $val]; // 坐标点
                // x坐标值
                $x ++;
            }
            $rowArr[$y] = $row;
            $y ++;
        }

        return compact('rowArr', 'points', 'nonZeroArr');
    }

    // 获取最大压力， 平均压力， 接触面积
    private function getCellValue($currentSheet, $peak_pressure_cell, $average_pressure_cell, $contact_area_cell)
    {
        return array(
            // 最大压力
            'peak_pressure' => $currentSheet->getCell($peak_pressure_cell)->getValue(),
            // 平均压力
            'average_pressure' => $currentSheet->getCell($average_pressure_cell)->getValue(), 
            // 接触面积
            'contact_area' => $currentSheet->getCell($contact_area_cell)->getValue()
        );
        
    }
}
