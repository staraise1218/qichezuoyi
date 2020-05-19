<?php

namespace app\admin\model;

use think\Model;


class FormData extends Model
{

    

    

    // 表名
    protected $name = 'form_data';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'sex_text'
    ];
    

    
    public function getSexList()
    {
        return ['1' => __('Sex 1'), '0' => __('Sex 0')];
    }

    public function getAgeList()
    {
        return ['18~22', '23~27', '28~32', '33~37', '38~42', '43~47', '48~52', '53~57', '58~62', '63+'];
    }   


    public function getSexTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['sex']) ? $data['sex'] : '');
        $list = $this->getSexList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
