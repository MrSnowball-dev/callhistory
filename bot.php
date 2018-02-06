<?php
header('Content-Type: multipart/form-data; boundary="--boundary"');
$token = '503700120:AAGPUE5Qb-IIt8qQyM92I9h_llLk-UPQf0c';
$api = 'https://api.telegram.org/bot'.$token;

$input = file_get_contents('php://input');
$output = json_decode($input, TRUE); //сюда приходят все запросы по вебхукам

//телеграмные события
$chat_id = $output['message']['chat']['id']; //отделяем id чата, откуда идет обращение к боту, я = 197416875
$message_id = $output['message']['message_id']; //id сообщения, которое нужно редактировать
$message = $output['message']['text']; //сам текст сообщения
$user = $output['message']['from']['username']; //сюда кладем юзернейм человек, обратившегося к боту
$user_firstname = $output['message']['from']['first_name'];
$user_id = $output['message']['from']['id']; //id юзера, для банов

//события ACR

$message = mb_strtolower($message); //этим унифицируем любое входящее сообщение в нижний регистр для дальнейшей обработки без ебли с кейсами

//--ДАЛЬШЕ ЛОГИКА БОТА--//

$data = array();
preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
if (!count($matches)) {
	parse_str(urldecode($input), $data);
}

$boundary = $matches[1];
$blocks = preg_split("/-+$boundary/", $input);
array_pop($blocks);

foreach ($blocks as $id => $block) {
	if (empty($block)) {
		continue;
	}
	if (strpos($block, 'application/octet-stream') !== FALSE) {
		preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
		$data['files'][$matches[1]] = $matches[2];
	} else {
		preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
		$data[$matches[1]] = $matches[2];
	}
}

foreach ($data as $key => $value) {
	sendMessage('197416875', 'input.'$key.': '.$value);
}

if ($message == 'hi') {
	foreach ($output as $key => $value) {
		sendMessage('197416875', 'output.'.$key.': '.$value);
	}
}

//----------------------------------------------------------------------------------------------------------------------------------//

//отправка форматированного сообщения
function sendFormattedMessage($chat_id, $message, $markup)
{
	file_get_contents($GLOBALS['api'].'/sendMessage?chat_id='.$chat_id.'&text='.urlencode($message).'&parse_mode='.$markup);
}

//удаление сообщения
function deleteMessage($chat_id, $message_id)
{
	file_get_contents($GLOBALS['api'].'/deleteMessage?chat_id='.$chat_id.'&message_id='.$message_id);
}

//отправка простого сообщения
function sendMessage($chat_id, $message)
{
	file_get_contents($GLOBALS['api'].'/sendMessage?chat_id='.$chat_id.'&text='.urlencode($message));
}
?>
