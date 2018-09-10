<?php

require_once 'vendor/autoload.php';

$token = '503700120:AAGxJuN9CMFqNjQ2lOsLvtb79T-Llz3H130';
$api = 'https://api.telegram.org/bot'.$token;

$input = file_get_contents('php://input');
$output = json_decode($input, TRUE); //сюда приходят все запросы по вебхукам

//соединение с БД
$db = mysqli_connect('eu-cdbr-west-02.cleardb.net', 'b70a1c22756565', '6c429cd3', 'heroku_18de73b74f8039e');

//телеграмные события
$chat_id = $output['message']['chat']['id']; //отделяем id чата, откуда идет обращение к боту
$message_id = $output['message']['message_id']; //id сообщения, которое нужно редактировать
$message = $output['message']['text']; //сам текст сообщения
$user = $output['message']['from']['username'];
$user_id = $output['message']['from']['id'];
$report = array(); //инициализация отчета

$message = mb_strtolower($message); //этим унифицируем любое входящее сообщение от телеги в нижний регистр для дальнейшей обработки без ебли с кейсами

//--ДАЛЬШЕ ЛОГИКА БОТА--//

//регистрация+генерация secret для ACR
if ($message == '/start') {
	//запрашиваем БД регистрировался ли юзер ранее, чтобы 
	$query = mysqli_query($db, 'select chat_id from users where chat_id='.$chat_id);
	while ($sql = mysqli_fetch_object($query)) {
		$sql_chat_id = $sql->chat_id;
	}
	if ($sql_chat_id == $chat_id) {
		sendMessage($chat_id, "Вы уже были зарегистрированы!\n\nВведите /secret чтобы узнать secret для настройки ACR.");
	} else {
		//генерация secret
		$acr_secret = base_convert($chat_id, 10, 36);
		sendFormattedMessage($chat_id, "Привет! Сейчас я покажу как настроить ACR для пользования ботом.\n\nДля начала надо зайти в пункт меню:\n*Настройки*->*Облачные сервисы*->*WebHook* \n\nДалее настроить URL для подключения к боту. Этот бот работает по адресу:\n\n`https://callhistory-bot.herokuapp.com/bot.php` \n\nВ поле *Секрет* надо будет ввести секретный код, который выдаст бот после регистрации.\n\nПосле этого можно выбрать желаемые значения, отправляемые вместе с файлом записи. Они отобразятся в одном сообщении вместе с записью голоса.\n\nЕсли у вас уже есть записи в памяти телефона - выгрузите их все сразу кнопкой в самом низу *\"Выгрузить еще раз\"*. Файлы добавятся в Telegram.\nЕсли у вас не было записей до этого - просто пользуйтесь телефоном как обычно, записи будут выгружены в соответствии с настройками приложения ACR.\n", 'Markdown');
		mysqli_query($db, "insert into users (chat_id, acr_secret) values (".$chat_id.", '".$acr_secret."')");
		sleep(5);
		sendMessage($chat_id, "Вы зарегистрированы!\n\nВаш секретный код:\n ```".$acr_secret."``` \n\nВведите его в поле secret в настройках Web Hook в ACR. Это идентифицирует вас и именно ваши записи.", 'Markdown');
	}
	
	mysqli_free_result($sql);
}

if ($message == '/secret') {
	$query = mysqli_query($db, 'select acr_secret from users where chat_id='.$chat_id);
	while ($sql = mysqli_fetch_object($query)) {
		$secret = $sql->acr_secret;
	}
	sendFormattedMessage($chat_id, "Ваш секретный код:\n\n```".$secret."```\n\nВведите его в поле secret в настройках Web Hook в ACR. Это идентифицирует вас и именно ваши записи.", 'Markdown');
	
	mysqli_free_result($sql);
}

if ($message == '/givemeid') {
	sendMessage($chat_id, $chat_id.' | '.$user.' | '.$user_id);
}

if ($message == '/help') {
	sendFormattedMessage($chat_id, "Привет! Сейчас я покажу как настроить ACR для пользования ботом.\n\nДля начала надо зайти в пункт меню:\n*Настройки*->*Облачные сервисы*->*WebHook* \n\nДалее настроить URL для подключения к боту. Этот бот работает по адресу:\n\n`https://callhistory-bot.herokuapp.com/bot.php` \n\nВ поле *Секрет* надо будет ввести секретный код, который выдаст бот после регистрации.\n\nПосле этого можно выбрать желаемые значения, отправляемые вместе с файлом записи. Они отобразятся в одном сообщении вместе с записью голоса.\n\nЕсли у вас уже есть записи в памяти телефона - выгрузите их все сразу кнопкой в самом низу *\"Выгрузить еще раз\"*. Файлы добавятся в Telegram.\nЕсли у вас не было записей до этого - просто пользуйтесь телефоном как обычно, записи будут выгружены в соответствии с настройками приложения ACR.\n", 'Markdown');
}

//кладем данные из ACR в массив параметров
$ACR_fields = array(
	"date" => date('d.m.Y H:i:s', $_POST['date']),
	"duration" => $_POST['duration']/1000,
	"important_flag" => $_POST['important'],
	"note" => $_POST['note'],
	"phone" => $_POST['phone'],
	"contact" => $_POST['contact']
);

//форматируем входные данные (если они есть)
if ($_POST['direction'] == 1) {
	$ACR_fields['direction'] = 'Исходящий';
} else if ($_POST['direction'] == 0){
	$ACR_fields['direction'] = 'Входящий';
}

if ($ACR_fields['date']) {
	$ACR_fields['date'] = 'Дата: '.$ACR_fields['date'];
}
if ($ACR_fields['phone']) {
	$ACR_fields['phone'] = 'Номер: '.$ACR_fields['phone'];
}
if ($ACR_fields['contact']) {
	$ACR_fields['contact'] = 'Имя контакта: '.$ACR_fields['contact'];
}
if ($ACR_fields['note']) {
	$ACR_fields['note'] = 'Заметка: '.urldecode($ACR_fields['note']);
}
if ($ACR_fields['duration']) {
	$ACR_fields['duration'] = 'Длительность: '.$ACR_fields['duration'].' секунд';
}
if ($ACR_fields['important_flag']) {
	$ACR_fields['important_flag'] = '#важный';
}

//чистим выключенные параметры (не будем их отсылать с отчетом)
$report = array_filter($ACR_fields);
$final_report = implode("\n", $report);

//получили что-то от ACR? отправляем запись!
if ($_POST['source'] == 'ACR') {
	$voice_file = $_FILES['file'];
	
	$query = mysqli_query($db, "select * from users where acr_secret='".$_POST['secret']."'");
	while ($sql = mysqli_fetch_object($query)) {
		$chat_id = $sql->chat_id;
		$secret = $sql->acr_secret;
	}
	
	if ($secret == $_POST['secret']) {
		sendVoice($chat_id, $voice_file, $_POST['duration']/1000, $final_report);
	}
	mysqli_free_result($sql);
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
function sendVoice($chat_id, $voice, $duration, $caption) {
	file_get_contents($GLOBALS['api'].'/sendChatAction?chat_id='.$chat_id.'&action=upload_voice');
	$filepath = realpath($_FILES['file']['tmp_name']);
	$post_data = array(
		'chat_id' => $chat_id,
		'voice' => new CURLFile($filepath),
		'duration' => $duration,
		'caption' => $caption
	);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $GLOBALS['api'].'/sendVoice');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_exec($ch);
	curl_close($ch); 
}

mysqli_close($db);
?>
