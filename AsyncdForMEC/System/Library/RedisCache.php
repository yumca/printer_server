<?php

class RedisCache {

    private $redis;
    private $prefix = '';

    /**
     *
     * @param array $config
     */
    public function __construct($config) {
        if (empty($config['REDIS_SERVER']) || empty($config['REDIS_POST']) || empty($config['REDIS_KEY_PREFIX'])) {
            echo "请配置Redis服务器信息";
        }
        $this->redis = new \Redis();
        $this->prefix = $config['REDIS_KEY_PREFIX'];
        $this->redis->connect($config['REDIS_SERVER'], $config['REDIS_POST']);
        if (!empty($config['pwd'])) {
            $this->redis->auth($config['pwd']);
        }
        return $this->redis;
    }

    /**
     * 设置值
     * @param string $key KEY名称
     * @param string|array $value 获取得到的数据
     * @param int $timeOut 时间
     */
    public function setstr($key, $value, $timeOut = 0) {
        $value = json_encode($value);
        $retRes = $this->redis->set($this->prefix . $key, $value);
        if ($timeOut > 0)
            $this->redis->setTimeout($this->prefix . $key, $timeOut);
        return $retRes;
    }

    /**
     * 通过KEY获取数据
     * @param string $key KEY名称
     */
    public function getstr($key) {
        $result = $this->redis->get($this->prefix . $key);
        return json_decode($result, TRUE);
    }

    /**
     * 删除一条数据
     * @param string $key KEY名称
     */
    public function deletestr($key) {
        return $this->redis->delete($this->prefix . $key);
    }

    /**
     * 清空整个数据
     */
    public function flushAll() {
        return $this->redis->flushAll();
    }

    /**
     * 获得库中总数
     * @privilege Backend:
     * @return int
     */
    public function dbsize() {
        return $this->redis->dbSize();
    }

    /**
     * 清空当前数据库
     */
    public function flushdb() {
        return $this->redis->flushdb();
    }

    /**
     * 数据入队列
     * @param string $key KEY名称
     * @param string|array $value 获取得到的数据
     * @param bool $right 是否从右边开始入
     */
    public function push($key, $value, $right = true) {
        $value = json_encode($value);
        return $right ? $this->redis->rPush($this->prefix . $key, $value) : $this->redis->lPush($this->prefix . $key, $value);
    }

    /**
     * 数据出队列
     * @param string $key KEY名称
     * @param bool $left 是否从左边开始出数据
     */
    public function pop($key, $left = true) {
        $val = $left ? $this->redis->lPop($this->prefix . $key) : $this->redis->rPop($this->prefix . $key);
        return json_decode($val);
    }

    /**
     * 数据自增
     * @param string $key KEY名称
     */
    public function increment($key) {
        return $this->redis->incr($this->prefix . $key);
    }

    /**
     * 数据自减
     * @param string $key KEY名称
     */
    public function decrement($key) {
        return $this->redis->decr($this->prefix . $key);
    }

    /**
     * key是否存在，存在返回ture
     * @param string $key KEY名称
     */
    public function exists($key) {
        return $this->redis->exists($this->prefix . $key);
    }

    /**
     * 返回redis对象
     * redis有非常多的操作方法，我们只封装了一部分
     * 拿着这个对象就可以直接调用redis自身方法
     */
    public function redis() {
        return $this->redis;
    }

    /**
     * 修改string数据库某个KEY的值
     * @param string $key KEY名称
     * @param string $value 值
     */
    public function editval($key, $value) {
        $value = json_encode($value);
        $retRes = $this->redis->getset($this->prefix . $key, $value);
        return $retRes;
    }

    /**
     * 修改lisg类型数据库某个list中KEY的值
     * @param string $key KEY名称
     * @param string $index index名称
     * @param string $value 值
     */
    public function editlistval($key, $index, $value) {
        $value = json_encode($value);
        $retRes = $this->redis->lset($this->prefix . $key, $index, $value);
        return $retRes;
    }

