<?php
class PrintCtr_old {
    //解析包头信息
    public static function parse_head($data){
        $result = array();
        
        $result['HeadFlags'] = unpack('H*',substr($data, 0, 4));//HeadFlags  宽度为4字节固定头部0XEE66EE66
        $result['CmdCode'] = unpack('H*',substr($data, 4, 1));//CmdCode  指令
        $result['CmdBufLen'] = unpack('H*',substr($data, 5, 2));//CmdBufLen  指令参数缓冲区长度
        
        return $result;
    }

    //解析登录信息
    public static function parse_login($data){
        $result = array();
        
        $result['HalErrCode'] = unpack('H*',substr($data, 7, 4));//HalErrCode  硬件错误码
        $result['PrinterStatus'] = unpack('H*',substr($data, 11, 1));//PrinterStatus  打印机状态
        $result['TFStatusTF'] = unpack('H*',substr($data, 12, 1));//TFStatusTF 卡状态，暂时没用，预留以后扩展
        $result['CapType'] = unpack('H*',substr($data, 13, 1));//CapType 捕获类型，参考配置块中的CapType含义
        $result['FirmVer'] = substr($data, 14, 16);//FirmVer 固件版本信息，不足的补0
        $result['ModeName'] = substr($data, 30, 32);//ModeName 模块名称，PMM模块的名称
        $result['MacAddr'] = unpack('H*',substr($data, 62, 6));//MacAddr PMM的MAC地址
        $result['UUID'] = unpack('H*',substr($data, 68, 12));//UUID PMM的唯一ID
        
        return $result;
    }

    //解析打印信息
    public static function parse_print($data){
        $result = array();
        
        $result['RtcTime'] = unpack('H*',substr($data, 7, 6));//RtcTime  当前时间，暂时没用，全部为0
        $result['PackeId'] = unpack('H*',substr($data, 13, 4));//PackeId  数据包ID，每个数据包都有一个唯一的ID号，如果服务器收到了重复ID号的数据包，那可能是重传的数据包，应该过滤掉。这种情况只会在异常的时候出现
        $result['MisCtrl'] = unpack('H*',substr($data, 17, 1));//MisCtrl  杂项控制位，保留内部使用
        $result['DataPackeLen'] = unpack('H*',substr($data, 18, 2));//DataPackeLen  数据包长度，是指DataPackBuf中的有效数据长度
        $result['DataPackBuf'] = unpack('H*',substr($data, 20));//DataPackBuf  数据区，采集到的打印机数据。数据包最大长度为2048
        $tmplen = str_split($result['DataPackeLen'][1],2);
        $result['DataPackeLen_oct'] = base_convert($tmplen[1].$tmplen[0], 16, 10); //解析数据包长度为10进制
        $result['DataPackeTrueLen'] = strlen(substr($data, 20));//DataPackeTrueLen  数据区实际长度
        
        return $result; 
    }

    //解析AT信息
    public static function parse_at($data){
        $result = array();
        
        $result['atres'] = substr($data, 7);
        
        return $result;
    }

    //解析警告信息
    public static function parse_warning($data){
        $result = array();
        
        $result['PrinterStatus'] = unpack('H*',substr($data, 7, 1));//PrinterStatus  打印机状态为1表示在线，为0表示离线
        $result['Res'] = unpack('H*',substr($data, 8, 64));//Res
        
        return $result;
    }

    //打包回应信息
    public static function put_pack($head, $data){
        switch($head['CmdCode'][1]){
            case '01':
                //登录应答指令
                $CmdCode = pack('H2','81');
                //登录应答内容
                $packdata = pack('h12',date('ymdHis'));
                break;
            case '07':
                //数据传输指令
                $CmdCode = pack('H2','87');
                //统一回应成功
                $packdata = pack('h8',$data['PackeId'][1]).pack('H2','00');
                break;
            default:
                $packdata = null;
                break;
        }
        //其他不做应答
        if($packdata === null){
            return null;
        }
        //根据应答内容判断应答长度
        if(strlen($packdata) < '256'){
            $datalen = str_pad(strlen($packdata),4,'0');
        }else{
            $tmplen = str_pad(strlen($packdata),4,'0',STR_PAD_LEFT);
            $tmplen = str_split($tmplen,2);
            $datalen = $tmplen[1].$tmplen[0];
        }
        //应答时CmdCode指令为高位在前   CmdBufLen缓冲区长度为低字节在前
        $package = pack('h8',$head['HeadFlags'][1]).$CmdCode.pack('h4',$datalen).$packdata;//pack('H2',$head['CmdCode'][1])
        
        return $package;
    }
}
