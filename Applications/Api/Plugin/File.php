<?php 
namespace Api\Plugin;
class File {
    /**
     * 建立文件夹
     * @param string $path
     * @param number $chmod
     * @param bool $recursive
     */
    public static function mkdir($path, $chmod = 0777, $recursive = true) {
        mkdir($path, $chmod, $recursive);
        return true;
    }
}
?>