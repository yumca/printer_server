<?php
/**
 * @author CPR006
 */
 //恒信开发环境
class Module_Common_Queue
{
	private $_objConn = null;
	private $_objChannel = null;
	private $_objExchange = null;
	private $_objQueue = null;
	private $_name = '';
	
	const HXMQ_NO_EXCHANGE = 404;
	const HXMQ_NO_QUEUE = 401;
	const HXMQ_NO_CHANNEL = 402;
	protected $_connArgs = array(
			'host' => '10.0.15.207', //测试环境
			'port' => '5672',
			'login' => 'hx',
			'password' => 'hx',
			'vhost'=>'/'
	);
	
	public function __construct()
	{
		$this->_objConn = new \AMQPConnection( $this->_connArgs );
		if (!$this->_objConn->connect()) {
			throw new \Exception("Failed to connect message queue server: " . json_encode($this->_connArgs, JSON_UNESCAPED_UNICODE));
		}
		$this->_objChannel = new \AMQPChannel($this->_objConn);
		$this->_objExchange = new \AMQPExchange($this->_objChannel);
		$this->_objQueue = new \AMQPQueue($this->_objChannel);
	}

	public function setSendOption($name,$flags='')
	{
		$this->_name = $name;
		$this->setExchangeName('e_' . $this->_name);
		$this->setExchangeType();
		$this->setExchangeFlags($flags);

		$this->setQueueName('q_' . $this->_name);
		$this->setQueueFlags($flags);

		$this->_objExchange->declare();
		$this->_objQueue->declare();
		$this->_objQueue->bind('e_' . $this->_name, 'r_' . $this->_name);
	}

	public function setConsumeOption($name, $flags = '')
	{
		$this->_name = $name;
		
		
		$this->_objQueue->setName('q_' . $this->_name);
		if ($flags == '' ) {
			$flags = AMQP_DURABLE | AMQP_AUTODELETE;
		}
		$this->_objQueue->setFlags($flags);
		$this->_objQueue->declareQueue();
		
		$this->setExchangeName('e_' . $this->_name);
		$this->setExchangeType();
		$this->setExchangeFlags($flags);
		
		$this->_objExchange->declareExchange();
		try {
			$this->_objQueue->bind('e_' . $this->_name, 'r_' . $this->_name);
		} catch ( AMQPQueueException $e) {
			throw new Exception($e->getMessage(), self::HXMQ_NO_EXCHANGE);
		}
		//$this->_objQueue->bind('e_' . $this->_name, 'r_' . $this->_name);
	}

	public function setExchangeName($exchangeName)
	{
		$this->_objExchange->setName($exchangeName);
	}

	public function setExchangeType($type = AMQP_EX_TYPE_DIRECT)
	{
		$this->_objExchange->setType($type);
	}

	public function setExchangeFlags($flags = '')
	{
		if ($flags == '') {
			$flags = AMQP_DURABLE;
		}
		$this->_objExchange->setFlags($flags);
	}

	public function setQueueName($queueName)
	{
		$this->_objQueue->setName($queueName);
	}

	public function setQueueFlags($flags = '')
	{
		if ($flags == '') {
			$flags = AMQP_DURABLE | AMQP_AUTODELETE;
		}
		$this->_objQueue->setFlags($flags);
	}

	public function beginTrans()
	{
		$this->_objChannel->startTransaction();
	}

	public function commit()
	{
		$this->_objChannel->commitTransaction();
	}

	public function send($message, $mandatory = AMQP_MANDATORY)
	{
		return $this->_objExchange->publish($message, 'r_' . $this->_name, $mandatory, array('delivery_mode' => 2));
	}

	public function get($callback)
	{
		$this->_objQueue->get($callback);
	}

	public function consume($callback)
	{
		try {
			$this->_objQueue->consume($callback);
		} catch( \AMQPChannelException $e) {
			throw new Exception($e->getMessage(), self::HXMQ_NO_CHANNEL);
		}
		//$this->_objQueue->consume($callback);
	}

	public function disconnect()
	{
		$this->_objConn->disconnect();
	}

	public function getQueue()
	{
		return $this->_objQueue;
	}

	public function process($envelope, $queue)
	{
		$msg = $envelope->getBody();
		//echo $msg."\n"; //处理消息
		$queue->ack($envelope->getDeliveryTag()); //手动发送ACK应答
	}
}
	/* 
	
//	$connArgs = array( 'host'=>'10.0.12.225' , 'port'=> '5672', 'login'=>'hx' ,'password'=> 'hx','vhost' =>'/');
	$objMQ = new MyMQ($connArgs);
	
	$objMQ->setSendOption('wqtest');
	$ret = $objMQ->send('This is a test');
	var_dump($ret);
	
	$objMQ->setConsumeOption('wqtest');
	//$objMQ->consume('HxMQ::process');
	$objMQ->consume(array($objMQ, 'process'));
 */
