<?php
/**
 * 数据库DB类
 */
abstract class Db{

    protected $_conn;
    protected static $_instance = array();
    protected $_table;
    protected $_where;
	protected $_join = '';
	protected $_error = '';
    protected $_sqlstr;
    private $sql = array(
        "field" => "*",
        "order" => "",
        "limit" => "",
        "group" => "",
        "having" => "",
    );

    public function getError(){
        return $this->_error;
    }

    public function sqlError($error = ''){
		if(!empty($error)){
			$this->_error = $error;
		}
        if (defined('DEBUG') && DEBUG){
			if($this->getLastErrorCode()!='00000' || !empty($this->_error)){
				$errorInfo = $this->getLastErrorString();
				if($this->getLastErrorCode()!='00000'){
					$this->_error = $errorInfo[2];
				}
				trace('ERROR:' . $this->_error,'e');
				trace('SQL:' . $this->_sqlstr,'e');
				$this->dbLogs('Mysql Error: ' , 'e' , array('error'=>$this->_error,'sql'=>$this->_sqlstr));
                return true;
			}
        }
        return false;
    }

    public function getLastSql(){
        return $this->_sqlstr;
    }

    public function debug($sql){
		return true;
    }

	//mysql数据库错误
	public function dbLogs($msg = '' , $type = 'e' , $data = '')
    {
		$this->logs  = new MyLogs('dbError');
        $this->logs->doLog($msg, $data, $type);
    }


    /**
     * 实现连贯操作
     * @param $methodName
     * @param $args
     * @return $this
     */
    function __call($methodName,$args){
        // 将第一个参数(代表不存在方法的方法名称),全部转成小写方式，获取方法名称
        $methodName = strtolower($methodName);
        // 如果调用的方法名和成员属性数组$sql下标对应上，则将第二个参数给数组中下标对应的元素
        if(array_key_exists($methodName,$this->sql)){
            $array = array('ORDER BY'=>'order','LIMIT'=>'limit','GROUP BY'=>'group','HAVING'=>'having');
            foreach($array as $key=>$val){
                if($methodName == $val){
					//var_dump($args);exit;
                    if (strpos($args[0],$methodName)){
                        $args[0] =  ' ' . $args[0];
                    }else {
                        $args[0] =  ' '.$key.' ' . $args[0];
                    }
                }
            }
            $this->sql[$methodName] = $args[0];
        }else{
            echo '调用类'.get_class($this).'中的方法'.$methodName.'()不存在';
        }
        // 返回自己对象，则可以继续调用本对象中的方法，形成连贯操作
        return $this;
    }

    public function getConn(){
        return $this->_conn;
    }

    /**
     * 获取一个数据列表
     * @param string $sql
     * @return array
     */
    abstract public function getAll($sql);

    /**
     * 获取一行数据
     * @param string $sql
     * @return array
     */
    abstract public function getRow($sql);

    /**
     * 获取下一行第一列单个结果
     * @param string $sql
     * @return int|string
     */
    abstract public function getScalar($sql);

    /**
     * 获取上一次插入的ID
     * @return int
     */
    abstract public function lastInsertID();

    /**
     * 受影响行数
     */
    abstract public function changes();

    /**
     * 获取错误代号
     * @param unknown_type $error_code
     */
    abstract public function getLastErrorCode();

    /**
     * 获取错误信息
     */
    abstract public function getLastErrorString($error_code = 0);

    /**
     * @param unknown_type $sql
     */
    abstract protected function _query($sql);

    abstract public function execute($sql);

    public function query($sql){
        $type = strtolower(substr($sql, 0, 6));
        $this->_sqlstr = $sql;
        $result = $this->_query($sql);
		$this->clear();
		$isErr = $this->sqlError();
		return $isErr?false:$result;
    }

    public function table($table){
        $this->_table = $table;
        return $this;
    }

    /**
     * 插入一条数据
     * @param array $data
     * @param bool $replace
     * @return bool|int
     */
    public function add(array $data, $replace = false){
        if (empty($data)){
            return false;
        }
		 $keys = implode('`,`', array_keys($data));
            foreach ($data as $value){
				if(is_array($value)){
					if(!is_bool($value[1])){
						$this->sqlError('插入参数格式错误!');
						return false;
					}
					$reValue = (isset($value[1]) && $value[1]===true)?"{$value[0]}":"'{$value[0]}'";
					$sql[] = "{$reValue}";
				}else{
					$sql[] = "'$value'";
				}
            }
           $values = implode(',', $sql);
        $op = $replace ? 'REPLACE' : 'INSERT';
        $sql = $op . ' INTO ' . $this->_table . ' (`'.$keys.'`) VALUES (' . $values . ');';
		$this->_sqlstr = $sql;
        $result = $this->execute($sql);
		$this->clear();
		$isErr = $this->sqlError();
		return $isErr?false:$result;
    }

