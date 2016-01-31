<?php
	namespace Api\Controler;
	class File extends Abstractex {

		public function doUpload() {
			$images = $this->toStr('image');
			for($i=0; $i<count($images); $i++) {
        			$file_type = 'jpg';
        			$file = preg_replace_callback('/data:image\/(\w+);base64,/',function($matches) use (&$file_type){
                			$type = array('jpeg'=>'jpg','gif'=>'gif','png'=>'png');
                			$file_type=$type[$matches[1]];
                			return '';
        			},$images[$i]);
				if(!file_exists('./upload/'.date('Ymd'))) {
					$this->doCreateDir('./upload/'.date('Ymd'));
				}
        		file_put_contents('upload/'.date('Ymd').'/'.time().mt_rand(1000,9999).$i.'.'.$file_type,base64_decode($file));
            }
			$this->_success('upload success');
		}
		public function doCreateDir($dir, $mode=0777) {
			if(!@mkdir($dir, $mode)) {
				$sundir = dirname($dir);
				self::doCreateDir($sundir, $mode);
			}
			@mkdir($dir, $mode);
		}

	}
