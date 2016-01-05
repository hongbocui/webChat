<?php 
    namespace Api\Controler;
    abstract class Abstractex {
        
        /**
         * 格式化REQUEST数值数据
         *
         * @return Int
         */
        public function toInt() {
            $args = func_get_args();
            $argsNum = func_num_args();
            if ($argsNum)
            {
                $ret = array();
                foreach ($args as $arg)
                    $ret[] = isset($_REQUEST[$arg]) ? intval($_REQUEST[$arg]) : 0;
                return ($argsNum == 1) ? $ret[0] : $ret;
            }
            else
                return 0;
        }
        /**
         * 格式化REQUEST字符串数据
         *
         * @param String $feild
         * @return String(Array)
         */
        public function toStr($feild, $dropHtml = true) {
            if (isset($_REQUEST[$feild])) {
                $val = $_REQUEST[$feild];
                if (is_array($val))
                    return $val;
                $val = strval($val);
                if ($dropHtml == true)
                    // return trim(strip_tags($val));
                    return trim(htmlspecialchars($val));
                return $val;
            }
            return '';
        }
    }
?>