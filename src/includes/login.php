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

    public $output = array();

    // method to control login and set session variables
    public function setLogin(){
        global $func,$u,$p,$sess;
        $tmp = [
            'content' => [
                'status' => 0
            ]
        ];

        // user login
        if (isset($u, $p) && USER === urldecode($u) && PSWD === urldecode($p)) {
            // set the session
            $sess->miuview_admin_in = true;
            // set remember me cookie
            setcookie('remember_me', md5(USER), time()+60*60*24*30, '/');
            $tmp['content']['status'] = '1';
        }
        $tmp['content_type'] = 'json';
        $this->output = $tmp;
    }

    // method to control log out and unset session variables
    public function setLogout(){
        global $sess;
        $tmp = [
            'content' => [
                'status' => 0
            ]
        ];

        unset($sess->miuview_admin_in);
        setcookie("remember_me", "", time()-3600, '/');

        $tmp['content']['status'] = '1';
        $tmp['content_type'] = 'json';
        $this->output = $tmp;
    }

    // method to return arrays
    public function getResult() {
        return $this->output;
    }
}
