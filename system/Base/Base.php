<?php

/**
 *
 * 脚本基类
 * 2017/01/22
 *
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '256M');
set_time_limit(0);

define('DEBUG', true); //DB调试
if(!defined('PATH')){
    define('PATH', dirname(dirname(dirname(__FILE__))));
}
if(!defined('SPATH')){
    define('SPATH', '/System');
}
include_once(PATH . SPATH . '/Common/function.php');
include_once(PATH . SPATH . '/Library/RedisCache.php');
include_once(PATH . SPATH . '/Library/MyLogs.php');
include_once(PATH . SPATH . '/Library/DbPdo.php');
include_once(PATH . SPATH . '/Library/Queue.php');

class Base {

    public $logs;
    public $conf;

    public function __construct() {

        //读取配置文件
        $configPath = PATH . SPATH . '/Conf/config.php';
        if (file_exists($configPath)) {
            $_conf = include $configPath;
            if (!defined('ENVIRONMENT')) {
                define('ENVIRONMENT', 'dev');
            }
            $this->conf = isset($_conf['dev']) ? $_conf[ENVIRONMENT] : $_conf;
        } else {
            echo '配置文件不存在!';
            exit;
        }
    }

    protected function _return($code = '500', $data = null) {
        header('Content-Type: application/json; charset=utf-8');
        $data = array('code' => $code, 'message' => $data);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function _redirect($url) {
        header("Location:" . $url);
        exit;
    }

    public function myLogs($msg = '', $type = '', $mode = 'demo', $data = '') {
        $this->logs = new MyLogs($mode);
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $this->logs->doLog($msg, $data, $type, current($trace));
    }

    //链接数据库
    //mall_main,mall_thirdpart,userinfo,mall_coupon
    protected function connect_mysql($dbconnect = null, $db_idx = null) {
        if ($dbconnect !== null) {
            if (in_array($dbconnect, array('db_local'))) {
                //单库
                if (!isset($this->conf[$dbconnect])) {
                    trace('mysql配置不存在!', 'w');
                    $this->myLogs('mysql单库配置不存在!', 'f', 'dbError', array('file' => $_SERVER['PHP_SELF']));
                    exit;
                }
                $dbConf = $this->conf[$dbconnect];
                $db_name = 'dbname=' . $dbConf['DB_PREFIX'] . $dbConf['DB_NAME'];
                $db_host = 'host=' . $dbConf['DB_HOST'];
                $db_port = 'port=' . $dbConf['DB_PORT'];
                $DSN = 'mysql:' . join(';', array($db_name, $db_host, $db_port));
            } else {
                //判断选择数据库类型
                if ($db_idx !== null) {
                    //mysql
                    if (!isset($this->conf['db_mysql_group'])) {
                        trace('mysql配置不存在!', 'w');
                        $this->myLogs('mysql多库配置不存在!', 'f', 'dbError', array('file' => $_SERVER['PHP_SELF']));
                        exit;
                    }
                    $dbConf = $this->conf['db_mysql_group'];
                    $db_idx = intval($db_idx) < 10 ? '0' . intval($db_idx) : $db_idx;
                    $db_name = 'dbname=' . $dbConf['DB_PREFIX'] . $dbconnect . '_' . $db_idx;
                    if (is_array($dbConf['DB_HOST'])) {
                        $db_host = 'host=' . $dbConf['DB_HOST'][floor(intval($db_idx) / 8)];
                    } else {
                        $db_host = 'host=' . $dbConf['DB_HOST'];
                    }
                    $db_port = 'port=' . $dbConf['DB_PORT'];
                    $DSN = 'mysql:' . join(';', array($db_name, $db_host, $db_port));
                } else {
                    //mycat
                    if (!isset($this->conf['db_mycat'])) {
                        trace('mycat配置不存在!', 'w');
                        $this->myLogs('mycat配置不存在!', 'f', 'dbError', array('file' => $_SERVER['PHP_SELF']));
                        exit;
                    }
                    $dbConf = $this->conf['db_mycat'];
                    $db_name = 'dbname=' . $dbConf['DB_PREFIX'] . $dbconnect;
                    $db_host = 'host=' . $dbConf['DB_HOST'];
                    $db_port = 'port=' . $dbConf['DB_PORT'];
                    $DSN = 'mysql:' . join(';', array($db_name, $db_host, $db_port));
                }
            }
        } else {
            trace('没有' . $dbconnect . '库的连接信息');
            exit;
        }
        try {
            $mycat_db = new \DbPdo($DSN, $dbConf['DB_USER'], $dbConf['DB_PWD'], '');
            $mycat_db->query('set names utf8');
            return $mycat_db;
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            echo 'Connection failed: ' . $msg;
            $this->myLogs('数据库连接失败!', 'f', 'dbError', array('file' => $_SERVER['PHP_SELF'], 'error' => iconv("GBK", "UTF-8", $msg)));
            exit;
        }
    }

    // 推送至抓取数据队列#陈志军修改后版本
    public function sendDataToQueue($name, $data, $flags = '') {
        //初始化队列推送类
        $send = new Queue($this->conf['MQ_CONN_ARGS']);
        // 设置队列名称
        $send->setSendOption($name, $flags);
        // 推送数据
        $data = json_encode($data);
        $ret = $send->send($data);
        if ($ret) {
            return true;
        } else {
            return false;
        }
    }

}
