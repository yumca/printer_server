<?php

error_reporting(E_ALL & ~E_NOTICE);
set_time_limit(0);
/**
 * 解析图片数据
 * 2017/11/30
 * 计划任务
 */
if(!defined('PATH')){
    define('PATH', dirname(dirname(dirname(__FILE__))));
}
if(!defined('SPATH')){
    define('SPATH', '/System');
}
if(!defined('ENVIRONMENT')){
    define('ENVIRONMENT', 'dev');
}
include_once(PATH . SPATH . '/Base/Base.php');

class ParsePrintData extends Base {

    protected $imgpath,$txtpath,$tesseract,$tesseractdata,$uuid;
    public $shopconf;
    public $keys = array(
        //存储打印数据
        'printdata_key' => 'printdata',
    );
    public $DbPdo,$redis;

    public function __construct($uuid) {
        parent::__construct();
        $this->redis = new RedisCache($this->conf['redis']);
        $db_name = 'dbname=' . $this->conf['db_local']['DB_PREFIX'] . 'print';
        $db_host = 'host=' . $this->conf['db_local']['DB_HOST'];
        $db_port = 'port=' . $this->conf['db_local']['DB_PORT'];
        $DSN = 'mysql:' . join(';', array($db_name, $db_host, $db_port));
        $this->DbPdo = new \DbPdo($DSN, $this->conf['db_local']['DB_USER'], $this->conf['db_local']['DB_PWD'], '');
        $this->imgpath = $this->conf['imgpath'];
        $this->txtpath = $this->conf['txtpath'];
        $this->tesseract = $this->conf['tesseract'];
        $this->tesseractdata = $this->conf['tesseractdata'];
        $this->uuid = $uuid;
        //根据设备uuid获取设备配置
        $this->shopconf = $this->DbPdo->getRow("select * from box_user where uuid = '{$this->uuid}'");
    }
    
    public function upDataToQrcode() {
        $redis_datas = $this->redis->getallHashval($this->keys['printdata_key'].'_'.$this->uuid);
        if($redis_datas){
            $merge_datas = $this->merge_datas($redis_datas);
            $ParseMergeData = $this->ParseMergeData($merge_datas);
            $order_datas = [];
            foreach ($ParseMergeData as $k=>$v){
                $order_datas[] = $this->parseData($v);
            }
            
            $result['data'] = $order_datas;
            if(count($order_datas) > 0){
//                $od = end($order_datas);
//                $result['url'] = $this->getKuMaoUrl($od['orderdata']['order_id'], $od['orderdata']['amount'], $od['orderdata']['method'], $od['orderdata']['times']);
//                $result['money'] = $od['orderdata']['amount'];
                
                //测试代码
                $shopID = $this->shopconf['shopid'];
                $shopCode = $this->shopconf['shopcode'];
                $order_id = date('YmdHis').rand(1000, 9999);//$od['orderdata']['order_id'];
                $amount = rand(1, 2000);//$od['orderdata']['amount'];
                $arr = ['现金','支付宝','微信','银行卡','平安付','点评'];
                $method = $arr[rand(0, 5)];//$od['orderdata']['method'];
                $times = date('Y-m-d H:i:s');//$od['orderdata']['times'];
//                $this->DbPdo->execute("insert into order_log(shopid,shopcode,order_id,amount,method,order_time) values('{$shopID}','{$shopCode}','{$order_id}','{$amount}','{$method}','{$times}')");
                $url = "http://meckm.t.hxqctest.com/Index/Index/index?z="
                        . "{$shopID}|{$shopCode}|{$order_id}|{$amount}|{$method}|{$times}";
                $result['url'] = 'http://10.0.15.203:33001/jump.php?jumpurl='.urlencode($url);

                $result['money'] = $amount;
            }else{
                $result = false;
            }
            
            return $result;
        }
    }
    
    private function merge_datas($redis_datas){
        $orgdata = [];
        if (!empty($redis_datas)) {
            foreach ($redis_datas as $k => $v) {
                $v = json_decode($v, true);
                $tmpctime = explode(' ', $v['ctime']);
                $tmpctime[0] = substr($tmpctime[0], 2);
                $ctime = $tmpctime[1] . '.' . $tmpctime[0];
                $orgdata[$ctime] = $v;
            }
        }else{
            return [];
        }
        $this->redis->deletestr($this->keys['printdata_key'].'_'.$this->uuid);
        if(!empty($orgdata)){
            $i = 0;
            //根据设备uuid获取打印速度
            $speed = $this->shopconf['speed'] ? $this->shopconf['speed'] : 1;
            ksort($orgdata);
            $tmpv = $orgdata;
            end($tmpv);
            $last_k = key($tmpv);
            $tmp = array();
            $last_ctime = 0;
            foreach ($orgdata as $kk => $vv) {
                //$vv = json_decode($vv,true);
                $tmpctime = explode(' ', $vv['ctime']);
                $tmpctime[0] = substr($tmpctime[0], 2);
                $ctime = $tmpctime[1] . '.' . $tmpctime[0];
                $vv['uuid'] = $this->uuid;
                if ($last_ctime > 0) {
                    if (($ctime - $last_ctime) < $speed) {
                        $last_ctime = $ctime;
                        $tmp[$i]['DataPackBuf'][1] .= $vv['DataPackBuf'][1];
                    } else {
                        $i++;
                        $last_ctime = $ctime;
                        $tmp[$i] = $vv;
                    }
                } else {
                    $last_ctime = $ctime;
                    $tmp[$i] = $vv;
                }
            }
            $merge_datas = array();
            foreach ($tmp as $data) {
                $guid = getItemId();
                $tmpctime = explode(' ', $data['ctime']);
                $ctime = $tmpctime[1] . '.' . $tmpctime[0];
                $tmpmerge = array(
                    'uuid' => $data['uuid'],
                    'CmdCode' => $data['CmdCode'][1],
                    'PackeId' => $data['PackeId'][1],
                    'DataPackBuf' => $data['DataPackBuf'][1],
                    'DataPackeLen_oct' => $data['DataPackeLen_oct'],
                    'DataPackeTrueLen' => $data['DataPackeTrueLen'],
                    'DataPackeLen_left' => $data['DataPackeLen_left'],
                    'mctime' => $ctime,
                    'ctime' => $tmpctime[1],
                    'guid' => $guid,
                );
                $merge_datas[] = $tmpmerge;
            }
            return $merge_datas;
        } else {
            return [];
        }
    }

