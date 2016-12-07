<?php 
    namespace Config;
    
    class Redis {
        /**
         * 服务器配置
         */
        public static  $server = array(
            'webChat' =>  array( #主服务，本业务的服务器redis
                'host' => 'localhost', #IP
                'port' => '6526',           #port
            ),
        );
    }
?>
