<?php
class Core_Daemon extends Core_Base
{
	protected static $_instance = null;

	private $_pids = array();

	private $_config = array();

	public static function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self();	
		}
		return self::$_instance;
	}

	public function init()
	{
		self::_checkPidFile();
		$pid = self::_createDaemon();	
		self::_savePidFile($pid);		
		declare(ticks = 1);
		$this->initSignal();
		$this->_getConfig();
		$this->_createWorkers();
	}

	private static function _checkPidFile()
	{
		$pidFile = ROOT_PATH . DS . parent::PID_FILE;
		if (file_exists($pidFile)) {
			$pid = trim(file_get_contents($pidFile));
			if (posix_kill($pid, 0)) {
				throw new Core_Exception('Server alraady running with PID ' . $pid);
			}
			if (!unlink($pidFile)) {
				throw new Core_Exception('Cannot unlink PID file ' . $pid);	
			}
		}
	}

	private static  function _createDaemon()
	{
		$pid = pcntl_fork();
		if ($pid == -1) {
			throw new Core_Exception('Failed to fork');	
		}

		// kill parent
		if ($pid) {
			exit(0);		
		}
		posix_setsid();	
		chdir('/');
		umask(0);
		$daemonPid =  posix_getpid();
		$str = 'Start daemon process with pid ' . $daemonPid;
		Core_Base::out($str);
		Core_Base::log($str, Core_Base::MSG);
		return $daemonPid;
	}

	private static function _savePidFile($pid)
	{
		$pidFile = ROOT_PATH . DS . parent::PID_FILE;
		file_put_contents($pidFile, $pid);	
	}

	public function initSignal()
	{
		pcntl_signal(SIGHUP, array(&$this, 'sigHandler'), false);
		pcntl_signal(SIGINT, array(&$this, 'sigHandler'), false);
		pcntl_signal(SIGTERM, array(&$this, 'sigHandler'), false);
		pcntl_signal(SIGUSR1, array(&$this, 'sigHandler'), false);
	}

	public function sigHandler($signo)
	{
		switch ($signo) {
			case SIGTERM:
			case SIGINT:
			case SIGHUP:
			case SIGQUIT:
				foreach ($this->_pids as $pid) {
					posix_kill($pid, $signo);	
				}
				foreach($this->_pids as $pid) {
					$status = null;
					pcntl_waitpid($pid, $status);
				}
				$str = 'Asyncd pid ' . getmypid() . ' exiting.';
				Core_Base::out($str);
				Core_Base::log($str, Core_Base::MSG);
				exit();
			case SIGUSR1:
				$str = getmypid() . ' Total children : ' . sizeof($this->_pids);
				Core_Base::out($str);
				Core_Base::log($str, Core_Base::MSG);
				break;
		}
	}

	private function _createWorkers()
	{
		while (1) {
			$this->_runWorker();	
			$this->_waitWorker();
			// 1000000 micro seconds = 1seconds
			usleep(5000);
		}
	}

	private function _getConfig()
	{
		require_once 'Core/Ini.php';
		$this->_config = Core_Ini::parse(ROOT_PATH . DS . Core_Base::INI_FILE);	
		if(!$this->_config) {
			$str = 'Can not find the child module in the asyncd.ini file';
			Core_Base::log($str, Core_Base::MSG);	
		}
	}

	private function _runWorker()
	{
		foreach($this->_config as $module => $subModules) {
			foreach($subModules as $subModule => $config) {
				$class = 'Module_' . $module . "_" . $subModule;
				$file = str_replace('_', '/', $class) . '.php';
				if(array_key_exists('total_threads', $config)) {
					$maxThread = $config['total_threads'];
				} else {
					$maxThread = $config['total_task'] / $config['task_per_thread'];
				}

				if(!isset($config['enabled']) || !$config['enabled']) {
					continue;
				}
				for($i = 0; $i < $maxThread; $i ++) {
					$key = $class . "_" . $i;
					if(array_key_exists($key, $this->_pids)) {
						continue;		
					}
					$pid = pcntl_fork();
					if(!$pid) {
						require_once $file;
						$str = 'Start class ' . $class . ' in child process ' . getmypid();
						//Core_Base::out($str);
						//Core_Base::log($str, Core_Base::MSG);
						new $class($i, $config);
						exit();
					} else {
						$this->_pids[$key] = $pid;	
					}
				}
			}
		}
	}

	private function _waitWorker()
	{
		$deadPid = pcntl_waitpid(-1, $status, WNOHANG);
		while ($deadPid > 0) {
			// Remove the dead pid from the array
			unset($this->_pids[array_search($deadPid, $this->_pids)]);

			// Look for another one
			$deadPid = pcntl_waitpid(-1, $status, WNOHANG);
		}
	}
}
