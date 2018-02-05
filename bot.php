<?php
echo '<script>console.log(\'Start script\')</script>';
header('Content-Type: text/html; charset=utf-8');

date_default_timezone_set('Europe/Moscow');

$token = '503700120:AAGPUE5Qb-IIt8qQyM92I9h_llLk-UPQf0c';
$api = 'https://api.telegram.org/bot'.$token;

$output = json_decode(file_get_contents('php://input'), TRUE); //сюда приходят все запросы по вебхукам

//телеграмные события
$chat_id = $output['message']['chat']['id']; //отделяем id чата, откуда идет обращение к боту
$message_id = $output['message']['message_id']; //id сообщения, которое нужно редактировать
$message = $output['message']['text']; //сам текст сообщения
$user = $output['message']['from']['username']; //сюда кладем юзернейм человек, обратившегося к боту
$user_firstname = $output['message']['from']['first_name'];
$user_id = $output['message']['from']['id']; //id юзера, для банов

//события ACR
$source = $output['source']; //всегда == ACR
$phone = $output['phone'];

$message = mb_strtolower($message); //этим унифицируем любое входящее сообщение в нижний регистр для дальнейшей обработки без ебли с кейсами

//--ДАЛЬШЕ ЛОГИКА БОТА--//

if ($message == "/start") {
	sendMessage($chat_id, "YO BRAH");
}

if ($message == "/chat") {
	sendMessage($chat_id, $chat_id);
}

if ($output) {
	$json_dec = json_decode($output, TRUE);
	sendMessage($chat_id, implode(';', $json_dec));	
}

//----------------------------------------------------------------------------------------------------------------------------------//

//отправка форматированного сообщения
function sendFormattedMessage($chat_id, $message, $markup)
{
	file_get_contents($GLOBALS['api'].'/sendMessage?chat_id='.$chat_id.'&text='.urlencode($message).'&parse_mode='.$markup);
}

//редактирование сообщения
function updateMessage($chat_id, $message_id, $new_message)
{
	file_get_contents($GLOBALS['api'].'/editMessageText?chat_id='.$chat_id.'&message_id='.$message_id.'&text='.urlencode($new_message));
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

echo '<script>console.log(\'End script\')</script>';
?>
