<?php

define('PATH', dirname(__FILE__));
define('SPATH', '/System');  //其他路径常量
define('ENVIRONMENT', 'dev');	//切换环境仅本地生效
include_once(PATH . SPATH . '/Base/Base.php');
include_once(PATH . SPATH . '/Library/ParsePrintData.php');

//设置时区
date_default_timezone_set('Asia/Shanghai');

class serv_fuc extends Base
{
    private $http_ini;
    private $host_dir;
    public $keys = array(
        //存储盒子登录链接
        'link_key' => 'printlink',
        //根据uuid存储盒子登录链接
        'link_uuidkey' => 'printuuidlink',
        //存储店铺登录链接
        'shoplink_key' => 'shoplink',
        //根据uuid存储店铺登录链接
        'shoplink_meckey' => 'shopmeclink',
        //存储有打印数据的盒子uuid集合
        'printuuid_list' => 'printuuid',
        //队列数据
        'data_queue' => 'print_queue',
        'data_queue_data' => 'print_queue_data',
    );
    public function __construct() {
        $this->http_ini = parse_ini_file(PATH.SPATH.'/Conf/http_ini.ini','ture');
        parent::__construct();
        $this->host_dir = $this->conf['host_dir'];
        $reserv = new RedisCache($this->conf['redis']);
        //清除链接
        $reserv->deletestr($this->keys['shoplink_key']);
        $reserv->deletestr($this->keys['shoplink_meckey']);
        unset($reserv);
    }

