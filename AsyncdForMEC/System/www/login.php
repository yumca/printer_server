<?php
include_once(PATH . SPATH . '/www/controller.php');
class login extends controller
{
    public function __construct() {
        parent::__construct();
    }

    public function run(){
        $this->headers['Access-Control-Allow-Origin'] = '*';
        $meccode = $this->request->post['meccode'];
        if(!$meccode){
            return ['data' => json_encode(['code'=>400,'msg'=>'meccode不能为空'])];
        }
        
        $shop = $this->DbPdo->getRow("select * from box_user where meccode = '{$meccode}'");
        if(!$shop){
            return ['data' => json_encode(['code'=>400,'msg'=>'错误的meccode'])];
        }
        $token = strtoupper(md5($meccode.time()));
        $res = $this->DbPdo->execute("update box_user set token = '{$token}' where meccode = '{$meccode}'");
        $this->myLogs("sql:update box_user set token = '{$token}' where meccode = '{$meccode}'", 'n', 'http-login', array('result'=>$res,'SQL' => $this->DbPdo->getLastSql(), 'error' => $this->DbPdo->getLastErrorString()));
        if($res){
            return ['data' => json_encode(['code'=>200,'msg'=>'登录成功','token'=>$token,'shop_name'=>$shop['shop_name']])];
        }else{
            return ['data' => json_encode(['code'=>400,'msg'=>'登录失败'])];
        }
    }
}