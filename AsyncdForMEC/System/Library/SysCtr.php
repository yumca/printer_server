<?php
class SysCtr {
    function enCmdCode($code){
        if($code['stat'] != 'sys'){
            return $this->result();
        }
        $sendinfo=pack('H8','66ee66ee');
        switch($code['cmdcode']){
            case 'check_online':
                $sendinfo.= pack('H2','04');
                $data = pack('H*',bin2hex('AT+GUUID')).pack('H4','0d0a');
//                $data='AT+GUUID'.pack('H4','0d0a');
                break;
            default:
                $data='';
                break;
        }
        if(empty($data)){
            return $this->result();
        }
        if(strlen($data) < '256'){
            $datalen = str_pad(strlen($data),4,'0');
        }else{
            $tmplen = str_pad(strlen($data),4,'0',STR_PAD_LEFT);
            $tmplen = str_split($tmplen,2);
            $datalen = $tmplen[1].$tmplen[0];
        }
        $sendinfo.= pack('h4',$datalen);
        $sendinfo.=$data;
        return $this->result('yes',$sendinfo);
    }

    function result($send = 'no', $sendinfo = ''){
        return array(
            'send' => $send,
            'sendinfo' => $sendinfo
        );
    }
}
