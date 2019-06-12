<?php

return array(
    'dev' => array(
        //测试线
        'db_local' => array(
            'DB_HOST' => '27.17.1.18',
            'DB_USER' => '',
            'DB_PWD' => '',
            'DB_PORT' => '3306',
            'DB_PREFIX' => '',
            'DB_NAME' => 'print',
        ),
        //Redis设置
        'redis' => array(
            'REDIS_SERVER' => '127.0.0.1',
            'REDIS_POST' => '6379',
            'REDIS_KEY_PREFIX' => 'pr_',
            //'pwd' => 'I8rIurQOYvVq3',
        ),
        'tesseractdata' => '/usr/share/tesseract/tessdata/',  //图片识别程序识别数据的绝对路径
        'tesseract' => '/usr/bin/',  //图片识别程序的绝对路径
        'imgpath' => '/home/www/print/',  //打印数据图片存储绝对路径
        'txtpath' => '/home/www/print/txt/',  //图片识别内容存储绝对路径
        'host_dir' => [
            '10.0.15.203' => '/home/www/htdocs/print/System/www',
            '192.168.205.129' => '/data/www/print/System/www',
            '27.17.1.18' => '/home/www/print/System/www',
            '192.168.2.12' => '/home/www/print/System/www'
        ],
        'site_url' => '',
        'bd_api' => 
        [
            [
                'bd_appid' => '14649041',
                'bd_apikey' => 'xZGhaOeh1rkMj1iQqVREf5L0',
                'bd_secretkey' => 'XSqGf4f8WaGYy5ItvQ93XpbazKvaWzzG',
                'ip' => ['CLIENT-IP: 58.49.78.112','X-FORWARDED-FOR: 58.49.78.112'],
            ],
            [
                'bd_appid' => '14661299',
                'bd_apikey' => 'gdo6GatzaiPGZRx2GIe92Rau',
                'bd_secretkey' => 'CKDkLZUgEyWewgxGK37hGZSC2s80AMFV',
                'ip' => ['CLIENT-IP: 58.49.78.142','X-FORWARDED-FOR: 58.49.78.142'],
            ],
        ],
    ),
    'test' => array(
        //预上线
        
    ),
    'pro' => array(
        //正式线
        
    )
);
