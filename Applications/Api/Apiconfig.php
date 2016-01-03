<?php 
    namespace Api;
    class ApiConfig {
        /**
         * 执行API的方法
         */
        public static function run($class, $method){
            if($method === "do" || !$class) return false;
            $obj = new $class();
            return $obj->$method();
        }
    }
?>