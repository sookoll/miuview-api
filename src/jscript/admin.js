/*
 * Miuview API admin
 * admin.js
 * 
 * Creator: Mihkel Oviir
 * 08.2011
 * 
 */

//random string
function rnd(){
	return String((new Date()).getTime()).replace(/\D/gi,'');
}

function postLogout(){
	$.post('request.php?'+rnd(),{
			c:'login',
			m:'setLogout'
		},
		function(response){
			if(response.status=='1'){ //if correct login detail
				window.location.reload(true);
			}
			else {
				alert('piip');
			}
		},
		'json'
	);
};

//layout height
function setDivHeight(){
	var initheight = $('#canvas').height();
	if(initheight<500) initheight=500;
	$('#sidebar').height(initheight-$('#header').height());
}

// sidebar content
var Gallery = new function(){
	
	this.album = '';
	this.item = '';
	this.previtem = '';
	this.nextitem = '';
	this.focus = false;
	
	this.check = function(){
		$.post('request.php?'+rnd(),{
				c:'main',
				m:'checkContent'
			},
			function(response){
				if(response.status!='0'){ //if correct login detail
					for(var i in response.data){
						if(response.data[i]!='' && response.data[i]!=null){
							$('#'+i).html(response.data[i]);
						}else{
							$('#'+i).html('Andmed korras');
						}
					}
					if(response.status=='1'){
						Gallery.disableButton('#check-gallery .submit');
					}else{
						Gallery.enableButton('#check-gallery .submit');
					}
				}
				else {
					alert('piip');
				}
			},
			'json'
		);
	};
	
	this.submitCheck = function(button){
		$(button).parent().find('.spinner').show();
		Gallery.disableButton(button);
		$.post('request.php?'+rnd(),{
				c:'main',
				m:'submitCheck'
			},
			function(response){
				if(response.status=='1'){ //if correct login detail
					$(button).parent().find('.spinner').hide();
					Gallery.enableButton(button);
					Gallery.check();
				}
				else {
					alert('piip');
				}
			},
			'json'
		);
	};
	
	this.loadGallery = function(){
		Gallery.reset();
		$('#organize > div').addClass('hide');
		$('#organize ul').sortable('destroy').html('');
		Gallery.disableButton('#gallery .submit');
		$.post('request.php?'+rnd(),{
				c:'main',
				m:'loadGallery'
			},
			function(response){
				if(response.status=='1'){ //if correct login detail
					$('#gallery ul').html(response.data);
					$('#gallery > input.skey').val(response.key);
					$('#gallery > input.url').val(response.url);
					$('#gallery').removeClass('hide');
					$('#gallery ul').sortable({
						placeholder: 'sortable-placeholder',
						opacity:0.8,
						items:'> li',
						handle:'.dragger',
						tolerance:'pointer',
						change:function(e,ui){
							Gallery.enableButton('#gallery .submit');
						}
					});
					$('#gallery ul > li textarea').keypress(function(e) {
						var KeyID = (window.event) ? event.keyCode : e.keyCode;
						if(KeyID != 37 && KeyID != 38 && KeyID != 39 && KeyID != 40){
							Gallery.enableButton('#gallery .submit');
						}
					});
					$('#gallery ul > li input[type=checkbox]').change(function(e) {
						Gallery.enableButton('#gallery .submit');
					});
				}
				else {
					alert('piip');
				}
			},
			'json'
		);
	};
	
	this.submitGallery = function(button){
		$(button).parent().find('.spinner').show();
		Gallery.disableButton(button);
		var params = {data:{}};
		var count = $('#gallery ul>li').size()-1;
		
		$('#gallery ul>li').each(function(i){
			params['data'][$(this).attr('id')] = {sort:count-i,name:$(this).find('textarea').val(),publc:$(this).find('input[type=checkbox]').attr('checked')}
		});
		$.extend(params,{c:'main',m:'submitGallery'});
		
		$.post('request.php?'+rnd(),params,
			function(response){
				if(response.status=='1'){ //if correct login detail
					$(button).parent().find('.spinner').hide();
					Gallery.loadGallery();
				}
				else {
					alert('piip');
				}
			},
			'json'
		);
	}
	
	this.loadAlbum = function(a){
		Gallery.reset();
		Gallery.album = a;
		$('#organize > div').addClass('hide');
		$('#organize ul').sortable('destroy').html('');
		Gallery.disableButton('#album .submit');
		$.post('request.php?'+rnd(),{
				c:'main',
				m:'loadAlbum',
				album:Gallery.album
			},
			function(response){
				if(response.status=='1'){ //if correct login detail
					$('#album ul').html(response.data);
					$('#album > h4').html(Gallery.album);
					$('#album > input.url').val(response.url);
					$('#album').removeClass('hide');
					$('#album ul').sortable({
						placeholder: 'sortable-placeholder',
						opacity:0.8,
						items:'> li',
						handle:'.dragger',
						tolerance:'pointer',
						change:function(e,ui){
							Gallery.enableButton('#album .submit');
						}
					});
					
					$('#album ul > li input[type=checkbox]').click(function() {
					    $('#album ul > li input[type=checkbox]').filter(':checked').not(this).removeAttr('checked');
					    Gallery.enableButton('#album .submit');
					});

				}
				else {
					alert('piip');
				}
			},
			'json'
		);
	};
	
	// submit album changes
	this.submitAlbum = function(button){
		$(button).parent().find('.spinner').show();
		Gallery.disableButton(button);
		var params = {data:{sort:{}}};
		var thumb = '';
		$('#album ul>li').each(function(i){
			params['data']['sort'][$(this).attr('id')] = i;
		});
		
		$('#album ul input[type=checkbox]').each(function(i){
			if($(this).attr('checked')){
				thumb = $(this).closest('li').attr('id');
			}
		});
		
		$.extend(params,{c:'main',m:'submitAlbum',album:Gallery.album,thumb:thumb})
		
		$.post('request.php?'+rnd(),params,
			function(response){
				if(response.status=='1'){ //if correct login detail
					$(button).parent().find('.spinner').hide();
					Gallery.loadAlbum(Gallery.album);
				}
				else {
					alert('piip');
				}
			},
			'json'
		);
	}
	
	// load item
	this.loadItem = function(i){
		if(Gallery.item == ''){
			$('#organize ul').sortable('destroy').html('');
		}
		Gallery.item = i;
		$('#organize > div').addClass('hide');
		Gallery.disableButton('#item .submit');
		$.post('request.php?'+rnd(),{
				c:'main',
				m:'loadItem',
				album:Gallery.album,
				item:Gallery.item
			},
			function(response){
				if(response.status=='1'){ //if correct login detail
					$('#item ul').html(response.data);
					$('#item').removeClass('hide');
					$('#item ul > li input.title,#item ul > li textarea').keypress(function(e) {
						var KeyID = (window.event) ? event.keyCode : e.keyCode;
						if(KeyID != 37 && KeyID != 38 && KeyID != 39 && KeyID != 40){
							Gallery.enableButton('#item .submit');
						}
					});
					Gallery.previtem = response.prev;
					Gallery.nextitem = response.next;
				}
				else {
					alert('piip');
				}
			},
			'json'
		);
	};
	
	// submit item changes
	this.submitItem = function(button){
		$(button).parent().find('.spinner').show();
		Gallery.disableButton(button);
		
		$.post('request.php?'+rnd(),{
				c:'main',
				m:'submitItem',
				album:Gallery.album,
				item:Gallery.item,
				title:$('#item ul > li input.title').val(),
				description:$('#item ul > li textarea').val()
			},
			function(response){
				if(response.status=='1'){ //if correct login detail
					$(button).parent().find('.spinner').hide();
					Gallery.loadItem(Gallery.item);
				}
				else {
					alert('piip');
				}
			},
			'json'
		);
	}
	
	this.enableButton = function(button){
		$(button).removeAttr('disabled');
	}
	this.disableButton = function(button){
		$(button).attr('disabled','disabled');
	}
	this.reset = function(){
		Gallery.item = '';
		Gallery.previtem = '';
		Gallery.nextitem = '';
	}
}

