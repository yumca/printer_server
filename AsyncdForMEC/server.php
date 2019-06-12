<?php

define('PATH', dirname(__FILE__));
define('SPATH', '/System');  //其他路径常量
define('ENVIRONMENT', 'dev');	//切换环境仅本地生效
include_once(PATH . SPATH . '/Base/Base.php');
include_once(PATH . SPATH . '/Library/phpqrcode/phpqrcode.php');
include_once(PATH . SPATH . '/Library/PrintCtr.php');
include_once(PATH . SPATH . '/Library/SysCtr.php');
include_once(PATH . SPATH . '/Library/ParsePrintData.php');

//设置时区
date_default_timezone_set('Asia/Shanghai');

class serv_fuc extends Base
{
    public $keys = array(
        //存储盒子登录链接
        'link_key' => 'printlink',
        //根据uuid存储盒子登录链接
        'link_uuidkey' => 'printuuidlink',
        //存储有打印数据的盒子uuid集合
        'printuuid_list' => 'printuuid',
        //存储打印数据
        'printdata_key' => 'printdata',
        //临时打印数据存储区
        'tmpprintdata_key' => 'tmpprintdata',
        //未知数据
        'notknow_key' => 'notknowdata',
        //task 0号进程队列key 负责处理at指令  每个key后面拼接tcp链接标识符作为一个tcp链接队列列表  使用redis列表list
        'task_list0' => 'tasklist_0_',
    );
    
    public function __construct() {
        parent::__construct();
        $reserv = new RedisCache($this->conf['redis']);
        //清除链接
        $reserv->deletestr($this->keys['link_key']);
        $reserv->deletestr($this->keys['link_uuidkey']);
        $reserv->deletestr($this->keys['printuuid_list']);
        //清除队列
        $reserv->redis()->delete($reserv->redis()->keys($this->conf['redis']['REDIS_KEY_PREFIX'].$this->keys['task_list0']."*"));
        unset($reserv);
    }

    public function servconnect($serv, $fd, $reactor_id){
        var_dump(date('Y-m-d H:i:s').',serverGET:'.$fd.',connect;');
    }
    
