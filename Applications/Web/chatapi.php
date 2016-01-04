<?php 
    //自动加载类
    use \Workerman\Autoloader;
    require '../../Workerman/Autoloader.php';
    Autoloader::setRootPath("../");
    
    require '../Api/Apiconfig.php';
    $class = rtrim('Api\Controler\ ').ucfirst($_REQUEST['c']);
    $method= "do".ucfirst($_REQUEST['a']);
    $data = Api\ApiConfig::run($class, $method); 
?>