    /**
     * 添加lisg类型数据库某个list中KEY的值
     * @param string $key KEY名称
     * @param string $index index名称
     * @param string $value 值
     */
    public function addlistval($key, $value) {
        $value = json_encode($value);
        $retRes = $this->redis->lpush($this->prefix . $key, $value);
        return $retRes;
    }

    /**
     * 修改lisg类型数据库某个list中KEY的值
     * @param string $key KEY名称
     * @param string $index index名称
     * @param string $value 值
     */
    public function dellistval($key, $value) {
        $value = json_encode($value);
        $retRes = $this->redis->lrem($this->prefix . $key, 0, $value);
        return $retRes;
    }

    /**
     * 获取数据库某个list中所有的值
     * @param string $key KEY名称
     */
    public function getlistval($key) {
        //获取元素长度
        //  $len_list =  $this->Redis->llen($key);
        $retRes = $this->redis->lrange($this->prefix . $key, 0, -1);
        return $retRes;
    }

    /**
     * 针对set数据
     * 添加数据方法
     * @param string $key KEY名称
     * @param string $value 值
     */
    public function addarrayset($key, $value) {
        $value = json_encode($value);
        $retRes = $this->redis->sadd($this->prefix . $key, $value);
        return $retRes;
    }

    /**
     * 针对set数据
     * 删除数据方法
     * @param string $key KEY名称
     * @param string $value 值
     */
    public function delarrayset($key, $value) {
        $value = json_encode($value);
        $retRes = $this->redis->srem($this->prefix . $key, $value);
        return $retRes;
    }

    /**
     * 针对set数据
     * 检查数据方法
     * @param string $key KEY名称
     * @param string $value 值
     */
    public function checkarrayset($key, $value) {
        $value = json_encode($value);
        $retRes = $this->redis->sismember($this->prefix . $key, $value);
        return $retRes;
    }

    /**
     * 针对set数据
     * 获取数据方法
     * @param string $key KEY名称
     */
    public function getarrayset($key) {
        $retRes = $this->redis->smembers($this->prefix . $key);
        return $retRes;
    }

    /**
     * 返回所有KEY
     * @param string $key KEY名称
     */
    public function getHashkeys($key) {
        $result = $this->redis->hkeys($this->prefix . $key);
        return json_decode($result, TRUE);
    }

    /**
     * 返回所有KEY
     * @param string $pattern   通配符
     * @privilege Backend:
     * @return array
     */
    public function getKeys($pattern = '*') {
        return $result = $this->redis->keys($pattern);
    }

    /**
     * 返回key的类型
     * @param string $key
     * @privilege Backend:
     * @return none(key不存在) int(0), string(字符串) int(1), list(列表) int(3), set(集合) int(2),zset(有序集) int(4),hash(哈希表) int(5)
     */
    public function getTypes($key = '') {
        return $this->redis->type($this->prefix . $key);
    }

    /**
     * 针对Hash数据操作
     * 添加Hash 数据
     * @param string $key KEY名称
     * @param string $field 唯一主键名称
     * @param string $value json值
     */
    public function addHashval($key, $field, $value) {
        $value = json_encode($value);
        $retRes = $this->redis->hset($this->prefix . $key, $field, $value);
        return $retRes;
    }

    /**
     * 针对Hash数据操作
     * 删除Hash 数据
     * @param string $key KEY名称
     * @param string $field 唯一主键名称
     * @param string $value json值
     */
    public function delHashval($key, $field) {
        $retRes = $this->redis->hdel($this->prefix . $key, $field);
        return $retRes;
    }

    /**
     * 针对Hash数据操作
     * 修改Hash 数据
     * @param string $key KEY名称
     * @param string $field 唯一主键名称
     * @param string $value json值
     */
    public function upHashval($key, $field, $value) {
        $value = json_encode($value);
        $retRes = $this->redis->hmset($this->prefix . $key, $field, $value);
        return $retRes;
    }

