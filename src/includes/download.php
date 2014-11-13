<?php 
/*
 * Miuview API
 * download class
 * 
 * Creator: Mihkel Oviir
 * 08.2011
 * 
 */

class download {
	
	public function __construct(){
		global $func,$album,$item;
		
		if(!empty($album) && !empty($item) && file_exists(PATH_ALBUMS.$album.'/'.$item) && $i=$func->getType(PATH_ALBUMS.$album.'/'.$item)){
			$is = getimagesize(PATH_ALBUMS.$album.'/'.$item);
			$this->downloadFile(PATH_ALBUMS.$album.'/',$item,$is['mime']);
		}
	}
	
	// Return the requested graphic file to the browser
	private function downloadFile($path,$file,$type){
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename=$file");
		header("Content-Type: Content-type: $type");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".filesize($path.$file));
		readfile($path.$file);
	}
	
}
?>