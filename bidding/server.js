var io = require('socket.io').listen(3000);
var easyamqp = require('easy-amqp');
var connection = easyamqp.createConnection({ host: 'localhost', port: 5672, login: 'guest', password: 'guest', vhost: '/' }, { defaultExchangeName: "amq.topic" });

io.sockets.on('connection', function (socket) {

});
connection.queue('helloqueue').bind('logs-exchange', '#').subscribe(function(message, headers, deliveryInfo, rawMessage, queue) {
  var encoded_payload = unescape(message.data);
  var payload = JSON.parse(encoded_payload);
  console.log('Recieved a message:');
  console.log(payload);
  io.sockets.emit('update-bids', payload);
});
