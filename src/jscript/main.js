/*
 * Miuview API admin
 * main.js
 * 
 * Creator: Mihkel Oviir
 * 08.2011
 * 
 */

//random string
function rnd(){
	return String((new Date()).getTime()).replace(/\D/gi,'');
}

// login
function postLogin(){
	var u = $('#username').val();
	var p = $('#password').val();
	if(u!='' && p!=''){
		//check the username exists or not from ajax
		$.post('request.php?'+rnd(),{
				c:'login',
				m:'setLogin',
				u:u,
				p:p
			},
			function(response){
				if(response.status=='1'){ //if correct login detail
					window.location.reload(true);
				}else{
					$('#username').val('');
					$('#password').val('');
				}
			},
			'json'
		);
	}
}

$(function() {
	
	// login
	$('#password').keypress(function(e) {
		var KeyID = (window.event) ? event.keyCode : e.keyCode;
		if(KeyID == 13 || KeyID == 9){
			postLogin();
		}
	});
	
	$('#login-submit').click(function(e){
		e.preventDefault();
		postLogin();
    });
    
});