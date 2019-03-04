var my_ui = {
    loading: function() {

    },
    showChat: function(user) {
        this.showPage('chat');
    },
    showLoading: function() {
        console.log('loading');
        this.showPage('loading');
    },
    showRegister: function(err) {
        if (err) {
            $('#login-err').html('<div class="alert alert-danger alert-dismissible" role="alert">' +
                '  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>\n' +
                '  <strong>Error!</strong> ' + err +
                '</div>');
        }
        this.showPage('register');
    },
    showLogin: function(err) {
        if (err) {
            $('#login-err').html('<div class="alert alert-danger alert-dismissible" role="alert">' +
                '  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>\n' +
                '  <strong>Error!</strong> ' + err +
                '</div>');
        }
        this.showPage('login');
    },
    showPage: function(id) {
        $('#' + id).addClass('view').siblings().removeClass('view');
    },
    insertMsg: function(msg) {
        var cls = msg.from == my_user.id ? 'mine sending' : 'other';
        var flag = my_user.id == msg.from;
        var avatar = flag ? my_user.avatar : '/default/avatar_'+(msg.from%9+1)+'.jpg';
        var content = '';
        if (msg.msgType == 1) {
            content = '<div class="message-content message-img">' +
                '<img src="data:image/png;base64,'+window.btoa(msg.content)+'"/></div></div>';
        } else if (msg.msgType == 2) {
            content = '<div class="message-content message-img"><img src="' + msg.content + '"/></div>';
        } else {
            content = '<div class="message-content message-text">' + msg.content + '</div>';
        }
        var _html = '<div class="message message-'+ cls +'" id="msg-' + msg.msgID + '">' +
            '<div class="avatar" style="background-image:url(' + avatar + ');"></div>' + content + '</div>';
        $('#message-container').append(_html);
        var _height = $('#message-container .message:last').offset().top - $('#message-container .message:first').offset().top;
        $('#message-container').scrollTop(_height);
        if (flag) {
            this.waiting_response(msg.msgID);
        }
    },
    waiting_response(msgID) {
        var self = this;
        my_client.timer[msgID] = setTimeout(function(){
            $('#msg-'+msgID).removeClass('sending').addClass('failed');
        }, 10000);
    },
    bind_events: function() {
        $('#send-text').on('click', function() {
            var text = $('#msg-text').val();
            if ('' !== text) {
                my_client.sendText(1, text);
                $('#msg-text').val('').focus();
            }
        });
        $('#msg-text').on('keyup', function(e) {
            if (e.keyCode == 13) {
                $('#send-text').trigger('click');
            }
        });
        // 断线重连
        $('#message-container').on('click', '.message.failed', function() {
            $(this).removeClass('failed').addClass('sending');
            if (!my_client.wsStatus()){
                my_client.ws_init(function() {
                    $(this).remove();
                    my_client.sendText(1, $(this).find('.message-content').text());
                });
            } else {
                $(this).remove();
                my_client.sendText(1, $(this).find('.message-content').text());
            }
        });
        // 发送图片
        $('#file-inp').on('change', function(e){
            //加载本地文件
            var file = $(this)[0].files[0];
            var reader = new FileReader();
            var step = 1024 * 1024;
            var total = file.size;
            var cuLoaded = 0;
            console.info("文件大小：" + file.size);
            if (file.size > 2048000) {
                alert('图片尺寸超过限制');
                return false;
            }
            // reader.readAsArrayBuffer(file); //
            reader.readAsBinaryString(file);   //返回二进制字符串
            reader.onload = function (e) {
                console.log(e);
                my_client.sendBlob(1, reader.result)
            }
        });
        // change avatar
        $('#message-container').on('click', '.message-mine .avatar', function() {
            $('#avatarChooseModal').modal();
        });
        $('#avatarChooseModal').on('click', '#save-avatar', function (e) {
            var idx = $('#avatarChooseModal .avatar-option.chosen').data('id');
            self.avatar = idx-1;
            $('.message-mine .avatar').css({'background-image': 'url(/default/avatar_'+(self.avatar%9+1)+'.jpg)'});
            $('#avatarChooseModal').modal('hide');
        });
        $('#page').on('click', '.avatar-item', function(e){
            if (!$(this).hasClass('chosen')) {
                $('.avatar-item.chosen').removeClass('chosen');
                $(this).addClass('chosen');
            }
        });
        // logout
        $('#logout').on('click', function () {
            my_user.logout();
        });
        $('#switch-register').on('click', function() {
            my_ui.showRegister();
        });
        $('#switch-login').on('click', function() {
            my_ui.showLogin();
        });
        $('#btn-register').on('click', function(e) {
            e.preventDefault();
            var username = $('#MyUsername').val();
            var password = $('#MyPassword').val();
            var idx = $('#MyAvatar .avatar-item.chosen').data('id') || '1';
            var avatar = '/default/avatar_'+idx+'.jpg';
            my_user.register({username, password, avatar});
        });
        $('#btn-login').on('click', function(e) {
            e.preventDefault();
            var username = $('#LoginUsername').val();
            var password = $('#LoginPassword').val();
            my_user.register({username, password});
        });
    },
    ini: function() {
        this.showLoading();
        this.bind_events();
    }
};