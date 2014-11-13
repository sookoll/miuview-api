<?php
/*
 * Miuview API
 * functions
 * 
 * Creator: Mihkel Oviir
 * 08.2011
 * 
 */

class Functions {
	
	# make db connection
	function connection() {
		mysql_connect(DB_HOST,DB_USER,DB_PWD) or die(mysql_error());
		mysql_select_db(DB_NAME) or die(mysql_error());
		$this->makeQuery("SET NAMES utf8");
		return true;
	}
	
	# close db connection
	function connection_close(){
		mysql_close();
	}
	
	# method to make query
	function makeQuery($q){
		$result = mysql_query($q) or die(mysql_error().': '.$q);
		return $result;
	}
	
	# move to url
	function gotourl($url) {
		if(empty($url)) $url = URL;
		header('Location: '.$url);
	}
	
	# current url
	function selfURL(){
		if(!isset($_SERVER['REQUEST_URI']))
			$serverrequri = $_SERVER['PHP_SELF'];
		else
			$serverrequri = $_SERVER['REQUEST_URI'];
		$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
		$protocol = strtolower($_SERVER["SERVER_PROTOCOL"]);
		$protocol = substr($protocol, 0, strpos($protocol, "/")).$s;
		$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
		return $protocol."://".$_SERVER['SERVER_NAME'].$port.$serverrequri;
	}
	
	# read file into variable
	function parseFile($page){
		$fd = fopen($page,'r');
		$page = @fread($fd, filesize($page));
		fclose($fd);
		return $page;
	}
	
	# parsing html to find php tags
	function replace_tags($page,$tags = array()) {
		$page=(@file_exists($page))? $this->parseFile($page):$page;
		if(sizeof($tags) > 0){
			foreach ($tags as $tag => $data) {
				$page = str_replace('{_'.$tag.'_}',$data,$page);
			}
		}
		return $page;
	}
	
	// find prev and next
	function array_navigate($array, $key) {
	    $keys = array_keys($array);
	    $index = array_flip($keys);
	    $r = array();
	    $r['prev'] = (isset($keys[$index[$key]-1])) ? $keys[$index[$key]-1] : end($keys);
		$r['next'] = (isset($keys[$index[$key]+1])) ? $keys[$index[$key]+1] : reset($keys);
	    return $r;
	}
	
	# definesarray
	function definesArray(){
		$data = array();
		$data['def-libs']=HTML_LIBS;
		$data['def-tmpl']=HTML_TMPL;
		$data['def-albums']=HTML_ALBUMS;
		
		return $data;
	}
	
	# remove directory
	function removedir($dirname){
		if (@is_dir($dirname))
			$dir_handle = opendir($dirname);
		if (!$dir_handle) return false;
		while($file = readdir($dir_handle)) {
			if ($file != '.' && $file != '..') {
				if (@is_file($dirname.'/'.$file)) unlink($dirname.'/'.$file);
				else $this->removedir($dirname.'/'.$file);
			}
		}
		closedir($dir_handle);
		rmdir($dirname);
		return true;
	}
	
	# remove empty subfolders
	function RemoveEmptySubFolders($path){
		$empty=true;
		if(file_exists($path)){
			$files = scandir($path);
			if(count($files)>2){
				foreach ($files as $file){
					if(file_exists($path.'/'.$file) && $file != '.' && $file != '..' && is_dir($path.'/'.$file)){
						if (!$this->RemoveEmptySubFolders($path.'/'.$file)) $empty=false;
					}else{
						$empty=false;
					}
				}
			}
			if ($empty) rmdir($path);
	  		return $empty;
		}
	}
	
	// determine item type
	function getType($item){
		if(@file_exists($item)){
			$ext = strtolower(substr($item, strrpos($item, '.') + 1));
			$types = unserialize(FORMATS);
			foreach($types as $key => $type){
				if(in_array($ext,$type)){
					return array('type'=>$key,'ext'=>$ext);
				}
			}
		}
		return false;
	}
	
		
	// get albums
	function getAlbums($album = null){
		$tmp = array();
		
		$q=$album==null?"SELECT * FROM ".TBL_ALBUMS." ORDER BY sort DESC":"SELECT * FROM ".TBL_ALBUMS." WHERE album='".$album."'";
		if($result = $this->makeQuery($q)){
			while($row = mysql_fetch_assoc($result)){
				$tmp[$row['album']] = $row;
			}
		}
		return $tmp;
	}
	
	// get items
	function getItems($album,$item=null,$start=null,$limit=null){
		$tmp = array();
		
		if($start!=null && $limit!=null)
			$l = " LIMIT ".$start.",".$limit;
		else
			$l = '';
		
		if($album=='*')
			$q=$item!=null?"SELECT * FROM ".TBL_ITEMS." WHERE item='".$item."'":"SELECT * FROM ".TBL_ITEMS." ORDER BY sort ASC".$l;
		else
			$q=$item!=null?"SELECT * FROM ".TBL_ITEMS." WHERE album='".$album."' AND item='".$item."'":"SELECT * FROM ".TBL_ITEMS." WHERE album='".$album."' ORDER BY sort ASC".$l;
		if($result = $this->makeQuery($q)){
			while($row = mysql_fetch_assoc($result)){
				$tmp[$row['album']][$row['item']] = $row;
			}
			return $tmp;
		}
		else return false;
	}
}

$func = new Functions();

?>