    public function servreceive($serv, $fd, $reactor_id, $data){
        var_dump(date('Y-m-d H:i:s').',serverGET:'.$fd.',receive;');
        $reserv = new RedisCache($this->conf['redis']);
        if(!empty($data)){
            //获取数据协议包头
            $head = PrintCtr::parse_head($data);
            //初始化回应指令
            $res_package = null;
            //判断pmm发送包信息
            if($head['HeadFlags'][1] == 'aa55'){
                switch($head['CmdCode'][1]){
                    case '00': //echo检测   收到后在redis检测是否存在   不存在则获取设备地址
                        $linkdata = json_decode($reserv->getoneHashval($this->keys['link_key'], $fd), true);
                        if(!$linkdata['UUID'][1]){
                            //打包回应数据
                            $res_package = PrintCtr::put_pack('07');
                        }
                        break;
                    case '07': //获取设备地址
                        $package_data = PrintCtr::parse_addr($data);
                        $reserv->addHashval($this->keys['link_key'], $fd, $package_data);
                        $reserv->addHashval($this->keys['link_uuidkey'], $package_data['UUID'][1], $fd);
                        var_dump(date('Y-m-d H:i:s').',serverlogin:'.$fd.',login:'.$package_data['UUID'][1]);
                        break;
                    case '04': //空数据
//                        $package_data = PrintCtr::parse_addr($data);
//                        $reserv->addHashval($this->keys['link_key'], $fd, $package_data);
//                        $reserv->addHashval($this->keys['link_uuidkey'], $package_data['UUID'][1], $fd);
                        break;
                    case '05': //打印数据传输指令
                        $type = 'print';
                        $package_data = PrintCtr::parse_print($data);
                        $package_data['ctime'] = microtime();
                        $package_data['guid'] = getItemId();
                        //todo  此处应该处理打印信息   打印数据若超出一次可传数据大小则会分包传输  此处注意
                        //验证打印数据长度是否与实际相等
                        if($package_data['DataPackeTrueLen'] == $package_data['DataPackeLen_oct']){
                            //获取此硬件历史数据  无则初始化
                            $linkdata = json_decode($reserv->getoneHashval($this->keys['link_key'], $fd), true);
                            $printdata = array_merge($head,$package_data);
                            $tmpctime = explode(' ',$package_data['ctime']);
                            $tmpctime[0] = substr($tmpctime[0],2);
                            $ctime = $tmpctime[1].'.'.$tmpctime[0];
                            // $ctime = $tmpctime[0]+$tmpctime[1];
                            //存储硬件打印信息
                            $reserv->addHashval($this->keys['printdata_key'].'_'.$linkdata['UUID'][1],'P'.$ctime, $printdata);
                            $reserv->addHashval($this->keys['printuuid_list'], $linkdata['UUID'][1], floatval($ctime));
                            //打包回应数据
//                            $res_package = PrintCtr::put_pack($head, $package_data);
                            $package_data['DataPackeLen_left'] = '给定长度等于实际长度';
                            $this->cmd_qrcode($serv,$fd,['guid'=>$package_data['guid'],'data'=>'积分二维码']);
                        }else{
                            //如果长度不同则是分包发送
                            //查看临时存储区是否有数据
                            $tmpdata = $reserv->getoneHashval($this->keys['tmpprintdata_key'], $fd);
                            if(!empty($tmpdata)){
                                //非空存入未知数据表
                                $tmpdata = $reserv->push($this->keys['notknow_key'], json_decode($tmpdata, true));
                                //清空临时存储区
                                $reserv->delHashval($this->keys['tmpprintdata_key'], $fd);
                            }
                            //数据存入临时存储区
                            $reserv->addHashval($this->keys['tmpprintdata_key'], $fd, array_merge($head,$package_data));
                            //不回应
                        }
                        break;
                    default: //未知指令
                        $type = $head['CmdCode'][1];
                        $package_data['data'] = unpack('H*',$data);
                        $package_data['ctime'] = microtime();
                        $package_data['type'] = 'Cmd';
                        //存入未知存储区
                        $tmpdata = $reserv->push($this->keys['notknow_key'], array_merge($head,$package_data));
                        //todo  
                        break;
                }
            }else{
                //如果包头错误   则可能是打印剩余数据
                $package_data['data'] = unpack('H*',$data);
                //根据fd获取临时存储区数据
                $tmpprintdata = json_decode($reserv->getoneHashval($this->keys['tmpprintdata_key'], $fd),true);
                if(empty($tmpprintdata)){
                    //如果没有则存入未知存储区
                    $type = 'notKnow';
                    $package_data['ctime'] = microtime();
                    $package_data['type'] = 'notKnow';
                    $reserv->push($this->keys['notknow_key'], $package_data);
                }else{
                    $type = 'print-lastdata';
                    //如果有  则并入数据
                    $tmpprintdata['DataPackBuf'][1] .= $package_data['data'][1];
                    $datalen = strlen($tmpprintdata['DataPackBuf'][1])/2;
                    //获取此硬件历史数据  无则初始化
                    $linkdata = json_decode($reserv->getoneHashval($this->keys['link_key'], $fd), true);
                    $tmpctime = explode(' ',$tmpprintdata['ctime']);
                    $tmpctime[0] = substr($tmpctime[0],2);
                    $ctime = $tmpctime[1].'.'.$tmpctime[0];
                    // $ctime = $tmpctime[0]+$tmpctime[1];
                    $tmpprintdata['DataPackeTrueLen'] = $datalen;
                    if($tmpprintdata['DataPackeLen_oct'] < $datalen){
                        $tmpprintdata['DataPackeTrueLen'] = $datalen;
                        $tmpprintdata['DataPackeLen_left'] = '给定长度小于实际长度';
                        //存储硬件打印信息
                        $reserv->addHashval($this->keys['printdata_key'].'_'.$linkdata['UUID'][1], 'P'.$ctime, $tmpprintdata);
                        $reserv->addHashval($this->keys['printuuid_list'], $linkdata['UUID'][1], floatval($ctime));
                        //清空临时存储区
                        $reserv->delHashval($this->keys['tmpprintdata_key'], $fd);
                        $this->cmd_qrcode($serv,$fd,['guid'=>$tmpprintdata['guid'],'data'=>'积分二维码']);
                    }else if($tmpprintdata['DataPackeLen_oct'] == $datalen){
                        $tmpprintdata['DataPackeTrueLen'] = $datalen;
                        $tmpprintdata['DataPackeLen_left'] = '给定长度等于实际长度';
                        //存储硬件打印信息
                        $reserv->addHashval($this->keys['printdata_key'].'_'.$linkdata['UUID'][1], 'P'.$ctime, $tmpprintdata);
                        $reserv->addHashval($this->keys['printuuid_list'], $linkdata['UUID'][1], floatval($ctime));
                        //清空临时存储区
                        $reserv->delHashval($this->keys['tmpprintdata_key'], $fd);
                        $this->cmd_qrcode($serv,$fd,['guid'=>$tmpprintdata['guid'],'data'=>'积分二维码']);
                    }else{
                        //数据存入临时存储区
                        $tmpprintdata['DataPackeTrueLen'] = $datalen;
                        $tmpprintdata['DataPackeLen_left'] = '给定长度大于实际长度';
                        $reserv->addHashval($this->keys['tmpprintdata_key'], $fd, $tmpprintdata);
                    }
                }
            }

            //return result
            if($res_package !== null){
//                var_dump('sendDATA:'.$res_package.';hex:'.unpack('h*',$res_package)[1]);
                $serv->send($fd, $res_package);
            }
        }
        
//        var_dump('typeGET:'.$type);
    }
    
