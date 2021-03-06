<?php

namespace app\admin\model;

use think\Model;


class People extends Model
{

    // 表名
    protected $name = 'people';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'sex_text'
    ];
    

    
    public function getSexList()
    {
        return ['0' => __('Sex 0'), '1' => __('Sex 1')];
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
