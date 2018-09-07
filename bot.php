<?php

require_once 'vendor/autoload.php';

$token = '503700120:AAGxJuN9CMFqNjQ2lOsLvtb79T-Llz3H130';
$api = 'https://api.telegram.org/bot'.$token;

$input = file_get_contents('php://input');
$output = json_decode($input, TRUE); //сюда приходят все запросы по вебхукам

//телеграмные события
$chat_id = $output['message']['chat']['id']; //отделяем id чата, откуда идет обращение к боту, я = 197416875
$message_id = $output['message']['message_id']; //id сообщения, которое нужно редактировать
$message = $output['message']['text']; //сам текст сообщения

//события ACR

$message = mb_strtolower($message); //этим унифицируем любое входящее сообщение от телеги в нижний регистр для дальнейшей обработки без ебли с кейсами

//--ДАЛЬШЕ ЛОГИКА БОТА--//

$ACR_fields = array(
	"source"=> $_POST['source'],
	"acrfilename"=> $_POST['acrfilename'],
	"secret"=> $_POST['secret'],
	"date"=> $_POST['date'],
	"duration"=> $_POST['duration'],
	"direction"=> $_POST['direction'],
	"important_flag"=> $_POST['important'],
	"note"=> $_POST['note'],
	"phone"=> $_POST['phone'],
	"contact"=> $_POST['contact']
);

if ($ACR_fields['source'] == 'ACR') {
	$voice_file = $_FILES['file'];
	sendMessage(197416875, $ACR_fields['acrfilename'].', '.$ACR_fields['date'].', '.$ACR_fields['contact'].', '.$ACR_fields['phone'].', '.$ACR_fields['direction'].', '.$ACR_fields['duration']);
	sendVoice(197416875, $voice_file, $_POST['phone']);
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

//отправка разговора
function sendVoice($chat_id, $voice, $caption) {
	$post_data = http_build_query(
		array(
			'chat_id' => $chat_id,
			'voice' => $voice,
			'caption' => $caption
		)
	);
	$opts = array('http' =>
			array(
				'method' => 'POST',
				'header' => 'Context-type: multipart/form-data',
				'content' => $post_data
			)
	);
	$context = stream_context_create($opts);
	file_get_contents($GLOBALS['api'].'/sendChatAction?chat_id='.$chat_id.'&action=upload_audio');
	file_get_contents($GLOBALS['api'].'/sendVoice', false, $context);	
}
?>