// tabs
var Tab = new function(){
	Gallery.reset();
	this.load = function(){
		var tab = $('#sidebar ul li a.active').attr('href');
		$('.page').addClass('hide');
		$('#'+tab).removeClass('hide');
		switch(tab){
			case 'check-gallery':
				Gallery.check();
			break;
			case 'organize':
				Gallery.loadGallery();
			break;
		}
	};
	
	this.change = function(tab){
		$('#sidebar ul li a').removeClass('active');
		$(tab).addClass('active');
		Tab.load();
	};
	
}

$(function() {
	
	// sidebar height
	setDivHeight();
	$(window).resize(function (){
		setDivHeight();
	});
	
	// open tab
	Tab.load();
	
	// change tab
	$('#sidebar ul li a').live('click',function(e){
		e.preventDefault();
		Tab.change(this);
	});
	
	// submit check
	$('#check-gallery .submit').live('click',function(e){
		e.preventDefault();
		Gallery.submitCheck(this);
	});
	
	// submit gallery
	$('#gallery .submit').live('click',function(e){
		e.preventDefault();
		Gallery.submitGallery(this);
	});
	
	// open album
	$('#gallery ul li a').live('click',function(e){
		e.preventDefault();
		Gallery.loadAlbum($(this).attr('href'));
	});
	
	// go back to gallery
	$('#album .back').live('click',function(e){
		e.preventDefault();
		Gallery.loadGallery();
	});
	
	// submit album
	$('#album .submit').live('click',function(e){
		e.preventDefault();
		Gallery.submitAlbum(this);
	});
	
	// open item
	$('#album ul li a').live('click',function(e){
		e.preventDefault();
		Gallery.loadItem($(this).attr('href'));
	});
	
	// next item
	$('#item ul li a').live('click',function(e){
		e.preventDefault();
		Gallery.loadItem($(this).attr('href'));
	});
	
	// go back to gallery
	$('#item .back').live('click',function(e){
		e.preventDefault();
		Gallery.loadAlbum(Gallery.album);
	});
	
	// submit item
	$('#item .submit').live('click',function(e){
		e.preventDefault();
		Gallery.submitItem(this);
	});
	
	// logout
	$('#logout-submit').click(function(e){
		e.preventDefault();
		postLogout();
	});

	$('textarea,input').live('focus',function(){
		Gallery.focus = true;
	}).live('blur',function(){
		Gallery.focus = false;
	});
	
	// navigate with keyboard
	$(window).keydown(function(e) {
		var KeyID = (window.event) ? event.keyCode : e.keyCode;
		if(Gallery.item!='' && Gallery.previtem!='' && Gallery.nextitem!='' && Gallery.focus == false){
			switch(KeyID){
				case 37:// left
					Gallery.loadItem(Gallery.previtem);
				break;
				case 38:// up
					
				break;
				case 39:// right
					Gallery.loadItem(Gallery.nextitem);
				break;
				case 40:// down
					
				break;
			}
		}
	});
});