<?php

/**
 * 	处理队列原始数据
 * 	2018年10月29日
 *
 */
if (!defined('PATH')) {
    define('PATH', dirname(dirname(dirname(dirname(__FILE__)))));
}

require_once PATH . '/System/Base/Base.php';

class UpParseData extends Base {

    protected $redisKeyQueue = 'print_queue';
    protected $redisValQueue = 'print_queue_data';
    protected $tableTime = 'table_time';
    public $logModule = 'UpParseData';
    public $logPid; //log处理进程ID
    public $redis,$DbPdo;

    public function __construct() {
        parent::__construct();
        //$this->logPid = getlogPid(); //处理进程ID
        $this->redis = new RedisCache($this->conf['redis']);
        $this->DbPdo = $this->connect_mysql('db_local');
    }

    /**
     * 自动请求回调地址
     */
    public function run() {
        for(;;){
            try {
                $queue_key = $this->redis->pop($this->redisKeyQueue);
                if($queue_key){
                    $queue_data = json_decode($this->redis->getoneHashval($this->redisValQueue,$queue_key),true);
                    if($queue_data){
                        $nowtable = $this->checktable();
                        if($nowtable === false){
                            continue;
                            //return;
                        }
    //                    $this->redis->delHashval($this->redisValQueue,$queue_key);
                        $this->DbPdo->execute('begin;');
                        $isrollback = false;
                        foreach ($queue_data['data'] as $k=>$v){
                            //data
                            $sql1 = "insert into {$nowtable['printtable']}(uuid,CmdCode,PackeId,DataPackBuf,DataPackeLen_oct,DataPackeTrueLen,DataPackeLen_left,mctime,ctime,guid,img,orgdata,is_parse) "
                                    . "values('{$v['data']['uuid']}','{$v['data']['CmdCode']}','{$v['data']['PackeId']}','{$v['data']['DataPackBuf']}',"
                                    . "'{$v['data']['DataPackeLen_oct']}','{$v['data']['DataPackeTrueLen']}','{$v['data']['DataPackeLen_left']}','{$v['data']['mctime']}',"
                                    . "'{$v['data']['ctime']}','{$v['data']['guid']}','{$v['data']['img']}','{$v['data']['orgdata']}','{$v['data']['is_parse']}')";
                            $res1 = $this->DbPdo->execute($sql1);
                            if ($res1 === false) {
                                $isrollback = true;
                                $this->myLogs('com:print数据存储失败,uuid:' . $v['data']['uuid'] . ';guid:' . $v['data']['guid'], 'e', $this->logModule, array('SQL' => $sql1, 'error' => $this->DbPdo->getLastErrorString()));
                                trace('com:print数据存储失败,uuid:' . $v['data']['uuid'] . ';guid:' . $v['data']['guid']);
                                break;
                            }
                            //orderdata
                            $sql2 = "INSERT INTO {$nowtable['ordertable']} (bid,shopid,shopcode,guid,order_id,amount,order_time,method) "
                                    . "VALUES ('{$v['orderdata']['bid']}','{$v['orderdata']['shopid']}','{$v['orderdata']['shopcode']}',"
                                    . "'{$v['orderdata']['guid']}','{$v['orderdata']['order_id']}', '{$v['orderdata']['amount']}',"
                                    . "'{$v['orderdata']['times']}', '{$v['orderdata']['method']}');";
                            $res2 = $this->DbPdo->execute($sql2);
                            if ($res2 === false) {
                                $isrollback = true;
                                $this->myLogs('com:order_detail数据存储失败,uuid:' . $v['data']['uuid'] . ';guid:' . $v['data']['guid'], 'e', $this->logModule, array('SQL' => $res2, 'error' => $this->DbPdo->getLastErrorString()));
                                trace('com:order_detail数据存储失败,uuid:' . $v['data']['uuid'] . ';guid:' . $v['data']['guid'].';'.$sql2);
                                break;
                            }
                        }

                        if($isrollback === false){
                            $this->redis->delHashval($this->redisValQueue,$queue_key);
                            $this->DbPdo->execute('commit;');
                        }else{
                            $res = $this->redis->addHashval($this->redisValQueue.'_fail', $queue_key,$queue_data);
                            if($res){
                                $this->redis->delHashval($this->redisValQueue,$queue_key);
                            }
                            $this->DbPdo->execute('rollback;');
                        }
                    }else{
                        $this->myLogs('com:队列获取数据失败,queue_key:' . $queue_key, 'e', $this->logModule, array());
                    }
                }
            } catch (Exception $e) {
                //$this->myLogs('读取redis 队列数据失败!', 'e', $this->logModule, $e->getMessage());
            }
        }
    }
    