    public function is_json($json){
        json_decode($json);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    
    public function servopen($serv, $request){
        //var_dump(date('Y-m-d H:i:s').',serverGET:'.$request->fd.',connect;');
    }
    
    public function servmessage($serv, $frame){
        $fd = $frame->fd;
        $data = $frame->data;
        //var_dump(date('Y-m-d H:i:s').',serverGET:'.$fd.',receive;');
        $reserv = new RedisCache($this->conf['redis']);
        if($this->is_json($data)){
            $data = json_decode($data,true);
            if(!empty($data['msg_type'])){
                if($data['msg_type'] == 'login' && !empty($data['token'])){
                    $DbPdo = $this->connect_mysql('db_local');
                    $shop = $DbPdo->getRow("select * from box_user where token = '{$data['token']}'");
                    if($shop){
                        //检测当前店铺是否有在线登录
                        $oldline = $reserv->getoneHashval($this->keys['shoplink_meckey'], $shop['meccode']);
                        if(!empty($oldline) && $oldline != $fd){
                            if(strpos($oldline,'"') || strpos($oldline,'\'')){
                                $oldline = str_replace(array('"','\''), '', $oldline);
                            }
                            $reserv->delHashval($this->keys['shoplink_key'], $oldline);
                            $reserv->delHashval($this->keys['shoplink_meckey'], $shop['meccode']);
                            $serv->push($oldline, json_encode(['code'=>400,'msg_type'=>'unbindlogin']));
                        }
                        $reserv->addHashval($this->keys['shoplink_key'], $fd, $shop);
                        $reserv->addHashval($this->keys['shoplink_meckey'], $shop['meccode'], $fd);
                        $serv->push($fd, json_encode(['code'=>200,'msg'=>'绑定成功']));
                    }else{
                        $serv->push($fd, json_encode(['code'=>400,'msg_type'=>'loginfail']));
                    }
                }elseif($data['msg_type'] == 'orders' && !empty($data['token'])){

                }
            }
        }
    }
    
    public function servclose($serv, $fd){
        $reserv = new RedisCache($this->conf['redis']);
        $shop = json_decode($reserv->getoneHashval($this->keys['shoplink_key'], $fd),true);
        $reserv->delHashval($this->keys['shoplink_key'], $fd);
        $reserv->delHashval($this->keys['shoplink_meckey'], $shop['meccode']);
        unset($reserv);
        //var_dump(date('Y-m-d H:i:s').',serverGET:'.$fd.',close;');
    }
    
    /**
     * task任务
     * @param type $serv
     * @param type $worker_id
     */
    public function servTask($serv, $task_id, $src_worker_id, $data){
        $reserv = new RedisCache($this->conf['redis']);
        if(is_array($data) && $data['type'] == 'upDataToQrcode'){
            $ParsePrintData = new ParsePrintData($data['uuid']);
            $result = $ParsePrintData->upDataToQrcode();
            if($result != false){
                //如果返回值不为false  则表明解析成功  需要发送二维码
                //查询店铺是否在线
//                $shopfd = $reserv->getoneHashval($this->keys['shoplink_meckey'], $ParsePrintData->shopconf['meccode']);
//                if($shopfd){
//                    //$shop = $reserv->getoneHashval($this->keys['shoplink_key'], $shopfd);
//                    $sendinfo = json_encode(array('code'=>200,'msg_type'=>'code_url','url'=>$result['url'],'money'=>$result['money']));
//                    $serv->push($shopfd, $sendinfo);
//                }
                $res = $reserv->addHashval($this->keys['data_queue_data'], $ParsePrintData->shopconf['meccode'].'_'.$result['data'][0]['data']['guid'],$result);
                if($res){
                    $reserv->push($this->keys['data_queue'], $ParsePrintData->shopconf['meccode'].'_'.$result['data'][0]['data']['guid']);
                }else{
                    $path = $this->conf['txtpath'].'fialdata/'.date('Ym').'/'.date('d');
                    $ParsePrintData->InitDir($path);
                    file_put_contents($path.'/'.$result['data'][0]['data']['guid'].'.txt', var_export($result));
                }
                //$result['type'] = 'ParsePrintData';
                //$serv->finish($result);
            }
        }
        //指定1号task进程处理task AT队列
//        if(intval($src_worker_id) === 1){
//            trace('Task:' . $task_id . ';'.$data);
//            //检测所有的链接
//            if($data == 'check_alllink'){
//                $sysctr = new SysCtr();
//                $senddata = $sysctr->enCmdCode(array('stat'=>'sys','cmdcode'=>'check_online'));
//                $connection_list = $serv->connection_list(0,10);
//                if(!empty($connection_list)){
//                    foreach ($connection_list as $lfd){
//                        $reserv->push($this->keys['task_list0'].$lfd, array('check_online'));
//                        $res = $serv->send($lfd, $senddata['sendinfo']);
//                        if($res === false){
//                            $reserv->pop($this->keys['task_list0'].$lfd, false);
//                        }
//                    }
//                }
//            }
//        }
    }
    
    /**
     * task任务完成回调
     * @param type $serv
     * @param type $task_id
     * @param type $data
     */
    public function servFinish($serv, $task_id, $data){
        if(is_array($data) && !empty($data)){
            if($data['type'] == 'ParsePrintData'){
                //$data['data']
//                $db_name = 'dbname=' . $this->conf['db_local']['DB_PREFIX'] . 'print';
//                $db_host = 'host=' . $this->conf['db_local']['DB_HOST'];
//                $db_port = 'port=' . $this->conf['db_local']['DB_PORT'];
//                $DSN = 'mysql:' . join(';', array($db_name, $db_host, $db_port));
//                $DbPdo = new \DbPdo($DSN, $this->conf['db_local']['DB_USER'], $this->conf['db_local']['DB_PWD'], '');
//                $DbPdo->execute('start;');
//                if($res === false){
//                    $this->myLogs('WorkerId:'.$src_worker_id.';Task:'.$task_id.';data:'.$data, 'e', 'Task', array('SQL' => $DbPdo->getLastSql(), 'error' => $DbPdo->getLastErrorString()));
//                    $DbPdo->execute('rollback;');
//                    return;
//                }
//                if(!empty($inline)){
//                    foreach($inline as $ik=>$iv){
//                        $sql = "update box_user set box_stasus = 1,printer_status = '{$iv['printer_status']}' where uuid = '{$iv['uuid']}'";
//                        $res = $DbPdo->execute($sql);
//                        if($res === false){
//                            $this->myLogs('WorkerId:'.$src_worker_id.';Task:'.$task_id.';data:'.$data.';uuid:'.$iv['uuid'], 'e', 'Task', array('SQL' => $DbPdo->getLastSql(), 'error' => $DbPdo->getLastErrorString()));
//                            $DbPdo->execute('rollback;');
//                            return;
//                        }
//                    }
//                }
//                $DbPdo->execute('commit;');
                //mysql判断是否有某张表
//                $org_table = 'print_org_'.date('Ym');
//                $res = $DbPdo->getRow("SELECT table_name FROM information_schema.TABLES WHERE table_name ='$org_table';");
//                if(!$res){
//                    $table_sql = "CREATE TABLE `$org_table` (
//                        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
//                        `uuid` varchar(50) DEFAULT NULL,
//                        `PackeId` varchar(20) DEFAULT NULL,
//                        `data` longtext,
//                        `ctime` varchar(50) DEFAULT NULL,
//                        `uptime` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
//                        `is_merge` tinyint(1) DEFAULT '0' COMMENT '是否已解析合并数据  0未合并  1已合并',
//                        PRIMARY KEY (`id`),
//                        KEY `idx_is_merge` (`is_merge`) USING BTREE
//                    ) ENGINE=InnoDB AUTO_INCREMENT=327 DEFAULT CHARSET=utf8;";
//                    $res = $DbPdo->execute($table_sql);
//                    if($res === false){
//                        $this->myLogs('org:创建表失败,表名:' . $org_table, 'e', $this->logModule, array('SQL' => $DbPdo->getLastSql(), 'error' => $DbPdo->getLastErrorString()));
//                        trace('org:创建表失败,表名:' . $org_table);
//                        exit;
//                    }
//                }
//                $db_name = 'dbname=' . $this->conf['db_local']['DB_PREFIX'] . 'print';
//                $db_host = 'host=' . $this->conf['db_local']['DB_HOST'];
//                $db_port = 'port=' . $this->conf['db_local']['DB_PORT'];
//                $DSN = 'mysql:' . join(';', array($db_name, $db_host, $db_port));
//                $DbPdo = new \DbPdo($DSN, $this->conf['db_local']['DB_USER'], $this->conf['db_local']['DB_PWD'], '');
//                $ParsePrintData = $data['data'];
//                $DbPdo->execute('start;');
//                foreach ($ParsePrintData as $v){
//                    if(!empty($v['data'])){
//
//                    }
//                }
//                $DbPdo->execute('commit;');
            }
        }
    }
    
    /**
     * 进程任务开始
     * @param type $serv
     * @param type $task_id
     * @param type $data
     */
    public function servWorkerStart($serv, $worker_id){
        //指定2号worker(非taskworker)进程启用定时器检查是否有打印数据
        if(!$serv->taskworker && intval($worker_id) === 0){
            $reserv = new RedisCache($this->conf['redis']);
            $serv->tick(1000, function($id) use ($serv, $reserv) {
                $uuids = $reserv->getallHashval($this->keys['printuuid_list']);
                if(!empty($uuids)){
                    foreach($uuids as $k=>$v){
                        if(strpos($v,'"') || strpos($v,'\'')){
                            $v = str_replace(array('"','\''), '', $v);
                        }
                        $now = microtime();
                        $tmpnow = explode(' ',$now);
                        $tmpnow[0] = substr($tmpnow[0],2);
                        $nowtime = floatval($tmpnow[1].'.'.$tmpnow[0]);
                        if(($nowtime - $v) > 1){
                            //投递task任务
                            $serv->task(['type'=>'upDataToQrcode','uuid'=>$k]);
                            $reserv->delHashval($this->keys['printuuid_list'], $k);
                        }
                    }
                }
            });
        }
    }

    public function servrequest($request, $response){
        $path_info = substr($request->server['request_uri'],1);
        $host_arr = explode(':', $request->header['host']);
        $host = $host_arr[0];
        $port = intval($host_arr[1]) > 0 ?: 80;
        $result = ['data' => ''];
        $content_type = 'text/html';
        $content_status = 200;
        //判断主机地址是否存在
        if(isset($this->host_dir[$host]) && !empty($this->host_dir[$host])){
            if(empty($path_info)){
                if(file_exists($this->host_dir[$host].'/index.php')){
                    $path_info = 'index.php';
                }else if(file_exists($this->host_dir[$host].'/index.html')){
                    $path_info = 'index.html';
                }else if(file_exists($this->host_dir[$host].'/index.htm')){
                    $path_info = 'index.htm';
                }
            }
            if(!empty($path_info) && file_exists($this->host_dir[$host].'/'.$path_info)){
                $path_info_arr = explode('/', $path_info);
                $ctr_arr = explode('.', end($path_info_arr));
                $content_type = isset($this->http_ini['minetype'][end($ctr_arr)])? $this->http_ini['minetype'][end($ctr_arr)] :'application/octet-stream';
                $response->header('Content-Type', $content_type);
                if(end($ctr_arr) == 'php'){
                    if(!class_exists($ctr_arr[0])){
                        include $this->host_dir[$host].'/'.$path_info;
                    }
//                    $controller = new $ctr_arr[0]();
//                    $controller->request = $request;
//                    $controller->response = $response;
//                    $result = $controller->run();
//                    foreach($controller->headers as $k=>$v){
//                        if($k == 'Content-Type'){
//                            $content_type = $v;
//                        }
//                        $response->header($k, $v);
//                    }
//                    unset($controller);
                    //下面是不销毁类  常驻内存  判断类是否存在内存  不存在则实例化
                    if(!($$ctr_arr[0] instanceof $ctr_arr[0])){
                        $$ctr_arr[0] = new $ctr_arr[0]();
                    }
                    $$ctr_arr[0]->request = $request;
                    $$ctr_arr[0]->response = $response;
                    $result = $$ctr_arr[0]->run();
                    $content_status = $$ctr_arr[0]->status;
                    foreach($$ctr_arr[0]->headers as $k=>$v){
                        if($k == 'Content-Type'){
                            $content_type = $v;
                        }
                        $response->header($k, $v);
                    }
                }elseif(substr($content_type, 0, 4) == 'text'){
                    $result['data'] = file_get_contents($this->host_dir[$host].'/'.$path_info);
                }else{
                    $result['data'] = $this->host_dir[$host].'/'.$path_info;
                }
            }
        }else{
            $content_status = 404;
        }
        $response->status($content_status);
        if(substr($content_type, 0, 4) != 'text'){
            $response->sendfile($result['data']);
        }else{
            $response->end($result['data']);
        }
    }
}
//实例化服务
$http = new swoole_websocket_server("0.0.0.0", 8080);
//$http = new swoole_http_server("0.0.0.0", 33001);
//设置参数
$logfile = 'httpserver-'.date('Ymd').'.log';
$http->set(array(
    //'reactor_num' => 2, //reactor thread num
    'worker_num' => 4,    //worker process num
    //'backlog' => 128,   //listen backlog
    'max_conn' => 1000,
    'open_tcp_nodelay' => 1,
    'task_worker_num' => 4, //task任务进程数
    //'max_request' => 5,
    'dispatch_mode' => 2,
    //'tcp_defer_accept' => 2,
//    'open_tcp_keepalive'=>1, //死链心跳检测 是否开启
//    'tcp_keepidle'=>20, //死链心跳检测 单位秒，连接在n秒内没有数据请求，将开始对此连接进行探测
//    'tcp_keepcount'=>1, //死链心跳检测 探测的次数，超过次数后将close此连接
//    'tcp_keepinterval'=>5, //死链心跳检测 探测的间隔时间，单位秒
    'daemonize' => true,
    'log_file' => PATH . '/logs/swoole/'.$logfile,
    'pid_file' => PATH . '/logs/swoole/httpserver.pid',
));
//注册服务回调
$serv_fuc = new serv_fuc();
$http->on('open', array($serv_fuc, 'servopen'));
$http->on('message', array($serv_fuc, 'servmessage'));
$http->on('close', array($serv_fuc, 'servclose'));
$http->on('Task', array($serv_fuc, 'servTask'));
$http->on('Finish', array($serv_fuc, 'servFinish'));
$http->on('WorkerStart', array($serv_fuc, 'servWorkerStart'));
$http->on('request', array($serv_fuc, 'servrequest'));
$http->start();