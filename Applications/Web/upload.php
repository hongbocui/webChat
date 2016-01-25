<?php
	//$file = preg_replace('/data:image\/\w+;base64,/','',$_POST['image']);
	//file_put_contents('upload/1.jpg',base64_decode($file));
	//echo json_encode(array());

//print_r($_POST);
//die;
for($i=0; $i<count($_POST['image']); $i++) {
	$file_type = 'jpg';
	$file = preg_replace_callback('/data:image\/(\w+);base64,/',function($matches) use (&$file_type){
		$type = array('jpeg'=>'jpg','gif'=>'gif','png'=>'png');
		$file_type=$type[$matches[1]];
		return '';
	},$_POST['image'][$i]);
	file_put_contents('upload/'.time().mt_rand(1000,9999).$i.'.'.$file_type,base64_decode($file));
}
	echo json_encode(array('success'=>1));
