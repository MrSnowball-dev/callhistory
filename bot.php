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
$report = array(); //инициализация отчета

$chat_id = 197416875; //УДАЛИТЬ ПОСЛЕ ВНЕДРЕНИЯ БД!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//события ACR

$message = mb_strtolower($message); //этим унифицируем любое входящее сообщение от телеги в нижний регистр для дальнейшей обработки без ебли с кейсами

//--ДАЛЬШЕ ЛОГИКА БОТА--//

//кладем данные из ACR в массив параметров
$ACR_fields = array(
	"source" => $_POST['source'],
	"acrfilename" => $_POST['acrfilename'],
	"secret" => $_POST['secret'],
	"date" => $_POST['date'],
	"duration" => $_POST['duration'],
	"direction" => $_POST['direction'],
	"important_flag" => $_POST['important'],
	"note" => $_POST['note'],
	"phone" => $_POST['phone'],
	"contact" => $_POST['contact']
);

//чистим выключенные параметры (не будем их отсылать с отчетом)
$report = array_filter($ACR_fields);

$final_report = implode("\n", $report);

//получили что-то от ACR? отправляем запись!
if ($ACR_fields['source'] == 'ACR') {
	$voice_file = $_FILES['file'];
	sendMessage($chat_id, "Запись:\n".$final_report);
	sendVoice($chat_id, $voice_file, $ACR_fields['duration']/1000);
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
function sendVoice($chat_id, $voice, $duration) {
	$filepath = realpath($_FILES['file']['tmp_name']);
	$post_data = array(
		'chat_id' => $chat_id,
		'voice' => new CURLFile($filepath),
		'duration' => $duration
	);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $GLOBALS['api'].'/sendVoice');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_exec($ch);
	curl_close($ch); 
}
?>
