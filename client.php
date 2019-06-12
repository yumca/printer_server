<?php
$ch = curl_init();
$jiaRes = 'meccode=L12';
curl_setopt($ch, CURLOPT_URL,'http://127.0.0.1:8080/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jiaRes);
$res = curl_exec($ch);
// grab URL, and print    
if(curl_errno($ch)){
    print curl_error($ch);
    exit;
}
curl_close($ch);
$res = json_decode($res,true);
if($res['code'] != 200){
    print_r($res['msg']);
    echo("\n");
    exit;
}
print_r('登录token：'.$res['token']);
echo("\n");
print_r('登录店铺名：'.$res['shop_name']);
echo("\n");


$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
$client->on("connect", function(swoole_client $cli) use($res) {
    $sendinfo = json_encode(array('msg_type'=>'login','token'=>$res['token']));
    $cli->send($sendinfo);
});
$client->on("receive", function(swoole_client $cli, $data){
    echo "Receive: {$data}\n";
    //$cli->send(str_repeat('A', 100)."\n");
    //sleep(1);
});
$client->on("error", function(swoole_client $cli){
    echo "error\n";
});
$client->on("close", function(swoole_client $cli){
    echo "Connection close\n";
});
$client->connect('127.0.0.1', 8082);