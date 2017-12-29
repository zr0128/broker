<!DOCTYPE html>
<html lang="zh-en">
<head>
	<meta charset="UTF-8">
	<title>demo</title>
	<meta name="renderer" content="webkit">
	<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <style>
        ul > li {cursor:pointer; line-height: 22px};
    </style>
</head>
<body>
    <div>
        <h3>用户列表</h3>
        <ul id="users"></ul>
    </div>
	<div id="right">
        <span id="me"></span>
        <div id="accept_message"></div>
		发消息给<span id="msgto"></span><textarea cols="20" rows="3" id="message"></textarea><br />
		<button id="send">发送</button>
	</div>
</body>
</html>
<script type="text/javascript" src="/pc/js/jquery.min.js"></script>
<script type="text/javascript">
	var heartInterval = null;
	var wsServer = 'ws://youdomain:9186';
	var websocket = new WebSocket(wsServer);
	var heartTimeout = 20000; // 超时时间，20S
    var me = {};
    var to = {id:0, nickname:''};

	websocket.onopen = onOpen;
	websocket.onclose = onClose;
	websocket.onmessage = onMessage;
	websocket.onerror = onError;

	function onOpen(evt) {
		//sendMessage('protocol base');
		clearInterval(heartInterval);
		heartInterval = setInterval(function() {
            sendMessage('^^');
		}, 10000);
	}

	function onMessage(evt) {
		var data = evt.data;
        var arrData = data.split(' ');

		console.log(data);

        switch (arrData[0]) {
            case 'protocol':
                sendMessage('login ' + me.id);
            break;
            case 'login':
                sendMessage('users json');
            break;
            case 'users':
                var users = eval('(' + arrData[1] + ')');
                $.each(users, function(n, v) {
                    $('#users').append('<li data-id="' + v.id + '">' + v.nickname + '</li>');
                });
            break;
            case 'behavior':
                if (arrData.length < 2) {
                    console.log('服务器返回数据格式不正确');
                    return;
                }

                updateUserList(arrData[1], arrData[2]);
            break;
            case 'fd':
                me.id = arrData[1];
                me.nickname = myNick(arrData[1]);
                $('#me').html('欢迎：' + me.nickname);

                sendMessage('protocol base');
            break;
            case 'message':
                var msg = eval('(' + arrData[1] + ')');
                if (typeof msg == 'object') {
                    $('#accept_message').html($('#accept_message').html() + '<br />' + msg.fromId + ':' + msg.message);
                }
                else {
                    alert('接收到一个无效的消息' + arrData[1]);
                }
            break;
        }
	}

    $('#send').click(function() {
        sendMessage('message ' + to.id + ' ' + $('#message').val());
        $('#message').val('');
    });

    function updateUserList(behavior, data) {
        console.log('behavior:' + behavior);
        console.log('data:' + data);

        var user = eval('(' + data + ')');
        switch (behavior) {
            case 'online':
                if (user.id != me.id) {
                    $('#users').prepend('<li data-id="' + user.id + '">' + user.nickname + '</li>');
                }
            break;
            case 'offline':
                $('#users').find('[data-id=' + user.id + ']').remove();
            break;
            default:
        }
    }

    function myNick(ind) {
        var nicks = ['john', '花猫', '憨貔貅', 'jesus', 'god', '突突小怪兽'];
        if (ind < nicks.length) {
            return nicks[ind];
        }

        return '访客' + (new Date()).getTime();
    }

    function getId(len) {
        len = len || 32;
        var $chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678';
        var maxPos = $chars.length;
        var pwd = '';
        for (var i = 0; i < len; i++) {
            pwd += $chars.charAt(Math.floor(Math.random() * maxPos));
        }

        return pwd;
    }

    $('#users').on('click', 'li', function() {
        to.id = $(this).data('id');
        to.nickname = $(this).text();
        $('#msgto').text(to.nickname);
    });

    function onError(evt) {
        // console.log('error: ' + evt.data);
        setTimeout(reConnect, 3000);
    }

    function onClose(evt) {
        // console.log('closed');
        clearInterval(heartInterval);
    }

    function reConnect() { // 重连
        // console.log('reconnect');

        websocket = new WebSocket(wsServer);
        websocket.onopen = onOpen;
        websocket.onclose = onClose;
        websocket.onmessage = onMessage;
        websocket.onerror = onError;
    }

    function sendMessage(msg) {
        waitForSocketConnection(function() {
            websocket.send(msg);
        });
    }

    function waitForSocketConnection(callback){
        setTimeout(function(){
            if (websocket.readyState === 1) {
                if(callback !== undefined){
                    callback();
                }
                return;
            }
            else {
                waitForSocketConnection(callback);
            }
        }, 200);
    }

</script>
