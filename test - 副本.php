<?php
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
print_r('运行监视：<br>');
$publicKey = 'cTOf2L';
$privateKey = '4cdc3f4c8697f2a7';
$shopID = 1016897;
$shopCode = 'L1049001';
if(!empty($shopCode)){
    $shopID = 0;
}
$shopID64 = decb64($shopID);
$posID = '';
$money = 10;
$useTime = '20180918174111';//date('YmdHis',time());
$useTime64 = decb64($useTime);
$seralNum = '158622421';
$remark = '';
$devShopID = '';
$payType = null;

if(empty($posID)){
	//POSID为空，对私钥进行32位大写的MD5加密
	$key = strtoupper(md5($privateKey));
}else{
	//POSID不为空，对私钥进行16位大写的MD5加密，对POSID进行16位大写的MD5加密
	$key = substr(strtoupper(md5($privateKey)),8,16) . substr(strtoupper(md5($posID)),8,16);
}
print_r('MD5EncryptTo16(privateKey)：'.substr(strtoupper(md5($privateKey)),8,16));
echo '<br>';
print_r('MD5EncryptTo16(posID)：'.substr(strtoupper(md5($posID)),8,16));
echo '<br>';
print_r('key：'.$key);
echo '<br>';

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
print_r('param：'.$param);
echo '<br>';

$key1 = substr($key,0,8);//取前8位
$iv 	= '';
$str	= openssl_encrypt($param, 'des-ecb', $key1, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);
$sign = base64_encode($str);
print_r('sign：'.$sign);
echo '<br>';


$sign16 = substr(strtoupper(md5($sign)),8,16);
print_r('sign16：'.$sign16);
echo '<br>';
print_r('des.Key：'.$key1);
echo '<br>';


$url = "http://p.m.mallcoo.cn/2/?z={$sign16}|{$publicKey}|{$shopID64}|{$posID}|{$money}|{$useTime64}|{$seralNum}|{$shopCode}|{$remark}|{$devShopID}|{$payType}";
print_r('url：'.$url);


//$res = openssl_decrypt(base64_decode('wkQA4Ffmlh8KycqM/ud8XPZgPOYvR9u0hr+X7pfQxpe8m/Qp+BLpkwIxiwuSSnf24920UIPwd0b2CUqZxCm34Q=='),'des-ecb', $key1, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);
//var_dump($res);