    /**
     * 插入多条数据
     * @param array $datas
     * @param bool $replace
     * @return bool|int
     */
    public function addAll(array $datas, $replace = false){
        if (empty($datas)){
			return !$this->sqlError('添加数据不能为空!');
        }
        $keys = implode('\',\'', array_keys(current($datas)));
        $sqls = array();
        $op = $replace ? 'REPLACE' : 'INSERT';
        foreach ($datas as $data){
            $sqls[] = "('".implode('\',\'', $data)."')";
        }
        $sqls = $op . ' INTO ' . $this->_table .' ('.$keys.') VALUES ' . implode(',', $sqls).';';
		$this->_sqlstr = $sqls;
        $result = $this->execute($sqls);
		$this->clear();
		$isErr = $this->sqlError();
		return $isErr?false:$result;
    }

    /**
     * 更新一条数据
     * @param array $data
     * @return bool|int
     */
    public function save($data){
		$result = false;
        if (empty($data)){
            return !$this->sqlError('保存数据不能为空!');
        }
        $sql = array();
        if(is_array($data)){
            foreach ($data as $key => $value){
				if(is_array($value)){
					if(!is_bool($value[1])){
						return !$this->sqlError('保存参数格式错误!');
					}
					$reValue = (isset($value[1]) && $value[1]===true)?"{$value[0]}":"'{$value[0]}'";
					$sql[] = "$key = {$reValue}";
				}else{
					$sql[] = "$key = '$value'";
				}
            }
            $set = implode(', ', $sql);
        }else{
            $set = $data;
        }
        $sql = "UPDATE " .$this->_table." SET " . $set . $this->_where.';';
		//更新操作必须有条件
		$this->_sqlstr = $sql;
		if(!empty($this->_where)){
			$result = $this->execute($sql);
		}else{
			$this->_error = '更新时条件不能为空!';
		}
		$this->clear();
		$isErr = $this->sqlError();
		return $isErr?false:$result;
    }

    /**
     * 删除一条数据
     * @return bool|int
     */
    public function delete(){
		$result = false;
        $sql = "DELETE FROM ".$this->_table. $this->_where.';';
		//更新操作必须有条件
		if(!empty($this->_where)){
			$result = $this->execute($sql);
		}else{
			$this->_error = '删除时条件不能为空!';
		}
		$this->_sqlstr = $sql;
		$this->clear();
		$isErr = $this->sqlError();
		return $isErr?false:$result;
    }

    /**
     * 获取数据
     * @param int $d
     * @return array|int|string
     */
    public function select(){
        $sql = "SELECT " . $this->sql['field'] . " FROM " . $this->_table . $this->_join . $this->_where . $this->sql['order'] . $this->sql['group'].$this->sql['having'] . $this->sql['limit'];
		$result = $this->getAll($sql);
        $result === false && $this->debug($sql);
        $this->sql['field'] = '*';
		$this->clear();
		$isErr = $this->sqlError();
		return $isErr?false:$result;
    }

	

	/**
     * 初始化变量
     */
	private function clear(){
		$this->_join = '';
		$this->_where = '';
		$this->_sqlstr = '';
		$this->sql = array(
					"field" => "*",
					"order" => "",
					"limit" => "",
					"group" => "",
					"having" => ""
				);
	}



    /**
     * 获取数据
     * @return array|int|string
     */
    public function find($m=0){
		if($m===1){
			$this->sql['field'] = 'count(*) as count';
		}
        $sql = "SELECT " . $this->sql['field'] . " FROM " . $this->_table . $this->_join .  $this->_where.$this->sql['order'] . $this->sql['group'] . $this->sql['having'] . $this->sql['limit'];
		$result = $this->getRow($sql);
        $result === false && $this->debug($sql);
        $this->sql['field'] = '*';
		$this->clear();
		$isErr = $this->sqlError();
		if($isErr){
			return false;
		}
        return $m===1?current($result):$result;
    }
	

	/**
     * 获取数据
     * @return array|int|string
     */
	public function join($join,$type='left') {
			if(strstr($join,'join')!==false)
			{
				$sql = " {$type} {$join}";
			}else{
				$sql = " {$type} join {$join}";
			}
			$this->_join.= $sql;
            return $this;
	}