    public function servclose($serv, $fd, $reactor_id){
        $reserv = new RedisCache($this->conf['redis']);
//        $llen = $reserv->redis()->lLen($this->keys['task_list0'].$fd);
//        $reserv->redis()->lTrim($this->keys['task_list0'].$fd,0,$llen);
        $pack = json_decode($reserv->getoneHashval($this->keys['link_key'], $fd),true);
        $reserv->delHashval($this->keys['link_key'], $fd);
        $reserv->delHashval($this->keys['link_uuidkey'], $pack['UUID'][1]);
        unset($reserv);
        var_dump(date('Y-m-d H:i:s').',serverGET:'.$fd.',close;');
    }
    /**
     * 进程任务开始
     * @param type $serv
     * @param type $task_id
     * @param type $data
     */
    public function servWorkerStart($serv, $worker_id){
        //指定0号worker(非taskworker)进程启用定时器扫描在线状态
        if(!$serv->taskworker && intval($worker_id) === 0){
            $serv->tick(10000, function($id) use ($serv) {
                $serv->task("check_link", 0);
            });
        }
        //指定1号worker(非taskworker)进程启用定时器检测死链  发送echo指令
        if(!$serv->taskworker && intval($worker_id) === 1){
            $serv->tick(5000, function($id) use ($serv) {
                $serv->task("cmd_echo");
            });
        }
        //指定2号worker(非taskworker)进程启用更新盒子在线状态
        if(!$serv->taskworker && intval($worker_id) === 1){
            $serv->tick(60000, function($id) use ($serv) {
                $serv->task("up_alllink");
            });
        }
    }
    /**
     * task任务
     * @param type $serv
     * @param type $worker_id
     */
    public function servTask($serv, $task_id, $src_worker_id, $data){
        $reserv = new RedisCache($this->conf['redis']);
        if(is_string($data)){
            //更新盒子在线状态
            if($data == 'up_alllink'){
                //获取所有的链接
                $linkdata = $reserv->getallHashval($this->keys['link_key']);
                $inline = array();
                foreach ($linkdata as $k=>$v){
                    if(intval($k) > 0){
                        $v = json_decode($v,true);
                        $tmp['fd'] = $k;
                        $tmp['uuid'] = $v['UUID'][1];
                        $tmp['printer_status'] = hexdec($v['PrinterStatus'][1]);
                        $inline[] = $tmp;
                    }
                }
                $DbPdo = $this->connect_mysql('db_local');
                $DbPdo->execute('start;');
                $res = $DbPdo->execute('update box_user set box_stasus = 0,printer_status = 0');
                if($res === false){
                    $this->myLogs('WorkerId:'.$src_worker_id.';Task:'.$task_id.';data:'.$data, 'e', 'Task', array('SQL' => $DbPdo->getLastSql(), 'error' => $DbPdo->getLastErrorString()));
    //                trace('WorkerId:'.$src_worker_id.';Task:'.$task_id.';data:'.$data);
                    $DbPdo->execute('rollback;');
                    return;
                }
                if(!empty($inline)){
                    foreach($inline as $ik=>$iv){
                        $sql = "update box_user set box_stasus = 1,printer_status = '{$iv['printer_status']}' where uuid = '{$iv['uuid']}'";
                        $res = $DbPdo->execute($sql);
                        if($res === false){
                            $this->myLogs('WorkerId:'.$src_worker_id.';Task:'.$task_id.';data:'.$data.';uuid:'.$iv['uuid'], 'e', 'Task', array('SQL' => $DbPdo->getLastSql(), 'error' => $DbPdo->getLastErrorString()));
    //                        trace('WorkerId:'.$src_worker_id.';Task:'.$task_id.';data:'.$data.';uuid:'.$iv['uuid']);
                            $DbPdo->execute('rollback;');
                            return;
                        }
                    }
                }
                $DbPdo->execute('commit;');
            }
            //更新redis盒子在线状态
            if($data == 'check_link'){
                $linkdata = $reserv->getallHashval($this->keys['link_key']);
                $meclinkdata = $reserv->getallHashval($this->keys['shoplink_key']);
                $start = 0;
                $connection_list = array();
                for(;;){
                    $tmp_con = $serv->connection_list($start,100);
                    if(!$tmp_con || $start > 1000){
                        break;
                    }
                    $connection_list = array_merge($connection_list,$tmp_con);
                    $start += 100;
                }
                $connection_list = array_unique($connection_list);
                foreach ($linkdata as $k=>$v){
                    if(intval($k) > 0){
                        if(!in_array($k, $connection_list)){
                            $v = json_decode($v,true);
                            $reserv->delHashval($this->keys['link_key'], $k);
                            $reserv->delHashval($this->keys['link_uuidkey'], $v['UUID'][1]);
                            var_dump(date('Y-m-d H:i:s').',serverGET:'.$k.',close-redis;');
                        }
                    }
                }
//                foreach ($meclinkdata as $k=>$v){
//                    if(intval($k) > 0){
//                        if(!in_array($k, $connection_list)){
//                            $v = json_decode($v,true);
//                            $reserv->delHashval($this->keys['shoplink_key'], $k);
//                            $reserv->delHashval($this->keys['shoplink_meckey'], $v['meccode']);
//                            var_dump(date('Y-m-d H:i:s').',serverGET:'.$k.',close-redis;');
//                        }
//                    }
//                }
            }
            if($data == 'cmd_echo'){
                $cmd_echo = PrintCtr::put_pack('00');
                $connection_list = array();
                for(;;){
                    $tmp_con = $serv->connection_list($start,100);
                    if(!$tmp_con || $start > 1000){
                        break;
                    }
                    $connection_list = array_merge($connection_list,$tmp_con);
                    $start += 100;
                }
                $connection_list = array_unique($connection_list);
                foreach ($connection_list as $v){
                    $serv->send($v, $cmd_echo);
                }
            }
        }else if(is_array($data)){
            if($data['code'] == 'cmd_mac'){
                //打包回应数据
                $res_package = PrintCtr::put_pack('07');
                $serv->send($data['fd'], $res_package);
            }else if($data['code'] == 'cmd_qrcode'){
                $cmd_qrcode = PrintCtr::put_pack('04', $this->conf['site_url'].'jump.php?guid='.$data['guid']);
                $res=$serv->send($data['fd'], $cmd_qrcode);
            }else if($data['code'] == 'cmd_orgcode'){
                $cmd_orgcode = PrintCtr::put_pack('0a', $data['data']);
                $res=$serv->send($data['fd'], $cmd_orgcode);
            }
        }
    }
    
