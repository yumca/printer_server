<?php
class PrintCtr
{
    //解析包头信息
    public static function parse_head($data)
    {
        $result = array();
        $result['HeadFlags'] = unpack('H*', substr($data, 0, 2)); //HeadFlags  宽度为2字节固定头部AA55
        $result['CmdCode'] = unpack('H*', substr($data, 2, 1)); //CmdCode  指令
        //        $result['CmdBufLen'] = unpack('H*',substr($data, 3, 2));//CmdBufLen  指令参数缓冲区长度

        return $result;
    }

    //解析地址信息
    public static function parse_addr($data)
    {
        $result = self::parse_head($data);
        $result['UUID'] = unpack('H*', substr($data, 3, 6)); //UUID  设备Mac编号

        return $result;
    }

    //解析打印信息
    public static function parse_print($data)
    {
        $result = self::parse_head($data);
        $result['UUID'] = unpack('H*', substr($data, 3, 6)); //UUID  设备Mac编号
        $result['DataPackeLen'] = unpack('H*', substr($data, 9, 4)); //DataPackeLen  数据包长度，是指DataPackBuf中的有效数据长度
        $result['DataPackeLen_oct'] = hexdec($result['DataPackeLen'][1]); //解析数据包长度为10进制   base_convert($result['DataPackeLen'], 16, 10);
        $result['DataPackBuf'] = unpack('H*', substr(substr($data, 13), 0, -2)); //DataPackBuf  数据区，采集到的打印机数据。
        $result['DataPackeTrueLen'] = strlen($result['DataPackBuf'][1]) / 2; //DataPackeTrueLen  数据区实际长度

        return $result;
    }

    //验证crc16
    public static function check_crc16($data)
    {
        $data_crc = unpack('H*', substr($data, -2)); //crc验证
        $cmdcode = unpack('H*', substr($data, 2, 1)); //CmdCode  指令
        $databuf = unpack('H*', substr(substr($data, 3), 0, -2)); //DataPackBuf
        $crc = self::get_crc16($cmdcode, $databuf);
        if ($crc != $data_crc) {
            return false;
        }

        return true;
    }

    //获取crc16
    public static function get_crc16($cmdcode64, $data = '')
    {
        $head = hex2bin('aa55'); //HeadFlags  宽度为2字节固定头部AA55
        $cmdcode = hex2bin($cmdcode64);
        $crcint = print_crc16($head . $cmdcode . $data);
        if ($crcint < '256') {
            $crc16 = str_pad(dechex($crcint), 4, '0');
        } else {
            $crc16 = dechex($crcint);
        }

        return $crc16;
    }

    //打包回应信息
    public static function put_pack($CmdCode, $data = '')
    {
        $head = 'AA55';
        switch ($CmdCode) {
            case '00':
                //登录查询
                $packdata = '00'; //pack('h2','00');
                //获取crc验证
                $crc = self::get_crc16($CmdCode, hex2bin('00'));
                break;
            case '01':
                //固件升级  保留
                $packdata = null;
                break;
            case '02':
                //工作模式设定
                //统一设置转发
                $packdata = '02'; //pack('H2','02');
                //获取crc验证
                $crc = self::get_crc16($CmdCode, hex2bin('02'));
                break;
            case '04':
                //追打二维码
                $data64 = bin2hex($data);
                $datalen = strlen($data);
                if ($datalen < '256') {
                    $datalen64 = str_pad(dechex($datalen), 4, '0', STR_PAD_LEFT);
                }
                $packdata = $datalen64 . $data64; //pack('H*',$datalen64.$data64);
                //获取crc验证
                $crc = self::get_crc16($CmdCode, hex2bin($datalen64) . $data);
                break;
            case '05':
            case '07':
                //主动请求数据
                $packdata = ''; //pack('H*','');
                //获取crc验证
                $crc = self::get_crc16($CmdCode);
                break;
            case '06':
                //设置设备mac编号
                if (strlen($data) == 12) {
                    $packdata = $data; //pack('H*',$data);
                } else {
                    $packdata = null;
                }
                //获取crc验证
                $crc = self::get_crc16($CmdCode, hex2bin($data));
                break;
            case '09':
                //                $data = 'https://www.baidu.com'; // wap url
                $errorCorrectionLevel = 'L'; //容错级别
                $matrixPointSize = 64; //生成图片大小
                $enc = QRencode::factory($errorCorrectionLevel, $matrixPointSize, 2);
                // $enc->eightbit = true;
                $qrdata = $enc->encode($data);
                $pad_str = str_pad('0', 25, '0');
                array_unshift($qrdata, $pad_str, $pad_str, $pad_str);
                array_push($qrdata, $pad_str, $pad_str, $pad_str, $pad_str);
                $qrdata2 = [];
                foreach ($qrdata as $v) {
                    $tmp = '';
                    $tmp = str_pad($v, 28, '0', STR_PAD_LEFT);
                    $tmp = str_pad($tmp, 32, '0', STR_PAD_RIGHT);
                    $i = 0;
                    $tmp_a = '';
                    for (; $i < strlen($tmp); $i++) {
                        $tmp_a .= str_pad($tmp[$i], 4, $tmp[$i]);
                    }
                    array_push($qrdata2, $tmp_a, $tmp_a, $tmp_a, $tmp_a);
                }
                $qrdata3 = '';
                foreach ($qrdata2 as $dv2) {
                    $i2 = 0;
                    for (; $i2 < strlen($dv2); $i2++) {
                        if ($dv2[$i2] == '1') {
                            $qrdata3 .= 'ff';
                        } else {
                            $qrdata3 .= '00';
                        }
                    }
                }

                $x = strlen($qrdata2[0]) - 1;
                $y = count($qrdata2) - 1;
                //                var_dump($x,$y,dechex($x),dechex($y));
                $pack_data = '00' . dechex($x) . '00' . dechex($y) . $qrdata3 . '1b4a' . dechex($y);
                $pack_len = str_pad(dechex(strlen($pack_data) / 2), 8, '0', STR_PAD_LEFT);
                $packdata = $pack_len . $pack_data;
                var_dump($packdata);
                $crc = self::get_crc16('09', hex2bin($packdata));
                break;
            case '0a':
                //追打字符创
                $data = mb_convert_encoding($data, "GB2312", "UTF-8");
                $data16 = '1b40' . bin2hex($data) . '0d0a1b5600';
                $datalen = strlen($data);
                if ($datalen < 256) {
                    $datalen16 = str_pad(dechex($datalen), 4, '0', STR_PAD_LEFT);
                }
                if ($datalen > hexdec('ffff')) {
                    $packdata = null;
                } else {
                    $packdata = $datalen16 . $data16;
                }
                //获取crc验证
                $crc = self::get_crc16($CmdCode, hex2bin($datalen16) . $data);
                break;
            default:
                $packdata = null;
                $crc = '';
                break;
        }
        //其他不做应答
        if ($packdata === null) {
            return false;
        }
        //数据组装
        // $package = $headpack.$CmdCodepack.$packdata.pack('H4',$crc);//substr($crc, 2,2).substr($crc, 0,2);;//pack('H2',$head['CmdCode'][1])
        if (!empty($packdata)) {
            $package = pack('H*', strtoupper($head . $CmdCode . $packdata . $crc)); //substr($crc, 2,2).substr($crc, 0,2);;//pack('H2',$head['CmdCode'][1])
        } else {
            $package = pack('H*', strtoupper($head . $CmdCode . $crc)); //substr($crc, 2,2).substr($crc, 0,2);;//pack('H2',$head['CmdCode'][1])
        }

        return $package;
    }
}
