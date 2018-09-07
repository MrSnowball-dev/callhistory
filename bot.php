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

$message = mb_strtolower($message); //этим унифицируем любое входящее сообщение в нижний регистр для дальнейшей обработки без ебли с кейсами

//--ДАЛЬШЕ ЛОГИКА БОТА--//

// $input_contents = [];
// mb_parse_str($input, $input_contents);

// foreach($input_contents as $parsed_header => $parsed_value) {
// 	sendMessage(197416875, 'got '.$parsed_header.': '.$parsed_value);
// }

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
	sendMessage(197416875, $ACR_fields['acrfilename'].', '.$ACR_fields['date'].', '.$ACR_fields['contact'].', '.$ACR_fields['phone'].', '.$ACR_fields['direction'].', '.$ACR_fields['duration'].', ');
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

function sendVoice($chat_id, $voice, $caption) {
	file_get_contents($GLOBALS['api'].'/sendChatAction?chat_id='.$chat_id.'&action=upload_audio');
	file_get_contents($GLOBALS['api'].'/sendAudio?chat_id='.$chat_id.'&audio='.$voice.'&caption='.$caption);	
}
// 	$boundary = uniqid();
// 	$delimiter = '-------------' . $boundary;
// 	$fields = array(
// 		"chat_id" => $chat_id,
// 		"caption" => $caption
// 	);
	
// 	$post_data = build_data_files($boundary, $fields, $voice);
	
// 	$ch = curl_init($GLOBALS['api'].'/sendVoice');
// 	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
// 		//"Authorization: Bearer $TOKEN",
// 		"Content-Type: multipart/form-data; boundary=" . $delimiter,
//         "Content-Length: " . strlen($post_data))
//     	);
// 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// 	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
// 	curl_setopt($ch, CURLOPT_POST, 1);
// 	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
// 	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
// 	curl_exec($ch);
// 	curl_close($ch);
// }
		    
// function build_data_files($boundary, $fields, $files){
//     $data = '';
//     $eol = "\r\n";

//     $delimiter = '-------------' . $boundary;

//     foreach ($fields as $name => $content) {
//         $data .= "--" . $delimiter . $eol
//             . 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
//             . $content . $eol;
//     }


//     foreach ($files as $name => $content) {
//         $data .= "--" . $delimiter . $eol
//             . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $name . '"' . $eol
//             //. 'Content-Type: image/png'.$eol
//             . 'Content-Transfer-Encoding: binary'.$eol
//             ;

//         $data .= $eol;
//         $data .= $content . $eol;
//     }
//     $data .= "--" . $delimiter . "--".$eol;


//     return $data;
// }
?>
