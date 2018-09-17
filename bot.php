<?php

include 'config.php';
require_once 'vendor/autoload.php';

$token = $tg_bot_token;
$api = 'https://api.telegram.org/bot'.$token;

$input = file_get_contents('php://input');
$output = json_decode($input, TRUE); //сюда приходят все запросы по вебхукам

//соединение с БД
$db = mysqli_connect($db_host, $db_username, $db_pass, $db_schema);

//телеграмные события
$chat_id = $output['message']['chat']['id']; //отделяем id чата, откуда идет обращение к боту
$message_id = $output['message']['message_id']; //id сообщения, которое нужно редактировать
$message = $output['message']['text']; //сам текст сообщения
$user = $output['message']['from']['username'];
$user_id = $output['message']['from']['id'];
$report = array(); //инициализация отчета

//язфк пользователя, по-умолчанию русский
$query = mysqli_query($db, 'select language from users where chat_id='.$chat_id);
while ($sql = mysqli_fetch_object($query)) {
	$user_lang = $sql->language;
}

//инициализация клавиатуры
$lang_keyboard_buttons = array(array(
	"🇷🇺 Русский",
	"🇺🇸 English"
));
$lang_keyboard = array(
	"keyboard" => $lang_keyboard_buttons,
	"resize_keyboard" => true,
	"one_time_keyboard" => true
);
//----------------------------------
$ru_keyboard_buttons = array(array(
	"🆔 Секретный код",
	"🛠 Настройки бота"
));
$ru_keyboard = array(
	"keyboard" => $ru_keyboard_buttons,
	"resize_keyboard" => true,
	"one_time_keyboard" => false
);
$ru_settings_keyboard_buttons = array(array(
	"📳 Тихий режим",
	"💱 Сменить язык",
	"🤔 Помощь в настройке"
));
$ru_settings_keyboard = array(
	"keyboard" => $ru_settings_keyboard_buttons,
	"resize_keyboard" => true,
	"one_time_keyboard" => true
);
//----------------------------------
$en_keyboard_buttons = array(array(
	"🆔 Secret code",
	"🛠 Bot settings"
));
$en_keyboard = array(
	"keyboard" => $en_keyboard_buttons,
	"resize_keyboard" => true,
	"one_time_keyboard" => false
);
$en_settings_keyboard_buttons = array(array(
	"📳 Silent mode",
	"💱 Change language",
	"🤔 Setup help"
));
$en_settings_keyboard = array(
	"keyboard" => $en_settings_keyboard_buttons,
	"resize_keyboard" => true,
	"one_time_keyboard" => true
);

//--ДАЛЬШЕ ЛОГИКА БОТА--//

//регистрация+генерация secret для ACR
if ($message == '/start') {
	sendMessage($chat_id, "Choose your language!\n\nВыберите язык!", $lang_keyboard);
}

if ($message == "🇺🇸 English") {
	$user_lang = 'en';
	$query = mysqli_query($db, 'select chat_id from users where chat_id='.$chat_id);
	while ($sql = mysqli_fetch_object($query)) {
		$sql_chat_id = $sql->chat_id;
	}
	
	if ($sql_chat_id == $chat_id) {
		sendMessage($chat_id, "You are already registered!\n\nYou can see your secret code by pressing the button below.", $en_keyboard);
	} else {
		//генерация secret
		$acr_secret = base_convert($chat_id, 10, 36);
		sendFormattedMessage($chat_id, "Привет! Сейчас я покажу как настроить ACR для пользования ботом.\n\nДля начала надо зайти в пункт меню:\n*Настройки*->*Облачные сервисы*->*WebHook* \n\nДалее настроить URL для подключения к боту. Этот бот работает по адресу:\n\n`https://callhistory-bot.herokuapp.com/bot.php` \n\nВ поле *Секрет* надо будет ввести секретный код, который выдаст бот после регистрации.\n\nПосле этого можно выбрать желаемые значения, отправляемые вместе с файлом записи. Они отобразятся в одном сообщении вместе с записью голоса.\n\nЕсли у вас уже есть записи в памяти телефона - выгрузите их все сразу кнопкой в самом низу *\"Выгрузить еще раз\"*. Файлы добавятся в Telegram.\nЕсли у вас не было записей до этого - просто пользуйтесь телефоном как обычно, записи будут выгружены в соответствии с настройками приложения ACR.\n", 'Markdown', $en_keyboard);
		mysqli_query($db, "insert into users (chat_id, acr_secret, language) values (".$chat_id.", SHA2('".$acr_secret."', 256), '".$user_lang."')");
		sleep(5);
		sendFormattedMessage($chat_id, "You have been registered!\n\nYour secret code:\n`".$acr_secret."`\n\nEnter it in SECRET field in ACR Webhook settings page. This will identify you and your recordings.", 'Markdown', $en_keyboard);
	}
	
	mysqli_free_result($sql);
}

