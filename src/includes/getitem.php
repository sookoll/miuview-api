<?php 
/*
 * Miuview API
 * getalbum class
 * 
 * Creator: Mihkel Oviir
 * 08.2011
 * 
 */

class getitem {
	
	var $result;
	var $album;
	var $size;
	var $thsize;
	var $skey;
	
	public function __construct(){
		global $func,$album,$item,$size,$thsize,$key,$start,$limit;
		
		$this->size = $size?$size:ITEM_SIZE;
		$this->thsize = $thsize?$thsize:TH_SIZE;
		
		$this->skey = $key;
		
		$aresult = array();
		$iresult = array();
		if(!empty($album) && !empty($item)){
			$albums = explode(',',$album);
			$items = explode(',',$item);
			
			if(count($albums)>1){
				foreach($albums as $a){
					$aresult = array_merge($aresult,$func->getAlbums($a));
					if(count($items)>1){
						foreach($items as $i){
							$iresult = array_merge_recursive($iresult,$func->getItems($a,$i));
						}
					} else {
						if($item == '*'){
							$iresult = array_merge_recursive($iresult,$func->getItems($a,null,$start,$limit));
						} else {
							$iresult = array_merge_recursive($iresult,$func->getItems($a,$item));
						}
					}
				}
			} else {
				if($album == '*'){
					$aresult = $func->getAlbums();
				} else {
					$aresult = $func->getAlbums($album);
				}
				if(count($items)>1){
					foreach($items as $i){
						$iresult = array_merge_recursive($iresult,$func->getItems($album,$i));
					}
				} else {
					if($item == '*'){
						$iresult = $func->getItems($album,null,$start,$limit);
					} else {
						$iresult = $func->getItems($album,$item);
					}
				}
			}
			//print_r($result);
			$this->result = $this->formatResult($aresult,$iresult);
			$this->output();
		} else
			die('Parameter album and item not set');
	}
	
	private function formatResult($ar,$ir){
		global $func;
		$data = array();
		
		$data['query'] = $func->selfURL();
		$i=0;
		foreach($ir as $album => $items){
			if($ar[$album]['public']==1 || $this->skey==md5(SECURITY_KEY)) {
				foreach($items as $item){
					$data['items'][$i]['id']=$item['item'];
					$data['items'][$i]['album']=$item['album'];
					$data['items'][$i]['title']=$item['title'];
					$data['items'][$i]['description']=$item['description'];
					$data['items'][$i]['type']=$item['type'];
					$data['items'][$i]['order']=$i;
					$data['items'][$i]['created']=$item['added'];
					if($item['metadata']!=''){
						$data['items'][$i]['metadata'] = json_decode($item['metadata'],true);
					}
					$data['items'][$i]['thumb_url']=URL.'?request=getimage&album='.$album.'&item='.$item['item'].'&size='.$this->thsize.'&mode=square';
					$data['items'][$i]['img_url']=URL.'?request=getimage&album='.$album.'&item='.$item['item'].'&size='.$this->size.'&mode=longest';
					$data['items'][$i]['query']=URL.'?request=getitem&album='.$album.'&item='.$item['item'];
					$i++;
				}
			}
		}
		$data['items_count'] = $i;
		
		return json_encode($data);
	}

	private function c($i=null){
		return $i!=null?$i:'';
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