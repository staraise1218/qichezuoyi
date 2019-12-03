<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 人员信息
 *
 * @icon fa fa-circle-o
 */
class People extends Backend
{
    
    /**
     * People模型对象
     * @var \app\admin\model\People
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\People;
        $this->view->assign("sexList", $this->model->getSexList());
        $this->view->assign("ageList", $this->model->getAgeList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    // 获取身高选项
    public function getHeightSelectOption(){
        $sex = input('sex', 1);
        $selectValue = input('selectValue', NULL);

        $menHeightList = ['160-','161~165','166~170','171~175','176~180','181~185','186+'];
        $womenHeightList = ['150-', '151~155', '156~160', '161~165', '166~170', '171~175', '176+'];

        $list = $sex == 1 ? $menHeightList : $womenHeightList;

        $html = '';
        foreach ($list as $val) {
            $selected = $selectValue == $val ? 'selected=selected' : '';
            $html .= "\t\n<option value='{$val}' {$selected}>{$val}</option>";
        }

        $this->success('', null, $html);
    }
    

    // 获取体重选项
    public function getWeightSelectOption(){
        $sex = input('sex', 1);
        $selectValue = input('selectValue', NULL);

        $menWeightList = ['50-', '51~55', '56~60', '61~65', '66~70', '71~75', '76~80', '81~85', '86~90', '91~95', '95+'];
        $womenWeightList = ['40-', '41~45', '46~50', '51~55', '56~60', '61~65', '66~70', '71~75', '76+'];

        $list = $sex == 1 ? $menWeightList : $womenWeightList;

        $html = '';
        foreach ($list as $val) {
            $selected = $selectValue == $val ? 'selected=selected' : '';
            $html .= "\t\n<option value='{$val}' {$selected}>{$val}</option>";
        }

        $this->success('', null, $html);
    }
    

    // 获取体重选项
    public function getShapeSelectOption(){
        $sex = input('sex', 1);
        $selectValue = input('selectValue', NULL);

        $menShapeList = ['瘦削', '标准', '上半身胖', '下半身胖', '微胖', '极胖'];
        $womenShapeList = ['上半身胖', '下半身胖', '微胖', '极胖'];

        $list = $sex == 1 ? $menShapeList : $womenShapeList;

        $html = '';
        foreach ($list as $val) {
            $selected = $selectValue == $val ? 'selected=selected' : '';
            $html .= "\t\n<option value='{$val}' {$selected}>{$val}</option>";
        }

        $this->success('', null, $html);
    }

    /**
     * 导入
     */
    protected function importData()
    {
        $file = $this->request->request('file');
        if (!$file) {
            $this->error(__('Parameter %s can not be empty', 'file'));
        }
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        if (!is_file($filePath)) {
            $this->error(__('No results were found'));
        }
        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            $this->error(__('Unknown data format'));
        }
        if ($ext === 'csv') {
            $file = fopen($filePath, 'r');
            $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
            $fp = fopen($filePath, "w");
            $n = 0;
            while ($line = fgets($file)) {
                $line = rtrim($line, "\n\r\0");
                $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                if ($encoding != 'utf-8') {
                    $line = mb_convert_encoding($line, 'utf-8', $encoding);
                }
                if ($n == 0 || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ($ext === 'xls') {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

        //导入文件首行类型,默认是注释,如果需要使用字段名称请使用name
        $importHeadType = isset($this->importHeadType) ? $this->importHeadType : 'comment';

        $table = $this->model->getQuery()->getTable();
        $database = \think\Config::get('database.database');
        $fieldArr = [];
        $list = db()->query("SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?", [$table, $database]);
        foreach ($list as $k => $v) {
            if ($importHeadType == 'comment') {
                $fieldArr[$v['COLUMN_COMMENT']] = $v['COLUMN_NAME'];
            } else {
                $fieldArr[$v['COLUMN_NAME']] = $v['COLUMN_NAME'];
            }
        }

        //加载文件
        $insert = [];
        try {
            if (!$PHPExcel = $reader->load($filePath)) {
                $this->error(__('Unknown data format'));
            }
            $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
            $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
            $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
            $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);
            $fields = [];
            for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $fields[] = $val;
                }
            }

            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                $values = [];
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $values[] = is_null($val) ? '' : $val;
                }
                $row = [];
                $temp = array_combine($fields, $values);
                foreach ($temp as $k => $v) {
                    if (isset($fieldArr[$k]) && $k !== '') {
                        $row[$fieldArr[$k]] = $v;
                    }
                }
                if ($row) {
                    $insert[] = $row;
                }
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
        if (!$insert) {
            $this->error(__('No rows were updated'));
        }

        try {
            //是否包含admin_id字段
            $has_admin_id = false;
            foreach ($fieldArr as $name => $key) {
                if ($key == 'admin_id') {
                    $has_admin_id = true;
                    break;
                }
            }
            if ($has_admin_id) {
                $auth = Auth::instance();
                foreach ($insert as &$val) {
                    if (!isset($val['admin_id']) || empty($val['admin_id'])) {
                        $val['admin_id'] = $auth->isLogin() ? $auth->id : 0;
                    }
                }
            }
            $this->model->saveAll($insert);
        } catch (PDOException $exception) {
            $msg = $exception->getMessage();
            if (preg_match("/.+Integrity constraint violation: 1062 Duplicate entry '(.+)' for key '(.+)'/is", $msg, $matches)) {
                $msg = "导入失败，包含【{$matches[1]}】的记录已存在";
            };
            $this->error($msg);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success();
    }
}
