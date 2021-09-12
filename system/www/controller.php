<?php
include_once(PATH . SPATH . '/Base/Base.php');
class controller extends Base
{
    protected $request, $response, $cookie, $headers = array(), $DbPdo, $redirect = false, $status = 200;
    public function __construct() {
        parent::__construct();
        $this->DbPdo = $this->connect_mysql('db_local');
        $this->headers['Content-Type'] = 'text/html';
    }

    public function is_json($json){
        json_decode($json);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    
    public function __set($property,$value){
        $this->$property = $value;
    }
    
    public function __get($propertyName){
        return $this->$propertyName;
    }
}