<?php

class Core_Base
{
	const PID_FILE = 'var/asyncd.pid';
	const LOG_PATH = 'log';
	const INI_FILE = 'var/asyncd.ini';

	const MSG = 'msg';
	const ERR = 'err';
	const EXCE = 'exce';//exception
	//public $pidFile = 'var/asyncd.pid';
	//public $logPath = 'var/log';

	public  static  function getConfigParams(){
		// 加载配置文件信息
		require_once ROOT_PATH . DS . self::CONF_FILE;
		return $config;
	}
	/**
	 * msgLevel: err | log
	 */
	public static function log($msg, $level = self::MSG)
	{
		$path = ROOT_PATH . DS . self::LOG_PATH . DS . date("Ym");
		if (!file_exists($path)) {
			mkdir($path, 0755, true);
		}
		$logFile = $path . DS . date('Ymd') . '.' . $level;
		$msg = date("Y-m-d H:i:s") . "\t" . $msg . PHP_EOL;
		file_put_contents($logFile, $msg, FILE_APPEND);
	}

	public static function logEx($module, $msg, $level = self::MSG)
	{
		$path = ROOT_PATH . DS . self::LOG_PATH . DS . date("Ym") . DS . $module;
		if (!file_exists($path)) {
			mkdir($path, 0755, true);
		}
		$logFile = $path . DS . date('Ymd') . '.' . $level;
		$msg = date("Y-m-d H:i:s") . "\t" . $msg . PHP_EOL;
		file_put_contents($logFile, $msg, FILE_APPEND);
	}

    public static function writeLog($type, $content)
    {
        $path = ROOT_PATH . DS . self::LOG_PATH . DS . date("Ym");
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $logFile = $path . DS . $type. '_' . date('d') . '.log';
        $msg = date("Y-m-d H:i:s") . "\t" . $content . PHP_EOL;
        file_put_contents($logFile, $msg, FILE_APPEND);
    }

	public static function out($msg)
	{
		fwrite(STDOUT, $msg . PHP_EOL);
		fflush(STDOUT);
	}
	
	/**
	 * 推送数据到队列
	 * @param string $eName 交换机
	 * @param string $kRoute 路由
	 */
	public static function sendToQueue( $name, $data ){
		require_once 'Module/Common/Queue.php';
		
		$queue = new Module_Common_Queue();
		$queue->setSendOption($name);
		// 推送数据
		return $queue->send( $data );
	}
	
	/**
	 * 推送数据到队列
	 * @param string $eName 交换机
	 * @param string $kRoute 路由
	 */
	public static function sendIdToQueue( $_eName, $_kRoute, $data ){
		require_once 'Module/Common/Erpqueue.php';
	
		$queue = new Module_Common_Erpqueue($_eName, $_kRoute);
		return  $queue->sendIdToQueue($data);
	}
	
}