        /**
     * @abstract 创建目录
     * @param <type> $dir 目录名
     * @return bool
     */
    private function createDir($dir) {
        return is_dir($dir) or ( $this->createDir(dirname($dir)) and @ mkdir($dir, 0777));
    }
    
    private function InitDir($FilePath) {
        if (is_dir($FilePath) === false) {
            if (!$this->createDir($FilePath)) {
                echo("创建目录失败!");
                //throw exception
                return false;
            }
        }
        return true;
    }

    public function ParseMergeData($datas) {
        $datano = array();
        $rollbacknum = 0;
        if (!empty($datas)) {
            parseempty:
            if(!empty($datano)){
                $datas = $datano;
                $datano = array();
                $lastem = '1b52';
                $rollbacknum++;
            }
            foreach($datas as $datak=>$datav){
                $print_data = $datav['DataPackBuf'];
                $print_data_arr = array();
                $break = false;
                $offset = 0;
                $last = isset($lastem) ? $lastem : '';
                while(!$break){
                    $tmp = array();
                    if(in_array(substr($print_data,0,6),array('1c7e53'))){
                        //FS ~ S 选择汉字打印速度
                        $tmp['data'] = substr($print_data,0,6);
                        $tmp['type'] = substr($print_data,0,6);
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,6);
                        $offset += 6;
                    }else if(substr($print_data,0,6) == '1d7630'){
                        //GS v 0 m xL xH yL yH d1...dk 打印光栅位图
                        $tmp['m'] = substr($print_data,6,2);
                        $tmp['xl'] = substr($print_data,8,2);
                        $tmp['xh'] = substr($print_data,10,2);
                        $tmp['yl'] = substr($print_data,12,2);
                        $tmp['yh'] = substr($print_data,14,2);
                        $tmp['xdatanum'] = base_convert($tmp['xl'],16,10)+base_convert($tmp['xh'],16,10)*256;
                        $tmp['ydatanum'] = base_convert($tmp['yl'],16,10)+base_convert($tmp['yh'],16,10)*256;
                        $tmp['data'] = str_split(substr($print_data,16,$tmp['xdatanum']*$tmp['ydatanum']*2),2);
                        $tmp['type'] = substr($print_data,0,6);
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,16+$tmp['xdatanum']*$tmp['ydatanum']*2);
                        $offset += 16+$tmp['xdatanum']*$tmp['ydatanum']*2;
                    }else if(substr($print_data,0,6) == '1d2846'){
                        //GS ( F pL pH a m nL nH 设置黑标定位偏移量
                        $tmp['pl'] = substr($print_data,6,2);
                        $tmp['ph'] = substr($print_data,8,2);
                        $tmp['am'] = substr($print_data,10,2);
                        $tmp['nl'] = substr($print_data,12,2);
                        $tmp['nh'] = substr($print_data,14,2);
                        $tmp['type'] = substr($print_data,0,6);
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,16);
                        $offset += 16;
                    }else if(substr($print_data,0,4)=='1b63'){
                        //1b63 ESC c 允许/禁止反向打印
                        if(substr($print_data,4,2)=='35'){
                            //1b6335 ESC c 5 n允许/禁止走纸按键
                            $tmp['data'] = substr($print_data,6,2);
                            $tmp['type'] = substr($print_data,0,6);
                            $print_data_arr[] = $tmp;
                            $print_data = substr($print_data,8);
                            $offset += 8;
                        }elseif(substr($print_data,4,2)=='34'){
                            //1b6334 ESC c 4 n 设置/取消缺纸时停止打印
                            $tmp['data'] = substr($print_data,0,6);
                            $tmp['type'] = substr($print_data,0,6);
                            $print_data_arr[] = $tmp;
                            $print_data = substr($print_data,6);
                            $offset += 6;
                        }else{
                            $tmp['data'] = substr($print_data,4,2);
                            $tmp['type'] = substr($print_data,0,4);
                            $print_data_arr[] = $tmp;
                            $print_data = substr($print_data,6);
                            $offset += 6;
                        }
                    }else if(substr($print_data,0,4) == '1b70'){
                        //1b70 ESC p m t1 t2产生钱箱驱动脉冲
                        $tmp['m'] = substr($print_data,4,2);
                        $tmp['t1'] = substr($print_data,6,2);
                        $tmp['t2'] = substr($print_data,8,2);
                        $tmp['type'] = substr($print_data,0,4);
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,10);
                        $offset += 10;
                    }else if(in_array(substr($print_data,0,4),array('1b40','1b76','1c26','1c2e','1b36','1b37','1b69','1b6d','1b0c','1b32','1b3c','1b6a','1b74','1c21','1d0c','1b7b','1c2d'))){
                        //不带参数指令
                        //1b40 ESC @ 初始化
                        //1b76 ESC v 向主机传送打印机状态
                        //1c26 FS & 选择汉字模式
                        //1c2e FS. 取消汉字打印模式
                        //1b36选择6*8字符集
                        //1b37选择6*8字符集
                        //1b69 ESC i 切纸刀命令 全切纸
                        //1b6d ESC m 执行部分切纸
                        //1b0c ESC FF执行走纸到黑标位置
                        //1b32 ESC 2 设定1/6英寸换行量
                        //1b3c ESC〈 打印头归位
                        //1b6a ESC j n 退纸n/144英寸
                        //1b74 ESC t n 选择字符集
                        //1c21 FS ! n 设置汉字字符模式
                        //1d0c GS FF 送黑标纸至打印起始位置
                        //1b7b ESC { n 选择/取消倒置打印模式
                        //1c2d FS - n 选择/取消汉字下划线模式
                        $tmp['data'] = substr($print_data,0,4);
                        $tmp['type'] = substr($print_data,0,4);
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,4);
                        $offset += 4;
                    }else if(in_array(substr($print_data,0,4),array('1b4a','1b64','1b2d','1b2e','1b21','1d42','1c49','1b6c','1b51','1b31','1b20','1b61','1c72','1b55','1b56','1d68','1d77','1d48','1d2f','1b23','1b25','1b43','1b47','1b4d','1d21','1d56','1b3f','1b45','1d66','1c57','1b33','1d54','1d49','1d72','1004','1005','1b3d','1b65'))){
                        //一个参数指令
                        //1b4a ESC J n 打印并进纸
                        //1b64 ESC d n 打印并进纸n行
                        //1b2d ESC – n取消设置上划线
                        //1b2e取消设置下划线
                        //1b21 ESC ! n 设置字符打印模式
                        //1d42 GS B n 设置/取消反白打印
                        //1c49设置字符旋转打印
                        //1b6c设置左侧不打印区域
                        //1b51设置右侧不打印区域
                        //1b31设置行间距
                        //1b20 ESC SP n 设置字间距
                        //1b61 ESC a n 设置对齐方式，当n=0时，字符行执行左对齐打印；当n=1时，字符行执行居中对齐打印；当n=2时，字符行执行右对齐打印
                        //1c72选择上下标
                        //1b55 ESC U n 水平放大字符
                        //1b56 ESC V n 垂直放大字符  --------选择/取消顺时针旋转90度
                        //1d68 GS h n 设置条码高度
                        //1d77 GS w n 设置条码宽度
                        //1d48 GS H n 选择可识读字符 --------选择HRI字符的打印位置
                        //1d2f GS / m 打印下载位图
                        //1b23设置打印曲线打印方式
                        //1b25 ESC % n 允许/禁止用户自定义字符集
                        //1b43设定检测黑标的范围
                        //1b47 ESC G n 选择/取消双重打印模式
                        //1b4d ESC J n 设置打印点阵字符字形 当n=0时，选择字型为12×24点阵字符；当n=1时，选择字型为 8×16 点阵字符。
                        //1d21 GS ! n选择字符尺寸
                        //1d56 GS V  m走纸到切纸位置
                        //1b3f ESC ? n 取消用户自定义字符
                        //1b45 ESC E n 选择/取消加粗模式
                        //1d66 GS f n 选择HRI使用字体
                        //1c57 FS W n 选择/取消汉字倍高倍宽
                        //1b33 ESC 3 n 设置字符行间距为n/8英寸
                        //1d54 GS T n设打印位置到打印行起始
                        //1d49 GS I n传送打印机ID
                        //1d72 GS r n返回状态
                        //1004 DLE EOT n 实时状态传送
                        //1005 DLE ENQ n 对打印机的实时请求
                        //1b3d ESC = n 设备设置/取消
                        //1b65 ESC e n 打印并反向进纸n字符行
                        $tmp['data'] = substr($print_data,4,2);
                        $tmp['type'] = substr($print_data,0,4);
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,6);
                        $offset += 6;
                    }else if(in_array(substr($print_data,0,4),array('1b24','1b58','1d51','1c70','1b73','1c53','1c54','1b5c','1d4c','1d50','1d57','1c3f'))){
                        //两个参数指令
                        //1b24 ESC $ nL nH 设置打印绝对位置
                        //1b58放大字符
                        //1d51设置条码水平打印位置
                        //1c70 FS p n m打印下传NV位图 --打印下载到FLASH中的位图
                        //1b73打印深度调整
                        //1c53 FS S n1 n2 设定全角汉字字间距
                        //1c54 FS T n1 n2 设定半角汉字字间距
                        //1b5c ESC \ nL nH 设置相对横向打印位置
                        //1d4c GS L nL nH 设置左边距
                        //1d50 GS P x y设置横向和纵向移动单位
                        //1d57 GS W nL nH 设置打印区域宽度
                        //1c3f FS ? c1   c2 取消用户自定义汉字
                        $tmp['data'] = substr($print_data,4,4);
                        $tmp['type'] = substr($print_data,0,4);
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,8);
                        $offset += 8;
                    }else if(substr($print_data,0,4) == '1b44'){
                        //ESC D n1...nk NULL 设置横向跳格位置-----设置水平制表位  截取位不定，待补充
                        $tmp['data'] = substr($print_data,0,4);
                        $tmp['type'] = substr($print_data,0,4);
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,4);
                        $offset += 4;
                    }else if(substr($print_data,0,4) == '1b26'){
                        //ESC & y c1 c2 [x1 d1...d(y*x1)]...[xk d1...d(y*xk)] 设置用户自定义字符集
                        $tmp['y']=substr($print_data,4,2);
                        $tmp['c1']=substr($print_data,6,2);
                        $tmp['c2']=substr($print_data,8,2);
                        $sizeNum=base_convert($tmp['c2'],16,10)-base_convert($tmp['c1'],16,10);
                        $print_data = substr($print_data,10);
                        $offset += 10;
                        for($i=1;$i<=$sizeNum;$i++){
                            $data['x']=substr($print_data,0,2);
                            $data['datanum']=base_convert($tmp['y'],16,10)*base_convert($data['x'],16,10);
                            $data['data']=substr($print_data,2,$data['datanum']*2);
                            $print_data = substr($print_data,2,$data['datanum']*2);
                            $tmp['data'][]=$data;
                        }
                        $tmp['type'] = substr($print_data,0,4);
                        $print_data_arr[] = $tmp;
                    }else if(substr($print_data,0,4) == '1b27'){
                        //打印水平行上N个点  截取位不定，待补充
                        $tmp['data'] = substr($print_data,0,4);
                        $tmp['type'] = substr($print_data,0,4);
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,4);
                        $offset += 4;
                    }else if(substr($print_data,0,4) == '1b29'){
                        //打印水平行上N个线段  截取位不定，待补充
                        $tmp['data'] = substr($print_data,0,4);
                        $tmp['type'] = substr($print_data,0,4);
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,4);
                        $offset += 4;
                    }else if(substr($print_data,0,4) == '1d6b'){
                        //① GS k m d1...dk NUL ② GS k m n d1...dn 打印条码
                        $tmp['m']=substr($print_data,4,2);
                        $m=base_convert($tmp['m'],16,10);
                        if($m==4){
                            $print_data=substr($print_data,6);
                            $offset +=6;
                            $indexof=stripos($print_data,'00');
                            $tmp['data']=substr($print_data,0,$indexof+2);
                            $print_data = substr($print_data,$indexof+2);
                            $offset += $indexof+2;
                        }else{
                            $tmp['n']=substr($print_data,6,2);
                            $n=base_convert($tmp['n'],16,10);
                            $tmp['data']=substr($print_data,8,$n*2);
                            $print_data = substr($print_data,8+$n*2);
                            $offset += 8+$n*2;
                        }
                        $tmp['type'] = substr($print_data,0,4);
                        $print_data_arr[] = $tmp;
                    }else if(substr($print_data,0,4) == '1c56'){
                        //FS V n垂直制表并打印  截取位不定，待补充
                        $tmp['data'] = substr($print_data,0,4);
                        $tmp['type'] = substr($print_data,0,4);
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,4);
                        $offset += 4;
                    }else if(substr($print_data,0,4) == '1c71'){
                        //FS q n [xL xH yL yH d1...dk]1...[xL xH yL yH d1...dk]n 定义Flash位图  截取位不定，待补充
                        $tmp['data'] = substr($print_data,0,4);
                        $tmp['type'] = substr($print_data,0,4);
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,4);
                        $offset += 4;
                    }else if(substr($print_data,0,4) == '1c32'){
                        //1c32 FS 2 c1 c2 d1...dk 用户自定义汉字  截取位不定，待补充
                        $tmp['data'] = substr($print_data,0,4);
                        $tmp['type'] = substr($print_data,0,4);
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,4);
                        $offset += 4;
                    }else if(substr($print_data,0,4) == '1d2a'){
                        //GS * x y d1...d(x*y*8) 定义下载位图 截取位不定，待补充
                        $tmp['data'] = substr($print_data,0,4);
                        $tmp['type'] = substr($print_data,0,4);
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,4);
                        $offset += 4;
                    }else if(substr($print_data,0,4) == '1b52'){
                        //ESC R n 选择n个不同国家的不同ASCII字符集
                        $tmp['data'] = substr($print_data,4,2);
                        $tmp['type'] = substr($print_data,0,4);
                        $last = $tmp['type'];
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,6);
                        $offset += 6;
                    }
