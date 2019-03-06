var my_client = {
    ws: null,
    timer: {},
    ini: function() {
        var self = this;
        my_user.ini();
        my_ui.ini();
        protobuf.load('/app.proto', function(err, root) {
            if (err) {
                console.error(err);
                return false;
            }
            my_probuf.root = root;
            self.ws_init();
        });

    },
    ws_init: function(cb) {
        var self = this;
        this.ws = new WebSocket("ws://192.168.1.27:8907");
        this.ws.binaryType = 'arraybuffer';
        this.ws.onopen = function(evt) {
            self.wsOpen(evt);
            if(cb) {cb();}
        }
        this.ws.onmessage = function(evt) {
            self.wsMessage(evt);
        }
        this.ws.onclose = function(evt) {
            self.wsClose(evt);
        }
    },
    wsStatus: function() {
        return this.ws.state;
    },
    wsOpen: function(evt) {
        var self = this;
        console.info('Connect to server ok.');
        if (my_user.token) {
            this.request({
                token: my_user.token,
            }, 1003, 'RequestLogin');
        } else {
            my_ui.showRegister();
        }
    },
    wsMessage: function(evt) {
        var self = this;
        var [msg_id, buf] = my_probuf.readBuf(evt.data);
        var res_proto = {
            1002: 'ResponseRegister',
            1004: 'ResponseLogin',
            1006: 'ResponseLogout',
            1008: 'ResponseSendMessage',
            1009: 'ResponseUserOnline',
            1010: 'ResponseReceiveMessage',
            1012: 'ResponseGetHistoryMessage',
        }[msg_id];
        clearTimeout(self.timer.req);
        my_probuf.Response_Message(buf, res_proto, function(data) {
            console.log('receive data: ', msg_id, data);
            switch (msg_id) {
                case 1002:
                    my_user.registerHandle(data);
                    // alert('可以愉快的聊天了');
                    break;
                case 1004:
                    my_user.loginHandle(data);
                    break;
                case 1006:
                    my_user.logoutHandle();
                    break;
                case 1009:
                    my_user.onlineHandle(data);
                    break;
                case 1008:
                    clearTimeout(self.timer[data.msgID]);
                    delete self.timer[data.msgID];
                    $('#msg-'+data.msgID).removeClass('sending');
                    break;
                case 1010:
                    // receive message
                    my_ui.insertMsg(data.msg);
                    break;
                case 1012:
                    for(var i in data.msg) {
                        my_ui.insertMsg(data.msg[i]);
                    }
                    break;
                default:
                    console.error('unresolved msg:' + msg_id);
                    return ;
            }
        })
    },
    wsClose: function(evt) {
        console.warn('Connection closed by server.');
        my_user.logout();
    },
    request: function(data, msg_id) {
        console.log('Request data: ', msg_id, data);
        var self = this;
        var msg_proto = {
            1001: 'RequestRegister',
            1003: 'RequestLogin',
            1005: 'RequestLogout',
            1007: 'RequestSendMessage',
            10011: 'RequestGetHistoryMessage',
        }[msg_id] || '';
        my_probuf.Request_Message(data, msg_proto, function(buf){
            self.ws.send(my_probuf.writeBuf(msg_id, buf));
            // self.timer.req = setTimeout(function() {
            //     console.log('AUTO RECONNECT..')
            //     self.ws_init();
            // }, 30000);
        });
    },
    sendMsg: function(chat, type, content) {
        var msg = {
            msgID: new Date().getTime(),
            msgType: type,
            stamp: new Date().getTime(),
            content: content,
            from: my_user.id,
        };
        this.request({
            ChatId: chat,
            msg: msg}, 1007, 'RequestSendMessage');
        my_ui.insertMsg(msg, true);
    },
    sendText: function(chat, text) {
        this.sendMsg(chat, 0, text);
    },
    sendBlob: function(chat, blob) {
        this.sendMsg(chat, 1, blob);
    },
    sendPic: function(chat, src) {
        this.sendMsg(chat, 2, src);
    },
};