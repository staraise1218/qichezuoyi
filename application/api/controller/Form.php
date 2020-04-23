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

        // $dimension = count($backrestSize) + count($cushionSize) + count($hardnessSize_backrest) + count($hardnessSize_cushion);
        // $data['dimension'] = $dimension;

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
        // $chair_backrest_size = json_decode($postData['chair_backrest_size'], true);
        // $chair_cushion_size = json_decode($postData['chair_cushion_size'], true);
        // $chair_hardness_backrest = json_decode($postData['chair_hardness_backrest'], true);
        // $chair_hardness_cushion = json_decode($postData['chair_hardness_cushion'], true);

        // $postData['dimension'] = count($chair_backrest_size)+count($chair_cushion_size)+count($chair_hardness_backrest)+count($chair_hardness_cushion);


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
        // if(cache(md5($filepath))) {
        //     $result['formData'] = $formData;

        //     $cacheData = cache(md5($filepath));
        //     $result['result'] = unserialize($cacheData);
        //     $this->success('', $result);
        // }
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

        // 计算靠背压力峰值点拟合图 （竖直每行最大值）
        $backrestVerticalArr = []; // 先计算出竖直的数组
        foreach ($backrestRowArr as $backrestRow) {
            foreach ($backrestRow as $k2 => $value) {
                $backrestVerticalArr[$k2][] = $value;
            }
        }
        $backrestVerticalMaxValueArr = [];
        foreach ($backrestVerticalArr as $row) {
            $backrestVerticalMaxValueArr[] = max($row);
        }

        // 计算坐垫压力峰值点拟合图 ：坐垫水平每行最大值
        $cushionHorizontalMaxValueArr = [];
        foreach ($cushionRowArr as $cushionRow) {
            $cushionHorizontalMaxValueArr[] = max($cushionRow);
        }

        // 计算对称性：只计算坐垫
        $cushionHorizontalMaxValueArr_max = max($cushionHorizontalMaxValueArr);
        $cushionHorizontalMaxValueArr_temp = $cushionHorizontalMaxValueArr;



        // 靠背加载力 加载力：非零值的总和*非零值个数*1.27*1.27
        $backrest_jiazaili = round(array_sum($backrestNonZeroArr)*count($backrestNonZeroArr)*1.27*1.27, 2);
        // 坐垫加载力
        $cushion_jiazaili = round(array_sum($cushionNonZeroArr)*count($cushionNonZeroArr)*1.27*1.27, 2);

        // 平均硬度， 该款座椅硬度方面XX（较软/偏硬，根据平均硬度评判）: 取坐垫硬度数值求平均数 与 4.1比较 
        $average_hardness = (array_sum($cushionNonZeroArr)/count($cushionNonZeroArr)) > 4.1 ? '较硬' : '较软';

        // 靠背沿躯干线拟合图线
        $chairHardnessBackrestArr = json_decode(htmlspecialchars_decode($formData['chair_hardness_backrest']), true);

        $backrestHardnessNihe = [
            ['x' => 1, 'y' => $chairHardnessBackrestArr['backrestHardness50']],
            ['x' => 2, 'y' => $chairHardnessBackrestArr['backrestHardness100']],
            ['x' => 3, 'y' => $chairHardnessBackrestArr['backrestHardness150']],
            ['x' => 4, 'y' => $chairHardnessBackrestArr['backrestHardness200']],
            ['x' => 5, 'y' => $chairHardnessBackrestArr['backrestHardness250']],
            ['x' => 6, 'y' => $chairHardnessBackrestArr['backrestHardness300']],
            ['x' => 7, 'y' => $chairHardnessBackrestArr['backrestHardness350']],
            ['x' => 8, 'y' => $chairHardnessBackrestArr['backrestHardness400']],
            ['x' => 9, 'y' => $chairHardnessBackrestArr['backrestHardness450']],
            ['x' => 10, 'y' => $chairHardnessBackrestArr['backrestHardness500']],
            ['x' => 11, 'y' => $chairHardnessBackrestArr['backrestHardness550']],
        ];

        // 沿坐垫面硬度拟合图线（两条）
        $chairHardnessCushionArr = json_decode(htmlspecialchars_decode($formData['chair_hardness_cushion']), true);

        // 左硬度
        $cushionHardnessLeftNihe = [
            ['x' => 1, 'y' => $chairHardnessCushionArr['cushionLeftHardness50']],
            ['x' => 2, 'y' => $chairHardnessCushionArr['cushionLeftHardness100']],
            ['x' => 3, 'y' => $chairHardnessCushionArr['cushionLeftHardness150']],
            ['x' => 4, 'y' => $chairHardnessCushionArr['cushionLeftHardness200']],
            ['x' => 5, 'y' => $chairHardnessCushionArr['cushionLeftHardness250']],
            ['x' => 6, 'y' => $chairHardnessCushionArr['cushionLeftHardness300']],
            ['x' => 7, 'y' => $chairHardnessCushionArr['cushionLeftHardness350']]
        ];
        // 右硬度
        $cushionHardnessRighttNihe = [
            ['x' => 1, 'y' => $chairHardnessCushionArr['cushionRightHardness50']],
            ['x' => 2, 'y' => $chairHardnessCushionArr['cushionRightHardness100']],
            ['x' => 3, 'y' => $chairHardnessCushionArr['cushionRightHardness150']],
            ['x' => 4, 'y' => $chairHardnessCushionArr['cushionRightHardness200']],
            ['x' => 5, 'y' => $chairHardnessCushionArr['cushionRightHardness250']],
            ['x' => 6, 'y' => $chairHardnessCushionArr['cushionRightHardness300']],
            ['x' => 7, 'y' => $chairHardnessCushionArr['cushionRightHardness350']]
        ];
        // 坐垫左硬度
        $cushionLeftHardness_arr = array_filter([
            $chairHardnessCushionArr['cushionLeftHardness50'],
            $chairHardnessCushionArr['cushionLeftHardness100'],
            $chairHardnessCushionArr['cushionLeftHardness150'],
            $chairHardnessCushionArr['cushionLeftHardness200'],
            $chairHardnessCushionArr['cushionLeftHardness250'],
            $chairHardnessCushionArr['cushionLeftHardness300'],
            $chairHardnessCushionArr['cushionLeftHardness350']
        ]);

        // 坐垫右硬度
        $cushionRightHardness_arr = array_filter([
            $chairHardnessCushionArr['cushionRightHardness50'],
            $chairHardnessCushionArr['cushionRightHardness100'],
            $chairHardnessCushionArr['cushionRightHardness150'],
            $chairHardnessCushionArr['cushionRightHardness200'],
            $chairHardnessCushionArr['cushionRightHardness250'],
            $chairHardnessCushionArr['cushionRightHardness300'],
            $chairHardnessCushionArr['cushionRightHardness350']
        ]);
        $junhengxing = '';
        if(count($cushionLeftHardness_arr)>0 && count($cushionRightHardness_arr)>0){
            $cushion_left_right = abs(array_sum($cushionLeftHardness_arr)/count($cushionLeftHardness_arr)
            -
            array_sum($cushionRightHardness_arr)/count($cushionRightHardness_arr));
            if($cushion_left_right > 0.1) {
                $junhengxing = '一般';
            } else {
                $junhengxing = '较好';
            }

        }

        // 减分项
        $score_arr = $this->_score($formData, $backrestData, $cushionData, $chairHardnessBackrestArr, $chairHardnessCushionArr);
        
        // echo '<pre>';

        // print_r($score_arr);
        // die();

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
            'backrestVerticalMaxValueArr' => $backrestVerticalMaxValueArr, // 靠背竖着每行最大值
            'cushionHorizontalMaxValueArr' => $cushionHorizontalMaxValueArr, // 坐垫水平每行最大值
            // 靠背硬度躯干线拟合图
            'backrestHardnessNihe' => $backrestHardnessNihe,
            // 坐垫左硬度拟合
            'cushionHardnessLeftNihe' => $cushionHardnessLeftNihe,
            // 坐垫右硬度拟合
            'cushionHardnessRighttNihe' => $cushionHardnessRighttNihe,
            'junhengxing' => $junhengxing, // 均衡性
            'score_arr' => $score_arr,
        );

        cache(md5($filepath), serialize($result['result']));
        $this->success('', $result);
    }

    private function _score($formData, $backrestData, $cushionData, $chairHardnessBackrestArr, $chairHardnessCushionArr)
    {
        $score = 100;
        $chair_backrest_size_arr = json_decode(htmlspecialchars_decode($formData['chair_backrest_size']), true);
        $chair_cushion_size_arr = json_decode(htmlspecialchars_decode($formData['chair_cushion_size']), true);
        // echo '<pre>';
        // print_r($chair_cushion_size_arr);
        // die();
        $sex = $formData['sex'];
        $height = (int) $formData['height'];
        $weight = (int) $formData['weight'];
        $shape = $formData['shape'];
        // 1.女性&身高<166cm&体重>65kg&上半身胖&+200靠背景中宽度<250
        if($sex == 0 && $height<166 && $weight>65 && $shape=='上身胖' && $chair_backrest_size_arr['back1'] < 250)
        {
            $score -= 3;
            $sub_items['three'][] = '靠背尺寸';
        }
        // 2.女性&身高<166cm&体重>65kg&上半身胖&+400靠背景中宽度<250
        if($sex == 0 && $height<166 && $weight>65 && $shape=='上身胖' && $chair_backrest_size_arr['back2'] < 250)
        {
            $score -= 3;
            $sub_items['three'][] = '靠背尺寸';
        }
        // 3.男性&身高<175cm&体重>80kg&上半身胖&+200靠背景中宽度<270
        if($sex == 1 && $height<175 && $weight>80 && $shape=='上身胖' && $chair_backrest_size_arr['back1'] < 270)
        {
            $score -= 3;
            $sub_items['three'][] = '靠背尺寸';
        }
        // 4.男性&身高<175cm&体重>80kg&上半身胖&+400靠背景中宽度<270
        if($sex == 1 && $height<166 && $weight>80 && $shape=='上身胖' && $chair_backrest_size_arr['back2'] < 270)
        {
            $score -= 3;
            $sub_items['three'][] = '靠背尺寸';
        }
        // 5.女性&身高<166cm&体重>65kg&下半身胖&+200靠背景中宽度<250
        if($sex == 0 && $height<166 && $weight>65 && $shape=='下身胖' && $chair_backrest_size_arr['back1'] < 280)
        {
            $score -= 3;
            $sub_items['three'][] = '坐垫尺寸';
        }
        // 6.女性&身高<166cm&体重>65kg&下半身胖&+400靠背景中宽度<280
        if($sex == 0 && $height<166 && $weight>65 && $shape=='下身胖' && $chair_backrest_size_arr['back2'] < 280)
        {
            $score -= 3;
            $sub_items['three'][] = '坐垫尺寸';
        }
        // 7.男性&身高<175cm&体重>80kg&下半身胖&+200靠背景中宽度<300
        if($sex == 1 && $height<175 && $weight>80 && $shape=='下身胖' && $chair_backrest_size_arr['back1'] < 300)
        {
            $score -= 3;
            $sub_items['three'][] = '坐垫尺寸';
        }
        // 8.男性&身高<175cm&体重>80kg&下半身胖&+400靠背景中宽度<300
        if($sex == 1 && $height<166 && $weight>80 && $shape=='下身胖' && $chair_backrest_size_arr['back2'] < 300)
        {
            $score -= 3;
            $sub_items['three'][] = '坐垫尺寸';
        }
        // 9.+400靠背侧翼角度>45
        if(atan(2*($chair_backrest_size_arr['back4']/($chair_backrest_size_arr['back3']-$chair_backrest_size_arr['back2']))) > 45) 
        {
            $score -= 3;
            $sub_items['three'][] = '靠背侧翼';    
        }
        // 10.+200靠背侧翼角度>45
        if(atan(2*($chair_cushion_size_arr['cushion3']/($chair_cushion_size_arr['cushion2']-$chair_cushion_size_arr['cushion1']))) > 45) 
        {
            $score -= 3;  
            $sub_items['three'][] = '坐垫侧翼';   
        }
        // 11.女性&身高<166cm&体重>65kg&下半身胖&接触面积<800
        if($sex == 0 && $height<166 && $weight>65 && $shape=='下身胖' && $cushionData['contact_area'] < 800)
        {
            $score -= 3;
            $sub_items['three'][] = '接触面积'; 
        }
        // 12.男性&身高<175cm&体重>80kg&下半身胖&接触面积<900
        if($sex == 0 && $height<175 && $weight>80 && $shape=='下身胖' && ($backrestData['contact_area']+$cushionData['contact_area'])/2 < 900)
        {
            $score -= 3;
            $sub_items['three'][] = '接触面积'; 
        }

        // 1 平均圧力>0.5
        if(($backrestData['average_pressure']+$backrestData['average_pressure'])/2 >0.5)
        {
            $score -= 4;
            $sub_items['four'][] = '体压分布'; 
        }

        // 2 最大圧力>1.5
        if(($backrestData['peak_pressure']+$backrestData['peak_pressure'])/2 >1.5)
        {
            $score -= 4;
            $sub_items['four'][] = '体压分布'; 
        }

        // 1.靠背最大宽度<450
        if($chair_backrest_size_arr['back6']<450)
        {
            $score -= 5;
            $sub_items['five'][] = '靠背尺寸'; 
        }

        // 2.坐垫最大宽度<450
        if($chair_cushion_size_arr['cushion6']<450)
        {
            $score -= 5;
            $sub_items['five'][] = '坐垫尺寸'; 
        }

        // 3.硬度最小值<3.0
        if( min( array_merge($chairHardnessBackrestArr, $chairHardnessCushionArr)
        ) < 3)
        {
            $score -= 5;
            $sub_items['five'][] = '硬度'; 
        }

        return compact('score', 'sub_items');
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

    // 获取起始行之间的行，二维数组，并返回所有坐标点
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
                $points[] = ['x' => $x, 'y' => $y, 'value' => $val]; // 坐标点
                // x坐标值
                $x ++;
            }
            $rowArr[$y] = $row;
            $y ++;
        }

        if($nonZeroArr = $nonZeroArr ?? []);

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
