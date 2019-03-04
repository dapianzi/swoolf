var my_user = {
    token: '',
    id: 0,
    avatar: '',
    name: '',
    friends: [],
    chat: [],
    loginHandle: function(data) {
        if (data.err) {
            this.clearUser();
            my_ui.showLogin('用户名或密码错误');
        } else {
            this.id = data.id;
            this.avatar = data.avatar;
            this.name = data.name;
            this.friends = data.friends;
            this.chat = data.chat;
            my_ui.showChat(this.getUser());
        }
    },
    registerHandle: function(data) {
        if (data.err) {
            this.clearUser();
            my_ui.showRegister('用户名重复');
        } else {
            this.token = data.token;
            my_utils.storage.set('token', this.token);
            this.login({token: this.token});
        }
    },
    logoutHandle: function(data) {
        this.clearUser();
    },
    login: function(data) {
        my_client.request(data, 1003);
    },
    register: function(data) {
        data.avatar = data.avatar||'/default/avatar_'+Math.ceil(Math.random()*9)+'./jpg';
        console.log(data);
        my_client.request(data, 1001);
    },
    logout: function() {
        my_client.request(null, 1005);
    },
    clearUser: function() {
        this.id = 0;
        this.avatar = '';
        this.name = '';
        this.friends = [];
        this.chat = [];
        this.token = '';
    },
    changeAvatar: function(avatar) {
        this.avatar = `/default/avatar_${avatar}.jpg`;
    },
    getUser: function() {
        return {
            token: this.token,
            id: this.id,
            avatar: this.avatar,
            name: this.name,
            friends: this.friends,
            chat: this.chat,
        }
    },
    ini: function() {
        this.token = my_utils.storage.get('token');
    },
};