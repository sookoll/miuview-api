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

// sidebar content
var Gallery = new function(){

    this.album = null;
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

    this.submitCheck = function(button, cb){
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
                    if(typeof cb === 'function') {
                        cb();
                    }
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
        //$('#organize ul').sortable('destroy').html('');
        Gallery.disableButton('#gallery .submit');
        $.post('request.php?'+rnd(),{
                c:'main',
                m:'loadGallery'
            },
            function(response){
                if(response.status=='1'){ //if correct login detail
                    $('#gallery ul').html(response.data);
                    $('#gallery input.skey').val(response.key);
                    $('#gallery input.url').val(response.url);
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
                    $('#gallery ul > li textarea').on('keyup', function(e) {
                        Gallery.enableButton('#gallery .submit');
                    });
                    $('#gallery ul > li input[type=checkbox]').on('change', function(e) {
                        Gallery.enableButton('#gallery .submit');
                    });
                }
                else {
                    alert('piip');
                }
            },
            'json'
        );
        this.enableUploader($('#fileupload1'));
    };

    this.submitGallery = function(button){
        $(button).parent().find('.spinner').show();
        Gallery.disableButton(button);
        var params = {data:{}};
        var count = $('#gallery ul>li').size()-1;

        $('#gallery ul>li').each(function(i){
            params['data'][$(this).attr('id')] = {
                sort:count-i,
                name:$(this).find('textarea').val(),
                publc:$(this).find('input[type=checkbox]').prop('checked')
            }
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
    };
    
    // delete album
    this.deleteAlbum = function (id, cb) {
        $.post('request.php?'+rnd(),{
                c: 'main',
                m: 'deleteAlbum',
                album: id
            },
            function(response){
                if(response.status=='1'){
                    if (typeof cb === 'function') {
                        cb();
                    }
                }
                else {
                    alert('piip');
                }
            },
            'json'
        );
    };

    this.loadAlbum = function(a){
        Gallery.reset();
        Gallery.album = a;
        $('#organize > div').addClass('hide');
        //$('#organize ul').sortable('destroy').html('');
        Gallery.disableButton('#album .submit');
        $.post('request.php?'+rnd(),{
                c:'main',
                m:'loadAlbum',
                album:Gallery.album
            },
            function(response){
                if(response.status=='1'){ //if correct login detail
                    $('#album ul').html(response.data);
                    $('#album > h4').html('Album: ' + response.title + ' (' + Gallery.album + ')');
                    $('#album input.url').val(response.url);
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
                        $('#album ul > li input[type=checkbox]').filter(':checked').not(this).prop('checked', false);
                        Gallery.enableButton('#album .submit');
                    });

                }
                else {
                    alert('piip');
                }
            },
            'json'
        );
        
        this.enableUploader($('#fileupload2'));
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
            if($(this).prop('checked')){
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
    };
    
    // delete item
    this.deleteItem = function (id, cb) {
        $.post('request.php?'+rnd(),{
                c: 'main',
                m: 'deleteItem',
                album: Gallery.album,
                item: id
            },
            function(response){
                if(response.status=='1'){
                    if (typeof cb === 'function') {
                        cb();
                    }
                }
                else {
                    alert('piip');
                }
            },
            'json'
        );
    };

    // load item
    this.loadItem = function(i){
        if(Gallery.item == ''){
            //$('#organize ul').sortable('destroy').html('');
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
                    $('#item ul > li input.title,#item ul > li textarea').on('keyup', function(e) {
                        Gallery.enableButton('#item .submit');
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
        Gallery.album = null;
        Gallery.item = null;
        Gallery.previtem = '';
        Gallery.nextitem = '';
    }
    
    this.enableUploader = function(input){
        // file upload
        var counter = 0,
            hash = null,
            url = 'request.php?c=main&m=upload';
        if(this.album)
            url = 'request.php?c=main&m=upload&album='+this.album;
        else
            url = 'request.php?c=main&m=upload';
        input.fileupload({
            url: url,
            dataType: 'json',
            dropZone: input.closest('.subpage'),
            pasteZone: null,
            singleFileUploads: true,
            sequentialUploads: true,
            submit: function(e, data){
                if(counter === 0){
                    hash = rnd();
                }
                data.formData = {hash: hash};
                counter ++;
            },
            done: function (e, data) {
                $('#main .progress .bar').css('width','0%');
                counter--;
                if(counter === 0){
                    Gallery.submitCheck(input.closest('.subpage').find('.submit'), function(){
                        if(Gallery.album) {
                            Gallery.loadAlbum(Gallery.album);
                        } else {
                            Gallery.loadGallery();
                        }
                        
                    });
                }
            },
            progressall: function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                $('#main .progress .bar').css('width',progress + '%');
            }
        });
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

    // open tab
    Tab.load();

    // change tab
    $('#sidebar ul li a').on('click',function(e){
        e.preventDefault();
        Tab.change(this);
    });

    // submit check
    $('#check-gallery .submit').on('click',function(e){
        e.preventDefault();
        Gallery.submitCheck(this);
    });

    // submit gallery
    $('#gallery .submit').on('click',function(e){
        e.preventDefault();
        Gallery.submitGallery(this);
    });

    // open album
    $('#gallery').on('click', 'ul li > a',function(e){
        e.preventDefault();
        Gallery.loadAlbum($(this).attr('href'));
    });
    
    // delete albu
    $('#gallery').on('click', 'li a.delete', function(e){
        e.preventDefault();
        var item = $(this).closest('li'),
            permit = confirm('Kustutan albumi ja kõik albumi pildid?');
        if (permit) {
            Gallery.deleteAlbum(item.attr('id'), function(){
                Gallery.submitCheck(item.closest('.subpage').find('.submit'), function(){
                    Gallery.loadGallery();
                });
            });
        }
    });

    // go back to gallery
    $('#album .back').on('click',function(e){
        e.preventDefault();
        Gallery.loadGallery();
    });

    // submit album
    $('#album .submit').on('click',function(e){
        e.preventDefault();
        Gallery.submitAlbum(this);
    });
    
    // delete item
    $('#album').on('click', 'li .thumb a.delete', function(e){
        e.preventDefault();
        var item = $(this).closest('li'),
            permit = confirm('Kustutan pildi?');
        if (permit) {
            Gallery.deleteItem(item.attr('id'), function(){
                Gallery.submitCheck(item.closest('.subpage').find('.submit'), function(){
                    Gallery.loadAlbum(Gallery.album);
                });
            });
        }
    });

    // open item
    $('#album').on('click', 'ul li > a',function(e){
        e.preventDefault();
        Gallery.loadItem($(this).attr('href'));
    });

    // next item
    $('#item').on('click', 'ul li a',function(e){
        e.preventDefault();
        Gallery.loadItem($(this).attr('href'));
    });

    // go back to album
    $('#item .back').on('click',function(e){
        e.preventDefault();
        Gallery.loadAlbum(Gallery.album);
    });

    // submit item
    $('#item .submit').on('click',function(e){
        e.preventDefault();
        Gallery.submitItem(this);
    });

    // logout
    $('#logout-submit').on('click', function(e){
        e.preventDefault();
        postLogout();
    });

    $('textarea,input').on('focus',function(){
        Gallery.focus = true;
    }).on('blur',function(){
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
    
    $(document).bind('drop dragover', function (e) {
        e.preventDefault();
    });
});