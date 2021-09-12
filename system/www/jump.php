<?php
include_once(PATH . SPATH . '/www/controller.php');
class jump extends controller
{
    public function __construct() {
        parent::__construct();
    }

    public function run(){
        $jumpurl = $this->request->get['jumpurl'];
        $this->headers['Access-Control-Allow-Origin'] = '*';
        $this->status = 302;
        $this->headers['Location'] = $jumpurl;
        return array(
            'data' => ''
        );
    }
}

//return new update();