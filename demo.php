<?php
/**
 * 猫酷开放平台接入示例
 * Class Mallcoo
 * @package tool\mallcoo
 */ 
class Mallcoo
{
    public $m_Mallid = '';
    public $m_AppID = '';
    public $m_PublicKey = '';
    public $m_PrivateKey = '';
 
    /**
     * 初始化
     *
     * @param string $sMallid Mallid
     * @param string $sAppID AppID
     * @param string $sPublicKey 公钥
     * @param string $sPrivateKey 私钥
     */
    public function __construct($sMallid = '', $sAppID = '', $sPublicKey = '', $sPrivateKey = ''){
        $this->m_Mallid = $sMallid?:'武汉摩尔城POS';
        $this->m_AppID = $sAppID?:'5b99fa313ae74e3280dd88d0';
        $this->m_PublicKey = $sPublicKey?:'cTOf2L';
        $this->m_PrivateKey = $sPrivateKey?:'4cdc3f4c8697f2a7';
    }
 
    /**
     * 获取授权url
     *
     * @param string $sCallbackUrl 回调url
     * @return string
     */
    public function getOauthUrl($sCallbackUrl){
        $sUrl = 'https://m.mallcoo.cn/a/open/User/V2/OAuth/CardInfo/';
        $sUrl .= '?AppID='.$this->m_AppID.'&PublicKey='.$this->m_PublicKey.'&CallbackUrl='.urlencode($sCallbackUrl);
        return $sUrl;
    }
 
    /**
     * 通过 Ticket 获取 Token
     *
     * @param string $sTicket Ticket
     * @return
     */
    public function GetTokenByTicket($sTicket){
        $sUrl = 'https://openapi10.mallcoo.cn/User/OAuth/v1/GetToken/ByTicket/';
        return $this->mallcooPost($sUrl, array('Ticket' => $sTicket));
    }
    
    /**
     * 获取商家列表
     *
     * @param string $sTicket Ticket
     * @return
     */
    public function GetShopList($aPostData){
        $sUrl = 'https://openapi10.mallcoo.cn/Shop/V1/GetList/';
        echo '获取商城商家列表，获取链接：'.$sUrl.'<br>';
        return $this->mallcooPost($sUrl, $aPostData);
    }
 
    /**
     * mallcoo post 请求
     *
     * @param string $sUrl 请求url
     * @param array $aPostData 请求参数
     * @return array
     */
    private function mallcooPost($sUrl, $aPostData){
        $sPostData = json_encode($aPostData);
        $nTimeStamp = date('YmdHis',time());
        $sS = "{publicKey:".$this->m_PublicKey.",timeStamp:".$nTimeStamp.",data:".$sPostData.",privateKey:".$this->m_PrivateKey."}";
        echo '参数：'.$sS.'<br>';
        $sSign = strtoupper(substr(md5($sS), 8, 16));
        $aHeader = array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($sPostData),
            'AppID: '.$this->m_AppID,
            'TimeStamp: '.$nTimeStamp,
            'PublicKey: '.$this->m_PublicKey,
            'Sign: '.$sSign,
        );
        $sR = $this->curl_post($sUrl, $aHeader, $sPostData);
        return json_decode(html_entity_decode($sR), true);
    }
 
    /**
     * curl post 请求
     *
     * @param string $url
     * @param array $aHeader
     * @param string $sParams
     * @param string $cookie
     * @return string
     */
    private function curl_post($url, $aHeader, $sParams, $cookie='') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sParams);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        if ($result === false){
            // log curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }
}

$m = new Mallcoo();
$list = $m->GetShopList([
  "PageIndex" => 1,
  "PageSize" => null,
  "FloorID" => null,
  "CommercialTypeID" => null
]);
echo '<pre>';
print_r($list);