    /**
     * task任务完成回调
     * @param type $serv
     * @param type $task_id
     * @param type $data
     */
    public function servFinish($serv, $task_id, $data){
//        if(is_array($data) && !empty($data)){
//            if($data['type'] == 'ParsePrintData'){
//                
//            }
//        }
    }
    
    public function cmd_qrcode($serv,$fd,$data){
        $cmd_qrcode = PrintCtr::put_pack('04', $this->conf['site_url'].'jump.php?guid='.$data['guid']);
        $res = $serv->send($fd, $cmd_qrcode);
        if($res){
            return true;
        }else{
            return false;
        }
    }
}
//实例化服务
$server = new swoole_server('0.0.0.0',8082,SWOOLE_PROCESS,SWOOLE_SOCK_TCP);
//$server = new swoole_server('0.0.0.0',33002,SWOOLE_PROCESS,SWOOLE_SOCK_TCP);
//设置参数
$logfile = 'server-'.date('Ymd').'.log';
$server->set(array(
    //'reactor_num' => 2, //reactor thread num
    'worker_num' => 4,    //worker process num
    //'backlog' => 128,   //listen backlog
    'max_conn' => 1000,
    'open_tcp_nodelay' => 1,
    'task_worker_num' => 4, //task任务进程数
    //'max_request' => 5,
    'daemonize' => true,
    'log_file' => PATH . '/logs/swoole/'.$logfile,
    'pid_file' => PATH . '/logs/swoole/server.pid',
    'dispatch_mode' => 2,
    //'tcp_defer_accept' => 2,
//    'open_tcp_keepalive'=>1, //死链心跳检测 是否开启
//    'tcp_keepidle'=>20, //死链心跳检测 单位秒，连接在n秒内没有数据请求，将开始对此连接进行探测
//    'tcp_keepcount'=>1, //死链心跳检测 探测的次数，超过次数后将close此连接
//    'tcp_keepinterval'=>5, //死链心跳检测 探测的间隔时间，单位秒
));
//注册服务回调
$serv_fuc = new serv_fuc();
$server->on('connect', array($serv_fuc, 'servconnect'));
$server->on('receive', array($serv_fuc, 'servreceive'));
$server->on('close', array($serv_fuc, 'servclose'));
$server->on('Task', array($serv_fuc, 'servTask'));
$server->on('Finish', array($serv_fuc, 'servFinish'));
$server->on('WorkerStart', array($serv_fuc, 'servWorkerStart'));
//开启服务
$server->start();
