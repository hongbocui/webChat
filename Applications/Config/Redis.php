<?php 
    namespace Config;
    
    class Redis {
        /**
         * 服务器配置
         */
        public static  $server = array(
            'webChat' =>  array( #主服务，本业务的服务器redis
                'host' => '172.31.152.132', #IP
                'port' => '6379'           #port
            ),
        );
    }
?>