    /**
     * where条件处理
     * @param null $where
     * @return array|string
     */
    public function where($where = null){
        if (empty($where)){
            $this->_where = '';
            return $this;
        }
        if (is_string($where)){
            $where = trim($where);
            if (strtolower(substr($where, 0, 5)) == 'where'){
                $this->_where = ' ' . $where;
                return $this;
            }else {
                $this->_where = ' WHERE ' . $where;
                return $this;
            }
        }elseif (is_array($where)){
            $sql = $this->forWhere($where);
            $sql = ' WHERE ' . $sql;
            $this->_where = $sql;
            return $this;
        }
    }

	//处理where数组
	//$where['[wx_open_id]'] = array(array('!=','',false,'or'),array('in',$openidList));		//使用方法
	private function forWhere($where = array()){
		$criteria_3 = '';
		$sql = array();
		foreach ($where as $key => $criteria){
				if(!is_string($key)){
					continue;
				}
				if($key==='[sql]'){
					if(!empty($sql)){
						$criteria_3 = (is_string(end($criteria)))?end($criteria).' ':'and ';
					}
					$_sql = $this->forWhere($criteria);
					if(!empty($_sql)){
						$sql[] = "{$criteria_3}(". $_sql.")";
					}
				}else{
					$criteria_3 = isset($criteria[3])?$criteria[3]:'and';
					if(is_array($criteria)){
							//判断多维数组
							if(is_array(current($criteria))){
								//判断key是否带有();
								$isBracket = false;
								if(strstr($key,'[') !== false && strstr($key,']') !== false){
									$key = str_replace(']','',str_replace('[','',$key));
									$isBracket = true;
								}
										//print_r($criteria);exit;
								foreach($criteria as $k=>$cval){
										$criteria_befor = ($k==0 && count($criteria)>1 && $isBracket)?'(':'';
										$criteria_after = ($k==count($criteria)-1 && count($criteria)>1 && $isBracket)?')':'';
										if (isset($cval[1]) && is_array($cval[1])){
											if(empty($sql)){
												$sql[] = "{$criteria_befor}{$key} {$cval[0]} ('".implode('\',\'', $cval[1])."'){$criteria_after}";
											}else{
												$sql[] = "{$criteria_3} {$criteria_befor}{$key} {$cval[0]} ('".implode('\',\'', $cval[1])."'){$criteria_after}";
											}
										}else {
											//是否添加标点符号
											$criteria_1 = (isset($cval[2]) && $cval[2]===true)?"{$cval[1]}":"'{$cval[1]}'";
											if(empty($sql)){
												$sql[] = "{$criteria_befor}{$key} {$cval[0]} {$criteria_1}{$criteria_after}";
											}else{
												$sql[] = "{$criteria_3} {$criteria_befor}{$key} {$cval[0]} {$criteria_1}{$criteria_after}";
											}
										}
											$criteria_3 = isset($cval[3])?$cval[3]:'and';
								}// foreach end
							}else{ //一维数组
									if(is_array($criteria[1])){
										if(empty($sql)){
											$sql[] = "$key {$criteria[0]} ('".implode('\',\'', $criteria[1])."')";
										}else{
											$sql[] = "{$criteria_3} $key {$criteria[0]} ('".implode('\',\'', $criteria[1])."')";
										}
									}else {
										//是否添加标点符号
										$criteria_1 = (isset($criteria[2]) && $criteria[2]===true)?"{$criteria[1]}":"'{$criteria[1]}'";
										if(empty($sql)){
											$sql[] = "$key {$criteria[0]} {$criteria_1}";
										}else{
											$sql[] = "{$criteria_3} $key {$criteria[0]} {$criteria_1}";
										}
									}
							}
					}else { //字符串
						if(empty($sql)){
							$sql[] = "$key = '$criteria' ";
						}else{
							$sql[] = "AND $key = '$criteria' ";
						}
                    }
                }
            }
			return implode(' ', $sql);
	}


    /**
     * 启动事务
     * @access public
     * @return void
     */
	public function startTrans($fal=true) {
        if($fal === false){
            return false;
        }
        $this->_conn->beginTransaction();
    }

    public function commit($fal=true){
        if($fal === false){
            return false;
        }
        $this->_conn->commit();
    }

    public function rollback($fal=true){
        if($fal === false){
            return false;
        }
        $this->_conn->rollback();
    }
}