    /**
     * 针对Hash数据操作
     * 获取Hash 所有数据
     * @param string $key KEY名称
     */
    public function getallHashval($key) {
        $retRes = $this->redis->hgetall($this->prefix . $key);
        return $retRes;
    }

    /**
     * 针对Hash数据操作
     * 获取Hash 某个数据
     * @param string $key KEY名称
     * @param string $field 唯一主键名称
     */
    public function getoneHashval($key, $field) {
        $retRes = $this->redis->hget($this->prefix . $key, $field);
        return $retRes;
    }

    /**
     * 针对Hash数据操作
     * 获取Hash中所对应的键值个数
     * @param string $key KEY名称
     */
    public function getHashCount($key) {
        $retRes = $this->redis->hlen($this->prefix . $key);
        return $retRes;
    }

    /**
     * 针对Hash数据操作
     * 获取Hash中是否存在键为field的域
     * @param string $key KEY名称
     */
    public function checkHashVal($key, $field) {
        $retRes = $this->redis->hexists($this->prefix . $key, $field);
        return $retRes;
    }

    /**
     * 设定一个KEY的活动时间
     * @param string $key KEY
     * @param int $time 秒
     */
    public function expire($key, $time) {
        $retRes = $this->redis->expire($this->prefix . $key, $time);
        return $retRes;
    }

    /**
     * 获取一个KEY的活动时间
     * @param string $key KEY
     * @param int $time 秒
     */
    public function getttl($key) {
        $rs = $this->redis->ttl($this->prefix . $key);
        return $rs;
    }

    /**
     * 选择数据库
     * @param int $num 数据库编号
     */
    public function select($num) {
        $retRes = $this->redis->select($num);
        return $retRes;
    }

    /*
     * 异步保存数据
     */

    public function redissave() {
        $this->redis->bgsave();
    }

    /**
     * get top N members(从小到大)
     * 
     * 获取有序集合topN元素
     *
     * @since 2015/08/18 周二
     * @author PHPJungle
     * @param string $key
     * @param int $topN [-1获取全部,0获取0个，n>=1获取n个]
     * @param bool $withscores [是否显示score]
     * @return array
     */
    public function oSetsTopNAsc($key, $topN = 3, $withscores = false) {
        if ($key and is_numeric($topN)) {
            $opt = array('withscores' => $withscores, 'limit' => array(0, (int) $topN));
            return $this->oSetsRangeByScore($this->prefix . $key, self::OSETS_RANGE_LEFT_INF, self::OSETS_RANGE_RIGHT_INF, $opt);
        }
        return array();
    }

    /**
     * get top N members(从大到小)
     *
     * @since since 2015/08/18 周二
     * @param string $key
     * @param int $topN [defalut:3]
     * @param bool $withscores [default:false 是否显示score]
     * @return array
     */
    public function oSetsTopNDesc($key, $topN = 3, $withscores = false) {
        if ($key and is_numeric($topN)) {
            $opt = array('withscores' => $withscores, 'limit' => array(0, (int) $topN));
            return $this->oSets_range_reverse_by_score($this->prefix . $key, self::OSETS_RANGE_LEFT_INF, self::OSETS_RANGE_RIGHT_INF, $opt);
        }
        return array();
    }

    /**
     * Returns the elements of the sorted set stored at the specified key <font color=red >which have scores in the range [start,end].</font>
     *
     *  Adding a parenthesis before start or end excludes it from the range. +inf and -inf are also valid limits.
     *  
     *  获取有序集合指定范围元素
     *
     * @since 2015/08/18 周二
     * @author PHPJungle
     * @param string $key
     * @param double $score_start
     * @param double $score_end
     * @param array $opt <font color=red>[Two options are available: withscores => TRUE, and limit => array($offset, $count)]</font>
     * @return array
     * @abstract demo:<br>
     * 		oSets_range_by_score($key, 0.0,1000,array('withscores' => true,'limit'=>array(0,3)));
     */
    private function oSetsRangeByScore($key, $score_start, $score_end, $opt = array()) {
        if ($key and ( is_numeric($score_start) or $score_start === self::OSETS_RANGE_LEFT_INF)
                and ( is_numeric($score_end) or $score_end === self::OSETS_RANGE_RIGHT_INF)) {

            # limit to int !important
            if (isset($opt['limit']['0'], $opt['limit']['1'])) {
                $opt['limit']['0'] = (int) $opt['limit']['0'];
                $opt['limit']['1'] = (int) $opt['limit']['1'];
            }

            return $this->redis->zRangeByScore($key, $score_start, $score_end, $opt);
        }
        return array();
    }

