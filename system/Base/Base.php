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
if (!defined('PATH')) {
    define('PATH', dirname(dirname(dirname(__FILE__))));
}
include_once(PATH . '/Common/function.php');
include_once(PATH . '/Library/RedisCache.php');
include_once(PATH . '/Library/MyLogs.php');
include_once(PATH . '/Library/DbPdo.php');

class Base
{
    public $logs;
    public $conf;

    public function __construct()
    {
        //读取配置文件
        $configPath = PATH . '/Conf/config.php';
        if (file_exists($configPath)) {
            $_conf = include_once $configPath;
            if (!defined('ENVIRONMENT')) {
                define('ENVIRONMENT', 'dev');
            }
            $this->conf = isset($_conf['dev']) ? $_conf[ENVIRONMENT] : $_conf;
        } else {
            echo '配置文件不存在!';
            exit;
        }
    }

    protected function _return($code = '500', $data = null)
    {
        header('Content-Type: application/json; charset=utf-8');
        $data = array('code' => $code, 'message' => $data, 'timestamp' => time());
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function _redirect($url)
    {
        header("Location:" . $url);
        exit;
    }

    public function myLogs($msg = '', $type = '', $mode = 'demo', $data = '')
    {
        if (!($this->logs instanceof MyLogs)) {
            $this->logs = new MyLogs();
        }
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $this->logs->doLog($mode, $msg, $data, $type, current($trace));
    }

    //链接数据库
    //mall_main,mall_thirdpart,userinfo,mall_coupon
    protected function connect_mysql()
    {
        //单库
        if (!isset($this->conf['db'])) {
            trace('mysql配置不存在!', 'w');
            $this->myLogs('mysql配置不存在!', 'f', 'dbError', array('file' => $_SERVER['PHP_SELF']));
            exit;
        }
        $dbConf = $this->conf['db'];
        $db_name = 'dbname=' . $dbConf['DB_PREFIX'] . $dbConf['DB_NAME'];
        $db_host = 'host=' . $dbConf['DB_HOST'];
        $db_port = 'port=' . $dbConf['DB_PORT'];
        $DSN = 'mysql:' . join(';', array($db_name, $db_host, $db_port));
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
}
