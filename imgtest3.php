<?php

include('AsyncdForMEC/System/Library/ParsePrintData.php');
$uuid = '1f0043000c51363239393738';
$ParsePrintData = new ParsePrintData($uuid);
//var_dump('start：', microtime());
$print_data = $ParsePrintData->DbPdo->getAll("select * from print_org where uuid = '{$uuid}'");
$uuid2 = $ParsePrintData->DbPdo->getAll("select * from print_org where meccode = '{$argv[1]}'");
if($uuid2){
    foreach($print_data as $v){
        $data = json_decode($v['data'],true);
        $tmpctime = explode(' ',$data['ctime']);
        $tmpctime[0] = substr($tmpctime[0],2);
        $ctime = $tmpctime[1].'.'.$tmpctime[0];
        $ParsePrintData->redis->addHashval('printdata_'.$uuid, 'P'.$ctime, $data);
        $ParsePrintData->redis->addHashval('printuuid', $uuid, floatval($ctime));
    }
}

//var_dump('end：', microtime());
//$ParsePrintData = new ParsePrintData($data['uuid']);
//$result = $ParsePrintData->upDataToQrcode();