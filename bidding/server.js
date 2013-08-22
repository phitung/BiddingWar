var io = require('socket.io').listen(3000);
var easyamqp = require('easy-amqp');
var connection = easyamqp.createConnection({ host: 'localhost', port: 5672, login: 'guest', password: 'guest', vhost: '/' }, { defaultExchangeName: "amq.topic" });
	
connection.queue('helloqueue').bind('logs-exchange', '#').subscribe(function(message, headers, deliveryInfo, rawMessage, queue) {
	io.sockets.emit('update-bids', JSON.parse(unescape(message.data)));
});
