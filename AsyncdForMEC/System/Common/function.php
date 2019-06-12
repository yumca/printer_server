<?php

// +----------------------------------------------------------------------
// | 常用函数库
// +----------------------------------------------------------------------
/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @return string
 */

/**
 * 生成itemId
 * @return string
 */
function getItemId() {
    $hour = date('z') * 24 + date('H');
    $hour = str_repeat('0', 4 - strlen($hour)) . $hour;
    //	echo date('y') . $hour . PHP_EOL;
    return date('y') . $hour . getRandNumber(10);
}

//返回秒随机号
function getMillisecond() {
    return (string) time() . (string) mt_rand(10000000, 99999999);
}

/**
 * 生成固定长度的随机数
 *
 * @param int $length
 * @return string
 */
function getRandNumber($length = 6) {
    $num = '';
    if ($length >= 10) {
        $t = intval($length / 9);
        $tail = $length % 9;
        for ($i = 1; $i <= $t; $i ++) {
            $num .= substr(mt_rand('1' . str_repeat('0', 9), str_repeat('9', 10)), 1);
        }
        $num .= substr(mt_rand('1' . str_repeat('0', $tail), str_repeat('9', $tail + 1)), 1);
        return $num;
    } else {
        return substr(mt_rand('1' . str_repeat('0', $length), str_repeat('9', $length + 1)), 1);
    }
}

//输出文字
function trace($info, $type = 'i') {
    $isLinux = php_uname('s') === 'Linux' ? true : false;
    if ($isLinux) {
        if ($type == 'e') {//错误
            //红底黄字
            $cmd = "echo -ne \"\033[41;33m" . $info . " \033[0m\n\"";
        } else if ($type == 'w') {//警告
            $cmd = "echo -ne \"\033[47;31m" . $info . " \033[0m\n\"";
        } else if ($type == 'n') {//备注
            $cmd = "echo -ne \"\033[33m" . $info . " \033[0m\n\"";
        } else {
            //绿色
            $cmd = "echo -ne \"\033[32m" . $info . " \033[0m\n\"";
        }
        $return = exec($cmd);
        echo "$return" . "\n";
    } else {
        echo "$info" . "\n";
    }
}

//日期格式检测
function checkDateFormat($date) {
    //匹配日期格式
    if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $date, $parts)) {
        //检测是否为日期
        if (checkdate($parts[2], $parts[3], $parts[1]) || $parts[3] == '00')
            return $parts;
        else
            return false;
    } else
        return false;
}

/**
 * 模拟post/get进行url请求
 *
 * @since 2016-06-03 Fri 09:37:53
 * @author PHPJungle
 * @param string $url
 * @param mix $param [array or string]
 * @param bool $is_post [default:post ,false:get]
 * @return string
 * @abstract <pre>
 *      方法说明:为了保证和以前使用方法兼容，故将$is_post默认值为true,如果需要get请求，将其置为false即可
 */
function request_post($url = '', $param = '', $is_post = true, $header = []) {
    $url = trim($url);
    if (empty($url)) {
        return false;
    }
    $queryStr = '';
    if (is_array($param)) {
        foreach ($param as $k => $v) {
            $v = trim($v);
            if ('' === $v)
                unset($param[$k]);
        }
        $queryStr = http_build_query($param); # 代码优化，减少网络开支
    }else {
        $queryStr = trim($param);
    }
    $ch = curl_init(); //初始化curl
    curl_setopt($ch, CURLOPT_HEADER, $header); //设置header
    if(!empty($header)){
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //设置header
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_TIMEOUT, 8); //执行超时时间 秒
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8); //链接超时时间 秒
    if ($is_post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $queryStr);
    } else {
        empty($queryStr) or $url .= '?' . $queryStr;
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch); //运行curl
    curl_close($ch);
    return $data;
}

/**
 * curl
 */
function curl_file_get_contents($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true); // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
}

/**
 * 生成logPId
 * @return string
 */
function getlogPid()
{
    return date('His') . getRandNumber(4);
}

/**
 * 生成guid
 * @return string
 */
function create_guid() {
    $charid = strtoupper(md5(uniqid(mt_rand(), true)));
    $hyphen = chr(45);// "-"
    $uuid = substr($charid, 0, 8).$hyphen
        .substr($charid, 8, 4).$hyphen
        .substr($charid,12, 4).$hyphen
        .substr($charid,16, 4).$hyphen
        .substr($charid,20,12);
    return $uuid;
}