if ($message == "🇷🇺 Русский") {
	$user_lang = 'ru';
	//запрашиваем БД регистрировался ли юзер ранее, чтобы выдать ему соответствующий secret
	$query = mysqli_query($db, 'select chat_id from users where chat_id='.$chat_id);
	while ($sql = mysqli_fetch_object($query)) {
		$sql_chat_id = $sql->chat_id;
	}
	
	if ($sql_chat_id == $chat_id) {
		sendMessage($chat_id, "Вы уже были зарегистрированы!\n\nВведите /secret чтобы узнать secret для настройки ACR.", $ru_keyboard);
	} else {
		//генерация secret
		$acr_secret = base_convert($chat_id, 10, 36);
		sendFormattedMessage($chat_id, "Привет! Сейчас я покажу как настроить ACR для пользования ботом.\n\nДля начала надо зайти в пункт меню:\n*Настройки*->*Облачные сервисы*->*WebHook* \n\nДалее настроить URL для подключения к боту. Этот бот работает по адресу:\n\n`https://callhistory-bot.herokuapp.com/bot.php` \n\nВ поле *Секрет* надо будет ввести секретный код, который выдаст бот после регистрации.\n\nПосле этого можно выбрать желаемые значения, отправляемые вместе с файлом записи. Они отобразятся в одном сообщении вместе с записью голоса.\n\nЕсли у вас уже есть записи в памяти телефона - выгрузите их все сразу кнопкой в самом низу *\"Выгрузить еще раз\"*. Файлы добавятся в Telegram.\nЕсли у вас не было записей до этого - просто пользуйтесь телефоном как обычно, записи будут выгружены в соответствии с настройками приложения ACR.\n", 'Markdown', $ru_keyboard);
		mysqli_query($db, "insert into users (chat_id, acr_secret, language) values (".$chat_id.", SHA2('".$acr_secret."', 256), '".$user_lang."')");
		sleep(5);
		sendFormattedMessage($chat_id, "Вы зарегистрированы!\n\nВаш секретный код:\n`".$acr_secret."`\n\nВведите его в поле secret в настройках Web Hook в ACR. Это идентифицирует вас и именно ваши записи.", 'Markdown', $ru_keyboard);
	}
	
	mysqli_free_result($sql);	
}

if ($message == '🛠 Настройки бота') {
	sendFormattedMessage($chat_id, "📳 *Тихий режим*\nЭта настройка позволит получать записи разговоров без уведомлений.\n\n🤔 *Помощь в настройке*\nПросмотрите начальное сообщение чтобы настроить ACR заново.", 'Markdown', $ru_settings_keyboard);
}
if ($message == '🛠 Bot settings') {
	sendFormattedMessage($chat_id, "📳 *Silent mode*\nThis setting will switch notifications on and off when you receive your recording.\n\n🤔 *Setup help*\nSee starting message to set up ACR again.", 'Markdown', $en_settings_keyboard);
}

if ($message == '🆔 Секретный код') {
	$secret =  base_convert($chat_id, 10, 36);
	sendFormattedMessage($chat_id, "Ваш секретный код:\n\n```".$secret."```\n\nВведите его в поле secret в настройках Web Hook в ACR. Это идентифицирует вас и именно ваши записи.", 'Markdown', $ru_keyboard);
}

if ($message == '💱 Сменить язык') {
	mysqli_query($db, "update users set language='en' where chat_id=".$chat_id);
	sendFormattedMessage($chat_id, "Language changed!", 'Markdown', $en_keyboard);
}
if ($message == '💱 Change language') {
	mysqli_query($db, "update users set language='ru' where chat_id=".$chat_id);
	sendFormattedMessage($chat_id, "Язык изменен!", 'Markdown', $ru_keyboard);
}

if ($message == '/givemeid') {
	sendMessage($chat_id, $chat_id.' | '.$user);
}

