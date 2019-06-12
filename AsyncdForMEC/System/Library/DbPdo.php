<?php
/**
 * Class db_Pdo
 */
if(!defined('PATH')){
    define('PATH', dirname(dirname(dirname(__FILE__))));
}
if(!defined('SPATH')){
    define('SPATH', '/System');
}
include_once(PATH . SPATH . '/Library/Db.php');
class DbPdo extends Db{

    /**
     * @var PDO
     */
    public $prefix;
    protected $_changes = 0;

    /**
     * @var PDOStatement
     */
    protected $_statement;

    public function __construct($dsn,$user,$pwd,$prefix='m_',$options = array()){
        if(!empty($options)){
            $this->_conn = new PDO($dsn,$user,$pwd,$options);
        }else{
            $this->_conn = new PDO($dsn,$user,$pwd);
        }
        $this->prefix = $prefix;
    }

    /**
     * (non-PHPdoc)
     * @see Db::_query()
     */
    protected function _query($sql){
        $this->_statement = $this->_conn->query($sql);
        return $this->_statement;
    }

    /**
     * (non-PHPdoc)
     * @see Db::changes()
     */
    public function changes(){
        if ($this->_statement){
            return $this->_statement->rowCount();
        }
        return 0;
    }

    /**
     * (non-PHPdoc)
     * @see Db::execute()
     */
    public function execute($sql=''){
        $result = $this->_conn->exec($sql);
        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see Db::getAll()
     */
    public function getAll($sql){
        return $this->_callQuery($sql, 'fetchAll', array(PDO::FETCH_ASSOC));
    }

    /**
     * (non-PHPdoc)
     * @see Db::getLastErrorCode()
     */
    public function getLastErrorCode(){
        return $this->_conn->errorCode();
    }

    /**
     * (non-PHPdoc)
     * @see Db::getLastErrorString()
     */
    public function getLastErrorString($error_code = 0){
        return $this->_conn->errorInfo();
    }

    /**
     * (non-PHPdoc)
     * @see Db::getRow()
     */
    public function getRow($sql){
        return $this->_callQuery($sql, 'fetch', array(PDO::FETCH_ASSOC));
    }

    /**
     * (non-PHPdoc)
     * @see Db::getScalar()
     */
    public function getScalar($sql){
        return $this->_callQuery($sql, 'fetchColumn', array(0));
    }

    protected function _callQuery($sql, $fun, $params = array()){
        $sth = $this->query($sql);
        $ret = null;
        if ($sth) {
            $result = call_user_func_array(array($sth, $fun), $params);
            if ($result){
                $ret = $result;
            }
        }
        return $ret;
    }

    /**
     * (non-PHPdoc)
     * @see Db::lastInsertID()
     */
    public function lastInsertID(){
        return $this->_conn->lastInsertId();
    }

    public function close(){
        unset($this->_conn);
    }

}