    /**
     *  returns the same items(like oSets_range_by_score ) but in reverse order
     *
     * @since since 2015/08/18 周二
     * @param string $key
     * @param double $score_start
     * @param double $score_end
     * @param array $opt <font color=red>[Two options are available: withscores => TRUE, and limit => array($offset, $count)]</font>
     * @return array
     */
    private function oSets_range_reverse_by_score($key, $score_start, $score_end, $opt = array()) {
        if ($key and ( is_numeric($score_start) or $score_start === self::OSETS_RANGE_LEFT_INF )
                and ( is_numeric($score_end) or $score_end === self::OSETS_RANGE_RIGHT_INF)) {

            # limit to int !important
            if (isset($opt['limit']['0'], $opt['limit']['1'])) {
                $opt['limit']['0'] = (int) $opt['limit']['0'];
                $opt['limit']['1'] = (int) $opt['limit']['1'];
            }
            return $this->redis->zRevRangeByScore($key, $score_end, $score_start, $opt);
        }
        return array();
    }

    //===========================================================
    /**
     * Redis 获取
     * @access protected
     * @param  string $name key
     * @return void
     */
    public function cacheGet($key) {
        if (empty($key)) {
            return false;
        }
        $key = $this->strReplace($key);
        $data = $this->getstr($key);
        $this->select(0);
        return $data;
    }

    /**
     * Redis 设置
     * @access protected
     * @param  string $name key
     * @param  all $value  值
     * @param  bool $expire 生存周期
     * @return void
     */
    public function cacheSet($key, $value, $expire = 0) {
        if (empty($key)) {
            return false;
        }
        $key = $this->strReplace($key);
        $result = $this->setstr($key, $value, $expire);
        $this->select(0);
        return $result;
    }

    /**
     * 获取一个KEY剩余生存时间
     * @param $key
     * @privilege Backend:
     * @return bool
     */
    public function cacheTTL($key) {
        if (empty($key)) {
            return false;
        }
        $key = $this->strReplace($key);
        $result = $this->getttl($key);
        $this->select(0);
        return $result;
    }

    /**
     * Redis 设置
     * @access protected
     * @param  string $name key
     * @param  int $ttl  拒绝时间
     * @return void
     */
    public function cacheDel($key) {
        if (empty($key)) {
            return false;
        }
        $key = $this->strReplace($key);
        $result = $this->deletestr($key);
        $this->select(0);
        return $result;
    }

    /**
     * 获得所有KEYS
     * @param string $pattern   通配符
     * @privilege Backend:
     * @return bool
     */
    public function cacheKeysGet($key) {
        if (empty($key)) {
            return false;
        }
        $key = $this->strReplace($key);
        $result = $this->getKeys($key);
        $this->select(0);
        return $result;
    }

    /**
     * Redis Hash获取
     * @access protected
     * @param  string $name key
     * @return void
     */
    public function cacheHashGet($key) {
        if (empty($key)) {
            return false;
        }
        $key = $this->strReplace($key);
        $data = $this->getallHashval($key);
        $this->select(0);
        return $data;
    }

    /**
     * Redis Hash获取单个字段
     * @access protected
     * @param  string $name key
     * @return void
     */
    public function cacheOneHashGet($key = '', $field = '') {
        if (empty($key)) {
            return false;
        }
        $key = $this->strReplace($key);
        $data = $this->getoneHashval($key, $field);
        $this->select(0);
        return $data;
    }

