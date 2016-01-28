<?php 
    /**
     * lastInsertId()、single($query = '',$params = null)、
     * row($query)、column($query)、query($query)
     */
    namespace Api\Model;
    abstract class Abstractex {
         //redis服务器
         protected static $redisServer = 'webChat';
        
         protected static function setSelectField($fields){
            return self::formatFiledsValue($fields);
         }
        
        /**
         * 设置insert条件
         */
        protected static function setInsertCondition($data){
            if(!is_array($data)) return false;
            $insertVals = self::formatFiledsValue($data, 1);
            $insertFileds = self::formatFiledsValue(array_keys($data));
            return array('fileds'=>$insertFileds, 'values'=>$insertVals);
        }
        
       
        
        /**
         * 设置update条件
         * array $data('filed'=>'value');
         * filed1='a',filed2='b',filed3='c'
         */
        protected static function setUpdateCondition($data){
            if($data){
                $str = '';
                $d = '';
                if(is_array($data)){
                    foreach($data as $k=>$v){
                        $str .= $d.$k."='".$v."'";
                        $d = ",";
                    }
                }else{
                    $str .= $data;
                }
            }
            return $str;
        }
        /**
         * 格式化数组
         * @param array $data
         * @param number $isvalue = 0: field1, field2, field2  $isvalue = 1: 'field1', 'field2', 'field2'
         * @return string
         */
        private static function formatFiledsValue($data, $isValue=0){
            $field = "";
            if(is_array($data) && $data){
                if($isValue){
                    $field = "'".join("','",$data)."'";
                }else{
                    $field = "`".join('`,`',$data)."`";
                }
            }else{
                if($isValue) return false;
                $field = " * ";
            }
            return $field;
        }
    }
?>