if ($message == '🤔 Помощь в настройке' || $message == '🤔 Setup help') {
	sendFormattedMessage($chat_id, "Привет! Сейчас я покажу как настроить ACR для пользования ботом.\n\nДля начала надо зайти в пункт меню:\n*Настройки*->*Облачные сервисы*->*WebHook* \n\nДалее настроить URL для подключения к боту. Этот бот работает по адресу:\n\n`https://callhistory-bot.herokuapp.com/bot.php` \n\nВ поле *Секрет* надо будет ввести секретный код, который выдаст бот после регистрации.\n\nПосле этого можно выбрать желаемые значения, отправляемые вместе с файлом записи. Они отобразятся в одном сообщении вместе с записью голоса.\n\nЕсли у вас уже есть записи в памяти телефона - выгрузите их все сразу кнопкой в самом низу *\"Выгрузить еще раз\"*. Файлы добавятся в Telegram.\nЕсли у вас не было записей до этого - просто пользуйтесь телефоном как обычно, записи будут выгружены в соответствии с настройками приложения ACR.\n", 'Markdown', $ru_keyboard);
}

//кладем данные из ACR в массив параметров
$ACR_fields = array(
	"date" => date('d.m.Y, H:i:s', $_POST['date']),
	"duration" => $_POST['duration']%1000,
	"important_flag" => $_POST['important'],
	"note" => $_POST['note'],
	"phone" => $_POST['phone'],
	"contact" => $_POST['contact']
);

//форматируем входные данные (если они есть)
if ($user_lang = 'ru') {
	if ($_POST['direction'] == 1) {
		$ACR_fields['direction'] = 'Исходящий';
	} else if ($_POST['direction'] == 0){
		$ACR_fields['direction'] = 'Входящий';
	}

	if ($ACR_fields['date']) {
		$ACR_fields['date'] = 'Дата: '.$ACR_fields['date'];
	}
	if ($ACR_fields['phone']) {
		$ACR_fields['phone'] = 'Номер: '.urldecode($ACR_fields['phone']);
	}
	if ($ACR_fields['contact']) {
		$ACR_fields['contact'] = 'Имя контакта: '.urldecode($ACR_fields['contact']);
	}
	if ($ACR_fields['note']) {
		$ACR_fields['note'] = 'Заметка: '.urldecode($ACR_fields['note']);
	}
	if ($ACR_fields['duration']) {
		$ACR_fields['duration'] = 'Длительность: '.floor($ACR_fields['duration']).' секунд';
	}
	if ($ACR_fields['important_flag']) {
		$ACR_fields['important_flag'] = '#важный';
	}
}

//чистим выключенные параметры (не будем их отсылать с отчетом)
$report = array_filter($ACR_fields);
$final_report = implode("\n", $report);

//получили что-то от ACR? отправляем запись!
if ($_POST['source'] == 'ACR') {
	$voice_file = $_FILES['file'];
	
	$query = mysqli_query($db, "select * from users where acr_secret=SHA2('".$_POST['secret']."', 256)");
	while ($sql = mysqli_fetch_object($query)) {
		$chat_id = $sql->chat_id;
		$secret = $sql->acr_secret;
	}
	
	if ($secret == hash('sha256', $_POST['secret'])) {
		sendVoice($chat_id, $voice_file, $_POST['duration']/1000, $final_report);
	}
	mysqli_free_result($sql);
}

//----------------------------------------------------------------------------------------------------------------------------------//

//отправка форматированного сообщения
function sendFormattedMessage($chat_id, $message, $markup, $keyboard)
{
	if ($keyboard === NULL) {
		file_get_contents($GLOBALS['api'].'/sendMessage?chat_id='.$chat_id.'&text='.urlencode($message).'&parse_mode='.$markup);
	} else {
		file_get_contents($GLOBALS['api'].'/sendMessage?chat_id='.$chat_id.'&text='.urlencode($message).'&parse_mode='.$markup.'&reply_markup='.json_encode($keyboard));
	}
}

//удаление сообщения
function deleteMessage($chat_id, $message_id)
{
	file_get_contents($GLOBALS['api'].'/deleteMessage?chat_id='.$chat_id.'&message_id='.$message_id);
}

//отправка простого сообщения
function sendMessage($chat_id, $message, $keyboard)
{
	if ($keyboard === NULL) {
		file_get_contents($GLOBALS['api'].'/sendMessage?chat_id='.$chat_id.'&text='.urlencode($message));
	} else {
		file_get_contents($GLOBALS['api'].'/sendMessage?chat_id='.$chat_id.'&text='.urlencode($message).'&reply_markup='.json_encode($keyboard));
	}
}

//отправка разговора
function sendVoice($chat_id, $voice, $duration, $caption) {
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
	file_get_contents($GLOBALS['api'].'/sendChatAction?chat_id='.$chat_id.'&action=upload_voice');
}

mysqli_close($db);
?>
