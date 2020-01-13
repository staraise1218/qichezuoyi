<?php

namespace app\index\controller;

use app\common\controller\Frontend;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function index()
    {
    	if(ismobile()){
    		$this->redirect('http://qichezuoyi.staraise.com.cn/m');
    	} else {
    		$this->redirect('http://qichezuoyi.staraise.com.cn/pc');
    	}
        
    }

}
