<?php 
/*
 * Miuview API
 * getalbum class
 * 
 * Creator: Mihkel Oviir
 * 08.2011
 * 
 */

class getalbum {
	
	var $result;
	var $thsize;
	var $skey;
	
	public function __construct(){
		global $func,$album,$thsize,$key;
		
		$this->thsize = $thsize?$thsize:100;
		
		$this->skey = $key;
		
		$result = array();
		if(!empty($album)){
			$albums = explode(',',$album);
			if(count($albums)>1){
				foreach($albums as $a){
					$result = array_merge($result,$func->getAlbums($a));
				}
			} else {
				if($album == '*'){
					$result = $func->getAlbums();
				} else {
					$result = $func->getAlbums($album);
				}
			}
			//print_r($result);
			$this->result = $this->formatResult($result);
			$this->output();
		} else
			die('Parameter album not set');
	}
	
	private function formatResult($r){
		global $func;
		$data = array();
		
		$data['query'] = $func->selfURL();
		$i=0;
		foreach($r as $album){
			if($album['public']==1 || $this->skey==md5(SECURITY_KEY)) {
				$items = $func->getItems($album['album']);
				$data['albums'][$i]['id']=$album['album'];
				$data['albums'][$i]['title']=$album['title'];
				$data['albums'][$i]['thumb']=$album['thumb'];
				$data['albums'][$i]['thumb_url']=($album['thumb']==null || $album['thumb']=='')?'':URL.'?request=getimage&album='.$album['album'].'&item='.$album['thumb'].'&size='.$this->thsize.'&mode=square';
				$data['albums'][$i]['order']=$i;
				$data['albums'][$i]['query']=URL.'?request=getitem&album='.$album['album'].'&item=*';
				$data['albums'][$i]['items_count'] = count($items[$album['album']]);
				$i++;
			}
		}
		$data['albums_count'] = $i;
		
		return json_encode($data);
	}
	
	private function output(){
		if($_GET['callback']){
			header('Content-Type: text/json');
			echo $_GET['callback'].'('.$this->result.');';
		}else{
			header('Content-Type: text/html');
			echo $this->result;
		}
	}

}
?>