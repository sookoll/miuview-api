<?php 
/*
 * Miuview API admin
 * Login class
 * 
 * Creator: Mihkel Oviir
 * 08.2011
 * 
 */

class login {
	
	var $output=array();
	
	// method to control login and set session variables
	public function setLogin(){
		global $func,$u,$p,$sess;
		$tmp['content']['status']='0';

		// user login
		if(isset($u) && isset($p)){
			if(USER === urldecode($u) && PSWD === urldecode($p)){
				// set the session
				$sess->miuview_admin_in = true;
				$tmp['content']['status']='1';
			}
		}
		$tmp['content_type'] = 'json';
		$this->output = $tmp;
	}
	
	// method to control log out and unset session variables
	public function setLogout(){
		global $sess;
		$tmp['content']['status']='0';
		
		unset($sess->miuview_admin_in);
		
		$tmp['content']['status']='1';
		$tmp['content_type'] = 'json';
		$this->output = $tmp;
	}
	
	// method to return arrays
	public function getResult() {
		return $this->output;
	}
}
?>