    /**
     * Redis Hash插入单个字段
     * @access protected
     * @param  string $name key
     * @return void
     */
    public function cacheOneHashSet($key = '', $field = '', $value) {
        if (empty($key)) {
            return false;
        }
        $key = $this->strReplace($key);
        $data = $this->addHashval($key, $field, $value);
        $this->select(0);
        return $data;
    }

    /**
     * Redis Hash删除单个字段
     * @access protected
     * @param  string $name key
     * @return void
     */
    public function cacheOneHashDel($key = '', $field = '') {
        if (empty($key)) {
            return false;
        }
        $key = $this->strReplace($key);
        $data = $this->delHashval($key, $field);
        $this->select(0);
        return $data;
    }

    /**
     * 检查一个Hash中的域是否存在
     * @param string $key
     * @param string $field
     * @privilege Backend:
     * @return bool
     */
    public function cacheOneHashExists($key = '', $field = '') {
        if (empty($key)) {
            return false;
        }
        $key = $this->strReplace($key);
        $data = $this->checkHashVal($key, $field);
        $this->select(0);
        return $data;
    }

    /**
     * Redis 有序集合
     * @access protected
     * @param  string $name key
     * @return void
     */
    protected function getZset($key, $topN = 10, $withscores = false) {
        if (empty($key)) {
            return false;
        }
        $key = $this->strReplace($key);
        $data = $this->oSetsTopNAsc($key, $topN, $withscores);
        $this->select(0);
        return $data;
    }

    /**
     * key 自动替换
     * @access private
     * @return void
     */
    public function strReplace($key = '') {
        if (strstr($key, '_sid') !== false) {
            $key = str_replace('_sid', '', $key);
            $key = $key . '_' . session_id();
        }
        if (substr($key, 0, 10) == 'thirdpart_') {
            $this->select(12);                           //第三方店铺
        } else if (substr($key, 0, 5) == 'ecar_') {              //电动车redis数据库
            $this->select(13);
        } else if (substr($key, 0, 5) == 'user_') {              //用户缓存库
            $this->select(1);
        } else if (substr($key, 0, 6) == 'items_') {       //商品缓存库
            $this->select(2);
        } else if (substr($key, 0, 8) == 'history_') {  //用户历史浏览记录
            $this->select(3);
        } else if (substr($key, 0, 7) == 'common_') {      //商品缓存库
            $this->select(4);
        } else if (substr($key, 0, 10) == 'temporder_') {  //订单临时数据
            $this->select(5);
        } else if (substr($key, 0, 8) == 'seckill_' OR substr($key, 0, 6) == 'share_') {  //特卖临时数据
            $this->select(6);
        } else if (substr($key, 0, 8) == 'details_') {  //商品详细数据
            $this->select(7);
        } else if (substr($key, 0, 17) == 'rememberPassword_') {
            $this->select(0);
        } else if (substr($key, 0, 5) == 'auth_' OR substr($key, 0, 12) == 'autoProcess_') {
            $this->select(10);              //邮箱验证信息
        } else if (substr($key, 0, 6) == 'redis_') {
            $this->select(9);               //redis版本号
        } else if (substr($key, 0, 4) == 'pay_') {
            $this->select(11);              //支付锁定
        } else if (substr($key, 0, 10) == 'redisExist') {
            $this->select(13);              //检查第三方redis是否存在
        } else if (substr($key, 0, 11) == 'valueAdded_' OR substr($key, 0, 6) == 'store_') {
            $this->select(8);               //ip仓库 //调用互联网外部接口的缓存
        } else if (substr($key, 0, 4) == 'amb_') {
            $this->select(14);              //维修保养（临时使用）
        } else if (substr($key, 0, 12) == 'illegalquery') { //违章的
            $this->select(15);
        } else if (substr($key, 0, 7) == 'weixin_') {//微信授权缓存令牌
            $this->select(10);
        } else if (substr($key, 0, 8) == 'carwash_' OR substr($key, 0, 12) == 'information_') {
            $this->select(11);              //洗车支付码
        } else {
            $this->select(0);
        }
        return $key;
    }

}