    function checktable(){
        //当前日期
        $nowdate = date('Ym');
        $lastdate = $this->redis->getstr($this->tableTime);
        $printtable = 'print_'.$nowdate;
        $ordertable = 'order_detail_'.$nowdate;
        if($nowdate != $lastdate){
            //mysql判断是否有某张表
            $res1 = $this->DbPdo->getRow("SELECT table_name FROM information_schema.TABLES WHERE table_name ='$printtable';");
            if(!$res1){
                $table_sql1 = "CREATE TABLE `{$printtable}` (
                    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `uuid` varchar(50) DEFAULT NULL,
                    `guid` varchar(50) DEFAULT NULL COMMENT '平台发送猫酷统一订单编号',
                    `img` varchar(255) DEFAULT NULL,
                    `orgdata` text,
                    `is_parse` tinyint(2) DEFAULT '0' COMMENT '0为解析  1有图片  2有文字 10解析失败',
                    `CmdCode` varchar(20) DEFAULT NULL,
                    `PackeId` varchar(20) DEFAULT NULL,
                    `DataPackBuf` longtext,
                    `DataPackeLen_oct` varchar(10) DEFAULT NULL,
                    `DataPackeTrueLen` varchar(10) DEFAULT NULL,
                    `DataPackeLen_left` varchar(255) DEFAULT NULL,
                    `mctime` varchar(50) DEFAULT NULL,
                    `ctime` varchar(50) DEFAULT NULL,
                    `createtime` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                    `uptime` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";
                $res = $this->DbPdo->execute($table_sql1);
                if($res === false){
                    $this->myLogs('org:创建表失败,表名:' . $printtable, 'e', $this->logModule, array('SQL' => $this->DbPdo->getLastSql(), 'error' => $this->DbPdo->getLastErrorString()));
                    trace('org:创建表失败,表名:' . $printtable);
                    return false;
                }
            }
            $res2 = $this->DbPdo->getRow("SELECT table_name FROM information_schema.TABLES WHERE table_name ='$ordertable';");
            if(!$res2){
                $table_sql2 = "CREATE TABLE `{$ordertable}` (
                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    `bid` int(10) DEFAULT '0' COMMENT '对应店铺表id',
                    `shopid` varchar(255) DEFAULT NULL COMMENT '酷猫商城店铺id',
                    `shopcode` varchar(255) DEFAULT NULL COMMENT '酷猫商城店铺编号',
                    `guid` varchar(50) DEFAULT NULL COMMENT '平台发送猫酷统一订单编号',
                    `order_id` varchar(255) DEFAULT NULL COMMENT '订单号',
                    `amount` int(10) DEFAULT '0' COMMENT '金额',
                    `method` varchar(255) DEFAULT NULL COMMENT '支付方式',
                    `is_deleted` tinyint(4) DEFAULT '0' COMMENT '10删除',
                    `order_time` timestamp NULL DEFAULT NULL COMMENT '订单生成时间',
                    `createtime` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";
                $res = $this->DbPdo->execute($table_sql2);
                if($res === false){
                    $this->myLogs('org:创建表失败,表名:' . $ordertable, 'e', $this->logModule, array('SQL' => $this->DbPdo->getLastSql(), 'error' => $this->DbPdo->getLastErrorString()));
                    trace('org:创建表失败,表名:' . $ordertable);
                    return false;
                }
            }
        }
        return ['printtable'=>$printtable,'ordertable'=>$ordertable];
    }

}
// 实例化		
//$class = new upParseData();
//$class->myLogs('conten' , 'i' , '','debug');
//$class->run();