//                    else if(substr($print_data,0,4) == '1b4b'){
//                        //ESC K 图形打印命令
//                        $tmp['nl'] = substr($print_data,4,2);
//                        $tmp['nh'] = substr($print_data,6,2);
//                        $tmp['datanum'] = base_convert($tmp['nl'],16,10)+base_convert($tmp['nh'],16,10)*256;
//                        $tmp['data'] = str_split(substr($print_data,8,$tmp['datanum']*2),2);
//                        $tmp['type'] = substr($print_data,0,4);
//                        $print_data_arr[] = $tmp;
//                        $print_data = substr($print_data,8+$tmp['datanum']*2);
//                        $offset += 8+$tmp['datanum']*2;
//                    }
                    else if(substr($print_data,0,4) == '1b2a'){
                        // ESC * m nL nH d1...dk 设置点阵图
                        $tmp['m'] = substr($print_data,4,2);
                        $tmp['nl'] = substr($print_data,6,2);
                        $tmp['nh'] = substr($print_data,8,2);
                        $tmp['datanum'] = base_convert($tmp['nl'],16,10)+base_convert($tmp['nh'],16,10)*256;
                        $tmp['data'] = str_split(substr($print_data,10,$tmp['datanum']*2),2);
                        $tmp['type'] = substr($print_data,0,4);
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,10+$tmp['datanum']*2);
                        $offset += 10+$tmp['datanum']*2;
                    }else if(in_array(substr($print_data,0,2),array('0a','0d','09','fa','0c'))){
                        //0a LF 打印并换行
                        //0d CR 打印并回车
                        //09 HT 水平制表
                        //fa打印结束
                        //0c打印走纸到下页首
                        $tmp['data'] = substr($print_data,0,2);
                        $tmp['type'] = substr($print_data,0,2);
                        $print_data_arr[] = $tmp;
                        $print_data = substr($print_data,2);
                        $offset += 2;
                    }else{
                        if($last == '1b52'){
                            $lastdata = end($print_data_arr);
                            if($lastdata['type'] == '1b52_data'){
                                $tmp = $lastdata;
                                $tmp['data'] .= substr($print_data,0,2);
                                $print_data_arr[key($print_data_arr)] = $tmp;
                                $offset += 2;
                                $print_data = substr($print_data,2);
                            }else if($lastdata['type'] == '1d56' && (strlen($lastdata['data']) < 3)){
                                $tmp = $lastdata;
                                $tmp['data'] .= substr($print_data,0,2);
                                $print_data_arr[key($print_data_arr)] = $tmp;
                                $offset += 2;
                                $print_data = substr($print_data,2);
                            }else{
                                $tmp['data'] = substr($print_data,0,2);
                                $tmp['type'] = '1b52_data';
                                $print_data_arr[] = $tmp;
                                $offset += 2;
                                $print_data = substr($print_data,2);
                            }
                        }else{
                            $dont[] = array(
                                'offset' => $offset,
                                'data' => substr($print_data,0,2)
                            );
                            $offset += 2;
                            $print_data = substr($print_data,2);
                        }
                    }
                    if(strlen($print_data) <= 0){
                        $break = true;
                    }
                }

                $arrs = array('1b2a' => array(), '1b52' => array(), '1d7630' => array());
                foreach ($print_data_arr as $k => $v) {
                    $bin = array();
                    if ($v['type'] == '1b2a') {
                        foreach ($v['data'] as $dv) {
                            if ($dv !== '') {
                                $i = base_convert($dv, 16, 2);
                                $bin[] = str_pad($i, 8, '0', STR_PAD_LEFT);
                            }
                        }
                        $line = array('', '', '', '', '', '', '', '');
                        foreach ($bin as $bv) {
                            $b = str_split($bv, 1);
                            foreach ($line as $kv => $lv) {
                                $line[$kv] .= $b[$kv];
                            }
                        }
                        $arrs['1b2a'] = array_merge($arrs['1b2a'], $line);
                        $print_data_type = '1b2a';
                    } else if ($v['type'] == '1b52_data') {
                        $str = hex2bin($v['data']);
                        $encodeing = mb_detect_encoding($str, array('ASCII', 'GB2312', 'GBK', 'UTF-8', 'BIG5'));
                        if (strtoupper($encodeing) != 'UTF-8') {
                            if (strtoupper($encodeing) == 'EUC-CN') {
                                $encodeing = 'GB2312';
                            }
                            $str = mb_convert_encoding($str, "UTF-8", $encodeing);
                        }
                        $arrs['1b52'] = array_merge($arrs['1b52'], array($str));
                        $print_data_type = '1b52';
                    }else if ($v['type'] == '1d7630') {
                        $j = 0;
                        foreach ($v['data'] as $dv) {
                            if ($dv !== '') {
                                $i = base_convert($dv, 16, 2);
                                if(!isset($bin[intval($j/$v['xdatanum'])])){
                                    $bin[intval($j/$v['xdatanum'])] = '';
                                }
                                $bin[intval($j/$v['xdatanum'])] .= str_pad($i, 8, '0', STR_PAD_LEFT);
                            }
                            $j++;
                        }
                        
                        $arrs['1d7630'] = array_merge($arrs['1d7630'], $bin);
                        $print_data_type = '1d7630';
                    }
                }
                $guid = $datav['guid'];
                if(empty($arrs['1b52']) && empty($arrs['1b2a']) && empty($arrs['1d7630'])){
                    $this->myLogs('数据解析失败,uuid:' . $datav['uuid'] . ';Guid:' . $guid, 'e', 'ParseMergeData', array());
                    $datano[] = $datav;
                    continue;
                }
                if (!empty($arrs['1b52'])) {
                    $str = '';
                    //去重
                    $tmp1b52 = array_unique($arrs['1b52']);
                    foreach ($tmp1b52 as $arrsv) {
                        $str .= $arrsv."\n";
                    }
                    $str = base64_encode($str);
                    $datas[$datak]['orgdata'] = $str;
                    $datas[$datak]['is_parse'] = 2;
                    $this->myLogs('1b52:解析数据成功,uuid:' . $datav['uuid'] . ';Guid:' . $guid, 'n', 'ParseMergeData', array());
                }
                if(!empty($arrs['1d7630'])){
                    if(!isset($arrs['1b2a'])){
                        $arrs['1b2a'] = array();
                    }
                    $arrs['1b2a'] = array_merge($arrs['1b2a'], $arrs['1d7630']);
                }
                if (!empty($arrs['1b2a'])) {
                    /* ----------- */
                    //if($print_data_type == '1b2a' || 1){
                        /* 去除条形码 */
//                        file_put_contents('1.txt', var_export($tmparr,true));
//                        var_dump($tmparr);exit;
                        $tmparr = $arrs['1b2a'];
                        $shopconf = array(
                            'qr_type' => '1',
                            'qr_offset_x' => '200', //特征最低长度
                            'qr_offset_h' => '50', //特征最低高度
                            'bar_type' => '1',
                            'bar_offset' => '60',
                        );
                        //print_r($arrs);
                        $arrs1 = [];
                        if($shopconf['qr_type'] == 1 || $shopconf['bar_type'] == 1 || $shopconf['qr_type'] == 2){
                            $unsetqr = [];
                            foreach($tmparr as $k=>$v){
                                //行距补位
                                if(strlen($v) < 2){
                                    $v = str_pad($v, strlen($tmparr[1]), '0');
                                }
                                $w = str_split($v, 1);
                                foreach($w as $wk=>$wv){
                                    if(!isset($arrs1[$wk])){
                                        $arrs1[$wk] = '';
                                    }
                                    $arrs1[$wk] .= $wv;
                                }
                                if($shopconf['qr_type'] == 1){
                                    //寻找横向二维码特征 并记录坐标组
                                    $xv_len = strpos($v,'1');
                                    if($xv_len > $shopconf['qr_offset_x']){
                                        //记录横向坐标
                                        $x[] = $k;
                                    }
                                }
                            }
                            if($shopconf['qr_type'] == 1){
                                $qi = -1;
                                end($x);
                                $edk = key($x);
                                foreach($x as $xk=>$sv){
                                    if($qi < 0){
                                        $qi = $sv;
                                    }else{
                                        if(($sv-$x[$xk-1]) != 1){
                                            if(($xk-1)-$qi > $shopconf['qr_offset_h']){
                                                //var_dump();
                                                $unsetqr[] = ['h1'=>$qi,'h2'=>$x[$xk-1]];
                                            }
                                            $qi = -1;
                                        }else{
                                            if($edk == $xk){
                                                $unsetqr[] = ['h1'=>$qi,'h2'=>$x[$edk]];
                                            }
                                        }
                                    }
                                }
                            }

                            $barh1 = 0;
                            $barh2 = 0;
                            foreach($arrs1 as $bark=>$v1){
                                if($shopconf['bar_type'] == 1){
                                    //寻找条形码特征
                                    $barv_len = strlen($v1);
                                    for($i=0;$i<$barv_len;$i++){
                                        if($v1[$i] == 1){
                                            $line = substr($v1,$i,$shopconf['bar_offset']);
                                            //查找是线长是否有中断
                                            if(strlen($line) == $shopconf['bar_offset'] && strpos($line,'0') === false){
                                                //记录条形码开始纵向坐标$i
                                                $barh1 = $i;
                                                //获取条形码结束纵向坐标
                                                $barh2 = strpos(substr($v1,$i),'0')+$i;
                                                break;
                                            }
                                        }
                                    }
                                    if($barh1 > 0 && $barh2 > 0){
                                        break;
                                    }
                                }
                            }

                            if($barh1 > 0 && $barh2 > 0){
                                for($i=$barh1;$i<$barh2;$i++){
                                    unset($tmparr[$i]);
                                }
                            }
                            if(!empty($unsetqr)){
                                foreach($unsetqr as $unsetqrv){
                                    $h1 = $unsetqrv['h1']-1;
                                    $h2 = $unsetqrv['h2']+1;
                                    for($i=$h1;$i<$h2;$i++){
                                        unset($tmparr[$i]);
                                    }
                                }
                            }
                            $tmparr = array_values($tmparr);
                        }
                        file_put_contents('/data/www/1.txt', var_export($tmparr,true));exit;
                        $i = 0;
                        $ttmp = array();
                        $lastnum = 0;
                        foreach ($arrs['1b2a'] as $v) {
                            if (strlen($v) != 1) {
                                $w = str_split($v, 1);
                                $break = false;
                                foreach ($w as $wv) {
                                    if ($wv == 1) {
                                        $break = true;
                                        break;
                                    }
                                }
                                if ($break == true) {
                                    $ttmp[$i][] = $v;
                                } else {
                                    $i++;
                                }
                            }
                        }
                        /* 去重 */
                        $keys = array();
                        foreach($ttmp as $tk=>$tv){
                            if(count($tv) == 30 && $tv[0] == '0'){
                                continue;
                            }else{
                                $tmps = array();
                                foreach ($tv as $ttk=>$ttv){
                                    foreach($ttmp as $tmptk=>$tmptv){
                                        if($tmptk == $tk || (isset($tmptv[$ttk]) && $tmptv[$ttk] != $ttv)){
                                            continue;
                                        }else{
                                            $tmps[$tmptk][] = 1;
                                        }
                                    }
                                }
                                if(!empty($tmps)){
                                    foreach($tmps as $tmpsk=>$tmpsv){
                                        if(count($tmpsv) === count($tv)){
                                            if($tk < $tmpsk){
                                                $keys[] = $tk.'-'.$tmpsk;
                                            }else{
                                                $keys[] = $tmpsk.'-'.$tk;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        foreach($keys as $ksk=>$ksv){
                            $ksv = explode('-', $ksv);
                            unset($ttmp[$ksv[1]]);
                        }
                        /* 去除横点或者横线 */
                        $tmparr = array();
                        foreach ($ttmp as $tk => $tv) {
                            if (count($tv) > 4) {
                                $tmparr[] = $tv;
                                $tmparr[] = array(0,0,0,0,0,0,0,0);
                            }
                        }
                        /* 放大 */
                        $ttmp = $tmparr;
                        $multiple = 1;
                        $tmparr = array();
                        foreach ($ttmp as $tk => $tv) {
                            foreach ($tv as $ttk => $ttv) {
                                $ttw = str_split($ttv, 1);
                                foreach ($ttw as $ttwk => $ttwv) {
                                    $ttw[$ttwk] = str_pad($ttwv, $multiple, $ttwv, STR_PAD_LEFT);
                                }
                                for ($tti = 0; $tti < $multiple; $tti++) {
                                    $tmparr[] = implode('', $ttw);
                                }
                            }
                        }
                        
                        /* 去除二维码 */
                        /* 截取单字 */
                        $f = array();
                        foreach($tmparr as $tk=>$tv){
                            if(count($tv) < 8){
                                unset($tmparr[$tk]);
                            }else{
                                $a = array();
                                foreach($tv as $tvv){
                                    $tw = str_split($tvv,1);
                                    foreach($tw as $twk=>$twv){
                                        if($twv == 1){
                                            $a[] = $twk;
                                        }
                                    }
                                }
                                $a = array_unique($a);
                                sort($a);
                                $lastnum = reset($a);
                                $l = 0;
                                $offset = array();
                                foreach($a as $av){
                                    if($av == $lastnum){
                                        $offset[$l]['start'] = $av;
                                    }else if(($av-$lastnum) < 3){
                                        $offset[$l]['end'] = $av;
                                    }else if(($av-$lastnum) > 2){
                                        $l++;
                                        $offset[$l]['start'] = $av;
                                    }
                                    $lastnum = $av;
                                }
                                $end = end($offset);
                                if(!isset($end['end'])){
                                    $offset[key($offset)]['end'] = $lastnum;
                                }
                                foreach($offset as $ofk=>$ofv){
                                    foreach($tv as $tkk=>$tvv){
                                        $f[$tk][$ofk][$tkk] = '00'.substr($tvv,$ofv['start'],($ofv['end']-$ofv['start'])).'00';
                                    }
                                    $str = '0';
                                    array_unshift($f[$tk][$ofk],str_pad($str,strlen(reset($f[$tk][$ofk])),'0'),str_pad($str,strlen(reset($f[$tk][$ofk])),'0'));
                                    array_push($f[$tk][$ofk],str_pad($str,strlen(reset($f[$tk][$ofk])),'0'),str_pad($str,strlen(reset($f[$tk][$ofk])),'0'));
                                }
                            }
                        }
                        $farrs = array();
                        foreach($f as $fk=>$fv){
                            $tmpfarrs = array();
                            foreach($fv as $ffk=>$ffv){
                                foreach($ffv as $fffk=>$fffv){
                                    if(!isset($tmpfarrs[$fffk])){
                                        $tmpfarrs[$fffk] = $fffv;
                                    }else{
                                        $tmpfarrs[$fffk] .= $fffv;
                                    }
                                }
                            }
                            $farrs = array_merge($farrs,$tmpfarrs);
                        }
//                        file_put_contents('1.txt', var_export($farrs,true));exit;
//                        var_dump($farrs);
//                        var_dump($arrs);
//                        exit;
                        $arrs['1b2a'] = $tmparr;
                    //}
                    
                    /* ----------- */
                    $width = 0; //画布宽度
                    $height = count($arrs['1b2a']); //画布高度
                    foreach ($arrs['1b2a'] as $v) {
                        $len = strlen($v);
                        if ($width < $len) {
                            $width = $len;
                        }
                    }

                    $im = imagecreatetruecolor($width, $height); //创建画布
                    $white = imagecolorallocate($im, 255, 255, 255); //设置一个颜色变量为白色
                    $black = imagecolorallocate($im, 0, 0, 0); //设置一个颜色变量为黑色
                    imagefill($im, 0, 0, $white); //将背景设为白色

                    foreach ($arrs['1b2a'] as $ak => $av) {
                        $w = str_split($av, 1);
                        foreach ($w as $wk => $wv) {
                            if ($wv == '1') {
                                imagesetpixel($im, $wk, $ak, $black); //画像素点
                            }
                        }
                    }
                    $imgpath = 'img/'. $datav['uuid'] . '/' . date('Ym') . '/' . date('d');
                    $this->InitDir($this->imgpath . $imgpath);
                    $imgname = $guid . '.png';
                    $imgsrc = $this->imgpath . $imgpath . '/' . $imgname;
                    $img = imagepng($im, $imgsrc); //生成PNG格式的图片
                    imagedestroy($im); //销毁图像资源，释放画布占用的内存空间
                    if($img === false){
                        $this->myLogs($print_data_type.':生成png图片失败,uuid:' . $datav['uuid'] . ';Guid:' . $guid, 'n', 'ParseMergeData', array('imgpath'=>$imgsrc));
                        continue;
                    }
                    $datas[$datak]['img'] = $imgpath . '/' . $imgname;
                    $datas[$datak]['is_parse'] = 2;
                    $this->myLogs($print_data_type.':生成png图片成功,uuid:' . $datav['uuid'] . ';Guid:' . $guid, 'n', 'ParseMergeData', array());
                }
            }
            if(!empty($datano) && $rollbacknum < 2){
                $this->myLogs('有数据解析失败，尝试重新解析第'.($rollbacknum+1).'次', 'e', 'ParseMergeData', array());
                goto parseempty;
            }
            return $datas;
        }else{
            return [];
            $this->myLogs('无数据更新', 'n', 'ParseMergeData', array());
        }
    }

    public function parseData($data) {
        if($data['is_parse'] == 2 && !empty($data['img'])){  //如果是图片则调用图片识别接口
            $guid = $data['guid'];
            $img_url = $this->imgpath . $data['img'];
            $textload = date('Ym').'/'. date('d').'/';
            $this->InitDir($this->txtpath . $textload);
            if(file_exists($this->tesseractdata.$this->shopconf['meccode'].'.traineddata')){
                $traineddata = $this->shopconf['meccode'];
            }else{
                $traineddata = 'chi_sim';
            }
            $this->myLogs('开始图片识别,img:' . $img_url . ';Guid:' . $guid.';文件:'.$this->txtpath . $textload.';使用字库:'.$traineddata, 'n', 'ParseMergeData', array('times'=> microtime()));
            system("cd ".$this->tesseractdata." && {$this->tesseract}tesseract ".$img_url." ". $this->txtpath . $textload . $guid ." -l {$traineddata}");
            $this->myLogs('结束图片识别', 'n', 'ParseMergeData', array('times'=> microtime()));
            
            $file_path = $this->txtpath . $guid .".txt";
            $orgdata = '';
            if (file_exists($file_path)) {
                $file_arr = file($file_path);
                for ($i = 0; $i < count($file_arr); $i++) {//逐行读取文件内容
                    $orgdata .= $file_arr[$i]."\n";    
                }
            }
            $data['orgdata'] = base64_encode($orgdata);
        }
        if(!empty($data['orgdata'])){
            $parseOrder = $this->getData(base64_decode($data['orgdata']));
            return [
                'data' => $data,
                'orderdata' => $parseOrder
            ];
            //print_r($data);exit;
        } else {
            return [
                'data' => $data,
                'orderdata' => []
            ];
        }
    }

    public function getData($file_arr=[]){
        //todo  根据配置$this->shopconf中的商户配置获取单独配置   然后解析成统一格式
        $file_arr = explode("\n", $file_arr);
        $data = array();
//        $rule = json_decode($this->shopconf['parse_order'],true);
//        foreach ($rule as $k=>$v){
//            
//        }
        foreach ($file_arr as $k => $v) {
            if (strpos($v, '单号') !== false) {
                $data['order_id'] = trim(mb_substr($v, strpos($v, '单号') + 2, 14)); // 订单号
            }
            if (strpos($v, '合 计') !== false) {
                $data['amount'] = number_format(trim(mb_substr($v, strpos($v, '合 计') + 8, 4)),2,".",""); // 合计
            }
            if (strpos($v, '付款方式') !== false) {
                $method = trim(mb_substr($file_arr[$k + 1], 0, 5));
                $method = !empty(trim($method)) ? $method : trim(mb_substr($file_arr[$k + 2], 0, 5)); // 付款方式
                $data['method'] = $method;
            }
            if (strpos($v, '日 期') !== false) {
                $data['times'] = trim(mb_substr($v, strpos($v, '日 期') + 5, 19)); // 日期
            }
        }
        return $data;
    }
    
    public function getKuMaoUrl($order_id,$amount,$method,$times){
//        print_r('运行监视：<br>');
        $publicKey = 'cTOf2L';
        $privateKey = '4cdc3f4c8697f2a7';
        $shopID = $this->shopconf['shopid'];
        $shopCode = $this->shopconf['shopcode'];
        if(!empty($shopCode)){
            $shopID = 0;
        }
        $shopID64 = $this->decb64($shopID);
        $posID = $this->shopconf['posid'];
        $money = $amount;
        $useTime = $times;//date('YmdHis',time());
        $useTime64 = $this->decb64($useTime);
        $seralNum = $order_id;
        $remark = '';
        $devShopID = $this->shopconf['devshopid'];
        $payType = $method;

        if(empty($posID)){
            //POSID为空，对私钥进行32位大写的MD5加密
            $key = strtoupper(md5($privateKey));
        }else{
            //POSID不为空，对私钥进行16位大写的MD5加密，对POSID进行16位大写的MD5加密
            $key = $this->md5to16($privateKey,1) . $this->md5to16($posID,1);
        }
//        print_r('MD5EncryptTo16(privateKey)：'.$this->md5to16($privateKey,1));
//        echo '<br>';
//        print_r('MD5EncryptTo16(posID)：'.$this->md5to16($posID,1));
//        echo '<br>';
//        print_r('key：'.$key);
//        echo '<br>';

        $param = "?z={$publicKey}|{$shopID}|{$posID}|{$money}|{$useTime}|{$seralNum}|{$shopCode}|{$remark}|{$devShopID}|{$payType}";
        $param_len = strlen($param);

        //加密字符串补位
        $amount_to_pad = 8-($param_len % 8);
        $pad_chr = chr(0);
        $tmp = "";
        for ($index = 0; $index < $amount_to_pad; $index++) {
            $tmp .= $pad_chr;
        }
        $param .= $tmp;
//        print_r('param：'.$param);
//        echo '<br>';

        $key1 = substr($key,0,8);//取前8位
        $iv = '';
        $str = openssl_encrypt($param, 'des-ecb', $key1, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);
        $sign = base64_encode($str);
//        print_r('sign：'.$sign);
//        echo '<br>';


        $sign16 = $this->md5to16($sign,1);
//        print_r('sign16：'.$sign16);
//        echo '<br>';
//        print_r('des.Key：'.$key1);
//        echo '<br>';


        $url = "http://p.m.mallcoo.cn/2/?z={$sign16}|{$publicKey}|{$shopID64}|{$posID}|{$money}|{$useTime64}|{$seralNum}|{$shopCode}|{$remark}|{$devShopID}|{$payType}";
//        print_r('url：'.$url);
        
        return $url;
    }

    function decb64($dec) { //10进制转换成64进制
	settype($dec,'double');
        if ($dec < 0) {
            return FALSE;
        }
        $map = array(
            0=>'A',1=>'B',2=>'C',3=>'D',4=>'E',5=>'F',6=>'G',7=>'H',8=>'I',9=>'J',
            10=>'K',11=>'L',12=>'M',13=>'N',14=>'O',15=>'P',16=>'Q',17=>'R',18=>'S',19=>'T',
            20=>'U',21=>'V',22=>'W',23=>'X',24=>'Y',25=>'Z',26=>'a',27=>'b',28=>'c',29=>'d',
            30=>'e',31=>'f',32=>'g',33=>'h',34=>'i',35=>'j',36=>'k',37=>'l',38=>'m',39=>'n',
            40=>'o',41=>'p',42=>'q',43=>'r',44=>'s',45=>'t',46=>'u',47=>'v',48=>'w',49=>'x',
            50=>'y',51=>'z',52=>'0',53=>'1',54=>'2',55=>'3',56=>'4',57=>'5',58=>'6',59=>'7',
            60=>'8',61=>'9',62=>'-',63=>'_',
        );
        $b64 = '';
        $i = 1;
        while ($dec >= 1) {
            if($dec > PHP_INT_MAX){
                $b64 = $map[bcmod($dec,64)] . $b64;
            }else{
                $b64 = $map[($dec % 64)] . $b64;
            }
            $dec /= 64;
            $i++;
            //$dec = bcdiv($dec,64);
        }
        return $b64;
    }
    
    function md5to16($str,$type = 0){
        if($type == 1){
            return substr(strtoupper(md5($str)),8,16);
        }else{
            return substr(md5($str),8,16);
        }
    }
}





/* 流水表
CREATE TABLE `print_data_log` (
    `id`  int NOT NULL AUTO_INCREMENT ,
    `pid`  int NOT NULL ,
    `order_id`  varchar(50) NULL ,
    `amount`  decimal NULL ,
    `ctime`  datetime NULL ,
    `menthod`  varchar(50) NULL ,
    `type`  int NOT NULL DEFAULT 0 ,
    `created`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    `mondified`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
*/