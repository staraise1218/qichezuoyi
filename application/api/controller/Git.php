<?php

namespace app\api\controller;

use think\Db;

class Git {


	public function pull(){
		$output = shell_exec("cd /workspace/www/qichezuoyi; git pull 2<&1");
        echo "<pre>$output</pre>";
	}

	public function testpull(){
		$output = shell_exec("cd /home/www/guodian; git pull 2<&1");
        echo "<pre>$output</pre>";
	}
}