<?php

require_once 'Module/Common/Abstract.php';
include_once 'script/upParseData.php';

class Module_UpPrintToDatabase_Update extends Module_Common_Abstract {

    /**
     * 这个函数会被放入子进程死循环
     */
    protected function _init($param) {
        $this->_fetchStart($param);
    }

    private function _fetchStart($flag) {

        // 实例化		
        $class = new upParseData();
        //$class->myLogs('conten' , 'i' , '','debug');
        $class->run();
    }

}
