<?php
class Module_Common_Common
{
	public static function getUserAgent()
	{
		$userAgents = array(
			//"Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)",
			//"Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)",
			//"Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)",
			'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:25.0) Gecko/20100101 Firefox/25.0',
		);
		return $userAgents[array_rand($userAgents)];
	}

	/*
	$params = array(
		'url' => $url,
		'query' => array(),
		'proxy' => $proxy,
		'is_http_proxy' => 1,
		'cookie_file' => $cookieFile,	
		'is_post' => 1,
		'is_debug' => 0,
		'user_agent' => '',
		'header' => array(),
	);
	*/
	public static function sendData($p)
	{
		$sURL 	= isset($p['url']) && $p['url'] ? trim($p['url']) : '';
		$query 	= isset($p['query']) && $p['query'] ? trim($p['query']) : '';;
		$proxy 	= isset($p['proxy']) && $p['proxy'] ? trim($p['proxy']) : '';
		$isHttpProxy = isset($p['is_http_proxy']) && $p['is_http_proxy'] ? $p['is_http_proxy'] : 0;
		$userAgent = isset($p['user_agent']) && $p['user_agent'] ? trim($p['user_agent']) : self::getUserAgent();
		$cookieFile = isset($p['cookie_file']) && $p['cookie_file'] ? trim($p['cookie_file']) : '';
		$isPost = isset($p['is_post']) && $p['is_post'] ? $p['is_post'] : 0;
		$isDebug = isset($p['is_debug']) && $p['is_debug'] ? $p['is_debug'] : 0;
		$header = isset($p['header']) && $p['header'] ? $p['header'] : array();

		$sQuery = $query ? http_build_query($query) : '';
		if(!$isPost && $sQuery) {
			$sURL = $sURL . "?" . $sQuery;	
		}

		$defaultHeader = array(
			"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
			"Accept-Language: zh-cn,zh;q=0.8,en-us;q=0.5,en;q=0.3",
			"Connection: keep-alive",
			"Cache-Control: max-age=0",
		);
		$header = array_merge($defaultHeader, $header); 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 			 $sURL);
		curl_setopt($ch, CURLOPT_HEADER, 		 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER,	 $header);
							    
		//curl_setopt($ch, CURLINFO_HEADER_OUT,	 1); // 影响CURLOPT_VERBOSE
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 	 $userAgent);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 		 30);
		curl_setopt($ch, CURLOPT_VERBOSE,   	 $isDebug);

		//curl_setopt($ch, CURLOPT_COOKIESESSION, true);  //不可用, 否则出问题
		if($isPost) {
			curl_setopt($ch, CURLOPT_POST, 			1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, 	$sQuery);
		}

		if ($proxy) {
			//curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);// 不可用，否则出问题
			curl_setopt($ch, CURLOPT_PROXY, 	$proxy);
			//curl_setopt($ch, CURLOPT_PROXYPORT, $proxyPort);
			if($isHttpProxy) {
				curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
			} else {
				curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
			}
		}

		if($cookieFile) {
			if (file_exists($cookieFile)) {
				curl_setopt($ch, CURLOPT_COOKIEFILE, 	$cookieFile); 
			} else {
				curl_setopt($ch, CURLOPT_COOKIEJAR,		$cookieFile);
				curl_setopt($ch, CURLOPT_COOKIEFILE, 	$cookieFile); 
			}
		}

		$sFetchResult = curl_exec($ch);
		if (0 != curl_errno($ch)) {
			curl_close ( $ch );
			return false;
		}
		curl_close ( $ch );
		return $sFetchResult;
	}
}
