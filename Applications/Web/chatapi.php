<?php 
    //自动加载类
    use \Workerman\Autoloader;
    require '../../Workerman/Autoloader.php';
    Autoloader::setRootPath(__DIR__."/..");
    
    //class and method
    $class = rtrim('Api\Controler\ ').ucfirst($_REQUEST['c']);
    $method= "do".ucfirst($_REQUEST['a']);
    
    ApiConfig::run($class, $method); 
    
    class ApiConfig {
        /**
         * 执行API的方法
         */
        public static function run($class, $method){
            if($method === "do" || !$class) return false;
            if(!class_exists($class))
                exit('class '.$class.' is not found!');
            $obj = new $class();
            if(!method_exists($obj, $method))
                exit('class '.$class.' has not method '.$method.' ');
            return $obj->$method();
        }
    }
?>