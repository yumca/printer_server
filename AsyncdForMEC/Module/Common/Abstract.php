<?php

abstract class Module_Common_Abstract
{
	protected $_conf = array();
	public function __construct($param, $config)
	{
		$this->_conf = $config;
		$this->_beforeInit($param);
		if($this->_conf['static']) {
			$cnt = 0;
			while(1) {
				
				$this->_init($param);
				if ($this->_conf['sleep'] > 0) {
					usleep($this->_conf['sleep'] * 1000000);
				}
				if ($this->_conf['static'] > 1) {
					$cnt ++;
					if ($this->_conf['static'] <= $cnt) {
						break;	
					}
				}
			}
		} else {
			$this->_init($param);
			if ($this->_conf['sleep'] > 0) {
				usleep($this->_conf['sleep'] * 1000000);
			}
		}
	}

	protected function _beforeInit($param = null)
	{
			
	}

	protected function _init($param)
	{
	
	}
	
	protected function _debug($msg)
	{
		if ($this->_conf['debug']) {
			echo date("Y-m-d H:i:s") . "\t" . $msg . PHP_EOL;
		}
	}
}
