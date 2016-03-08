<?php 
    namespace Api\Model;
    class Mcommon{
        /**
         * 单人聊天获取chatid
         * @param array $chatList 聊天人员 域账号数组
         * @return 一组对话的唯一的chatid
         */
        public static function singleChatId($chatList) {
            if(!$chatList || !is_array($chatList))
                return false;
            
            sort($chatList);
            return implode('--', $chatList);
        }
        /**
         * 群组聊天获取chatid
         */
        public static function groupChatId($master, $uuid) {
            if(!$master || !$uuid) return false;
            return $master.'-'.$uuid;
        }
        
        /**
         * 密码加密
         */
        public static function encryptPwd($pwd='', $salt='*^_^||') {
            if(!$pwd) return $pwd;
            $pwd = strrev($pwd);
            $code = floor(substr(ord(substr($pwd, -1)), -1)/2);
            if(strlen($pwd) > 2*$code) $pwd = substr($pwd, -$code).substr($pwd, $code, -$code).substr($pwd, 0, $code);
            return md5(md5($pwd).$salt);
        }
        /**
         * 判断某个字符串是否在某个文件中，
         * 存在 返回true
         * 不存在 返回false 并且将其写入
         */
        public static function isStrInFile($fileName, $strInFile) {
            if(!$fileName || !$strInFile) return false;
            $fpath = sys_get_temp_dir().'/webChat/'.$fileName;
            if(!file_exists($fpath)){
                $dir = dirname($fpath);
                if (!file_exists($dir)) {
                    \Api\Plugin\File::mkdir($dir) ? chmod($dir, 0777) : die('filesystem is not writable: ' . $dir);
                }
                file_put_contents($fpath, $strInFile);
                return false;
            }
        
            if(file_get_contents($fpath) !== $strInFile) {
                file_put_contents($fpath, $strInFile);
                return false;
            }else{
                return true;
            }
        }
    }
?>