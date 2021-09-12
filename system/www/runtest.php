<?php
include_once(PATH . SPATH . '/www/controller.php');
class runtest extends controller
{
    public function __construct() {
        parent::__construct();
    }

    public function run(){
        system("cd /home/www/htdocs/print && php imgtest1.php && php imgtest2.php");
        $this->headers['Access-Control-Allow-Origin'] = '*';
        return array(
            'data' => 'succ'
        );
    }
}

//return new update();