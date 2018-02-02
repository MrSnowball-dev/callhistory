<?php
echo '<script>console.log(\'Start script\')</script>';
header('Content-Type: text/html; charset=utf-8');

date_default_timezone_set('Europe/Moscow');

$token = '206038081:AAFEFCv3oCo5JjlrBTzBmj7N-6g8yqNUeQc';
$api = 'https://api.telegram.org/bot'.$token;

//ключи API к google, на всякий
$google_api = 'AIzaSyCUZZZy89L0YEWAztQjbslPJEl8qHSFShw';
$google_cx = '013003578290987184015:nuvp1p9mlyy';

//открываем соединение с БД
$db = mysqli_connect('gi6kn64hu98hy0b6.chr7pe7iynqr.eu-west-1.rds.amazonaws.com:3306', 'dmhkih38p43a23hr', 'z1f1v1ggkkwj1flt', 'zwx3a04852rid4xc');
if (!$db) {
    echo '<script>console.log(\'Unable to connect to MySQL!\')</script>' . PHP_EOL;
    echo '<script>console.log(\'MySQL Errnum: \')</script>' . mysqli_connect_errno() . PHP_EOL;
    echo '<script>console.log(\'MySQL Error: \')</script>' . mysqli_connect_error() . PHP_EOL;
} else {
	echo '<script>console.log(\'MySQL connected\')</script>';
}

$output = json_decode(file_get_contents('php://input'), TRUE); //сбда получаем всюхуйню от телеграма
$chat_id = $output['message']['chat']['id']; //отделяем id чата, откуда идет обращение к боту, для нарконфы -1001058554435
$message_id = $output['message']['message_id']; //id сообщения, которое нужно редактировать
$message = $output['message']['text']; //сам текст сообщения
$user = $output['message']['from']['username']; //сюда кладем юзернейм человек, обратившегося к боту
$user_firstname = $output['message']['from']['first_name'];
$user_id = $output['message']['from']['id']; //id юзера, для банов

$callback_query = $output['callback_query']; //сюда получаем все, что приходит от inline клавиатуры
$callback_data = $callback_query['data']; //ответ от клавиатуры идет сюда
$callback_chat_id = $callback_query['message']['chat']['id']; //id чата, где был вызов клавиатуры
$callback_message_id = $callback_query['message']['message_id']; //id того сообщения, в котором нажата кнопка клавиатуры

$message = mb_strtolower($message); //этим унифицируем любое входящее сообщение в нижний регистр для дальнейшей обработки без ебли с кейсами

//--ДАЛЬШЕ ЛОГИКА БОТА--//

//включение/выключение бота
$query = mysqli_query($db, 'select * from chat_stats where id=1');
while ($stats = mysqli_fetch_object($query)) {
	$status = $stats->status;
}

if ($message == '/switch') {
	if ($status == '1') {
		mysqli_query($db, 'update chat_stats set status=0 where id=1');
		sendMessage($chat_id, 'Бот выключен.');
	} elseif ($status == '0') {
		mysqli_query($db, 'update chat_stats set status=1 where id=1');
		sendMessage($chat_id, 'Бот включен!');
	}
}

//если бот включен - выполням обработку сообщений
if ($status == '1') {

	//если находим в тексте аву, славона, а также команды для посыла, то собсна посылаем нахуй
	//фраза "отсылки" каждый раз разная, выходит забавно

	if (is_int(stripos($message, 'аватар')) || is_int(stripos($message, 'аван')) || is_int(stripos($message, 'права')) || is_int(stripos($message, 'авалон')) || is_int(stripos($message, 'трава')) || is_int(stripos($message, 'авари')) || is_int(stripos($message, 'красава')) || is_int(stripos($message, 'лава'))) {
		return;
	} elseif (is_int(stripos($message, ' ава ')) || is_int(stripos($message, 'ава ')) || is_int(stripos($message, ' ава')) || is_int(stripos($message, ' славон ')) || is_int(stripos($message, 'славон ')) || is_int(stripos($message, ' славон')) || $message == 'ава' || $message == 'славон' || $message == '/ava' || $message == '/ava@narklair_bot') {
		$nah_array = ["АВА", "ИДИ", "НАХУЙ"];
		shuffle($nah_array);
		$final_nah = implode(" ", $nah_array);
		sendMessage($chat_id, $final_nah);
		$query = 'update chat_stats set times_screwed=times_screwed+1 where id=1';
		mysqli_query($db, $query);
	}

	//есть возможность выразить недовольство ссылкой вручную :)
	if ($message == '/o4ko' || $message == '/o4ko@narklair_bot') {
		$o4ko_array = ["ЗАСУНЬ", "ЭТОТ ЛИНК", "СЕБЕ", "В ЖОПУ"];
		shuffle($o4ko_array);
		$final_o4ko = implode(" ", $o4ko_array);
		sendMessage($chat_id, $final_o4ko);
	}

	//mysql запросы для апдейта базы прямо из бота
	if (is_int(stripos($message, '/mysql'))) {
		$query = substr($message, 7);
		mysqli_query($db, $query);
		sendMessage($chat_id, 'Доне, '.$query);
	}

	//поиск картинки в Google по запросу
	if ((is_int(stripos($message, '/img@narklair_bot'))) && !(is_int(stripos($message, 'http')))) {
		$query = substr($message, 18).' мем';
		mysqli_query($db, 'update chat_stats set imgquery="'.$query.'" where id=1');
		sendGoogleImage($chat_id, $query, '0');
	}

	if (is_int(stripos($message, '/more@narklair_bot'))) {
		sendGoogleImage($chat_id, '', '1');
	}

	//MrSnowball сделал это :D
	if (is_int(stripos($message, '/me '))) {
		$me_message = substr($message, 4);
		deleteMessage($chat_id, $message_id);
		if ($user) {
			sendFormattedMessage($chat_id, '`'.$user." ".$me_message.'`', 'Markdown');
		} else {
			sendFormattedMessage($chat_id, '`'.$user_firstname." ".$me_message.'`', 'Markdown');
		}
	}

	//сколько времени?
	if (is_int(stripos($message, '/time'))) {
		sendMessage($chat_id, 'Сейчас в Москве: '.date("H:i:s", strtotime('now')));
	}

	//блок против авы
	if ($user == 'InnerLight') {
		//баяны не пройдут
		if (is_int(stripos($message, 'баян')) || is_int(stripos($message, 'ба́ян')) || is_int(stripos($message, 'бая́н')) || is_int(stripos($message, 'бaян')) || is_int(stripos($message, 'бaяh')) || is_int(stripos($message, 'бaяh')) || is_int(stripos($message, 'баяh')) || is_int(stripos($message, 'ба́я́н'))) {

			//записываем баян в стопку
			mysqli_query($db, 'update chat_stats set bajans=bajans+1 where id=1');

			//получаем счетчик баянов
			$query = mysqli_query($db, 'select * from chat_stats where id=1');
			while ($stats = mysqli_fetch_object($query)) {
				$bajans = $stats->bajans;
			}
			if ($bajans < 3) {
				$remaining_bajans = 3 - $bajans;
				sendMessage($chat_id, 'Ава, внимание! До твоего РО на 1 час осталось '.$remaining_bajans.' баянов.');
			} elseif ($bajans >= 3) {
				mysqli_query($db, 'update chat_stats set bajans=0, avabans=avabans+1 where id=1');
				$banlift = date("d.m.y \в H:i:s", strtotime('+1 hours'));
				sendMessage($chat_id, 'Ава был награжден РО на час за использование слова "баян" слишком много раз. Бан истекает '.$banlift.'. Счетчик баянов был сброшен.');
				banUser($chat_id, $user_id);
			}
		}

		//игнорщики не пройдут
		if (is_int(stripos($message, 'игнор'))) {
			sendMessage($chat_id, 'да ты заебал уже, повтори вопрос идиотина!');
			$query = 'update chat_stats set ignore_warnings=ignore_warnings+1 where id=1';
			mysqli_query($db, $query);
		}

		//если ава постит ссылку, бот удаляет её
		if (is_int(stripos($message, 'http'))) {
			deleteMessage($chat_id, $message_id);
			$query = 'update chat_stats set hidden_links=hidden_links+1 where id=1';
			mysqli_query($db, $query);
		}

		//всем действительно похуй мнение авы насчет вин10
		if (is_int(stripos($message, 'вин10')) || (is_int(stripos($message, 'win10')))) {
			sendMessage($chat_id, 'как жаль, что всем похуй');
			$query = 'update chat_stats set times_screwed=times_screwed+1 where id=1';
			mysqli_query($db, $query);
		}

		//известно, что ава любит всех заебывать вопросами. Решение перед вами
		if (is_int(stripos($message, '?')) && !(is_int(stripos($message, 'http')))) {
			$gugl_array = ["АВА", "ЗАЕБАЛ", "СПРАШИВАЙ ГУГЛ"];
			shuffle($gugl_array);
			$final_gugl = implode(" ", $gugl_array);
			sendMessage($chat_id, $final_gugl);
			$query = 'update chat_stats set questions_asked=questions_asked+1 where id=1';
			mysqli_query($db, $query);
		}
	}

	//автоматический посылатель нахуй, надо лишь выбрать ник
	//TODO: заполнение клавиатуры актуальными участниками конфы
	if (is_int(stripos($message, 'идиома')) || is_int(stripos($message, 'идиот')) || is_int(stripos($message, 'идилия')) || is_int(stripos($message, 'приди')) || is_int(stripos($message, 'видимо')) || is_int(stripos($message, 'сиди')) || is_int(stripos($message, 'идим')) || is_int(stripos($message, 'идиу')) || is_int(stripos($message, 'идик')) || is_int(stripos($message, 'идич')) || is_int(stripos($message, 'идиз')) || is_int(stripos($message, 'идид')) || is_int(stripos($message, 'идиц')) || is_int(stripos($message, 'идил')) || is_int(stripos($message, 'идих')) || is_int(stripos($message, 'идий')) || is_int(stripos($message, 'идир')) || is_int(stripos($message, 'идия')) || is_int(stripos($message, 'идис')) || is_int(stripos($message, 'идив')) || is_int(stripos($message, 'идиж')) || is_int(stripos($message, 'идит')) || is_int(stripos($message, 'идиа')) || is_int(stripos($message, 'идию')) || is_int(stripos($message, 'идиш'))
		|| is_int(stripos($message, 'види'))) {

		return;

	} elseif (is_int(stripos($message, ' идите')) || is_int(stripos($message, 'иди')) || is_int(stripos($message, 'пойти')) || $message == '/pnah' || $message == '/pnah@narklair_bot') {
		$keyboard = [
			'inline_keyboard' => [[['text' => 'Нахуй!', 'callback_data' => '1']], 
								  [['text' => 'Не нахуй!' , 'callback_data' => '2']]],
		];
		sendInlineMessage($chat_id, 'Нахуй?', $keyboard);
		$query = 'update chat_stats set fuck_yous=fuck_yous+1 where id=1';
			mysqli_query($db, $query);
	}

	//проверка, какая кнопка была нажата
	//будет проверять по юзернейму
	switch ($callback_data) {
		case '1':
			updateMessage($callback_chat_id, $callback_message_id, 'Нахуй!');
			break;

		case '2':
			updateMessage($callback_chat_id, $callback_message_id, 'Не нахуй. Ну ладно.');
			break;
	}

	//выводим собранную статистику на экран
	if ($message == '/stat' || $message == '/stat@narklair_bot') {

		$query = mysqli_query($db, 'select * from chat_stats where id=1');

		while ($stats = mysqli_fetch_object($query)) {
			$hidden_links = $stats->hidden_links;
			$times_screwed = $stats->times_screwed;
			$ignore_warnings = $stats->ignore_warnings;
			$questions_asked = $stats->questions_asked;
			$fuck_yous = $stats->fuck_yous;
			$bajans = $stats->bajans;
			$avabans = $stats->avabans;
		}

		sendMessage($chat_id, "Cтатистика: \n\n".$hidden_links." ссылок от авы скрыто\n".$times_screwed." километров ава идет нахуй\n".$ignore_warnings." раз аву проигнорили по его словам\n".$questions_asked." тупых вопросов задано авой\n".$fuck_yous." раз кого-то послали нахуй\n".$avabans." раз ава был в РО\n"."Текущий СБА: ".$bajans);

		mysqli_free_result($stats);
	}

	//денчик - охуенчик
	if (is_int(stripos($message, 'денчик')) && ($user != 'InnerLight')) {
		sendMessage($chat_id, 'Охуенчик!');
	}

	//вуфль - хуюфль
	if (is_int(stripos($message, 'вуфл')) && ($user != 'InnerLight')) {
		sendMessage($chat_id, 'Хуюфль!');
	}

	//снижок
	if (is_int(stripos($message, 'снижок')) && ($user != 'InnerLight')) {
		sendMessage($chat_id, '*Снижок - ник, официально разрешенный на территории РФ.');
	}

	//Где ляля?
	if (is_int(stripos($message, 'ляля')) || is_int(stripos($message, ' лялю')) || is_int(stripos($message, ' ляле')) || is_int(stripos($message, ' ляли')) || is_int(stripos($message, 'лялю ')) || is_int(stripos($message, 'ляле ')) || is_int(stripos($message, 'ляли '))) {
		sendMessage($chat_id, 'Ляли больше нет.');
		sendLalaPhoto($chat_id);
	}

	//Что думает Верник?
	if (is_int(stripos($message, 'думает верник'))) {
		$vernick_pic = array('http://i.imgur.com/rhX8nY8.png', 'http://i.imgur.com/uCyrbDj.png', 'http://i.imgur.com/hxbtEQf.png');
		$random_vernick = array_rand(array_flip($vernick_pic));
		sendVernick($chat_id, $random_vernick);
	}

}

//----------------------------------------------------------------------------------------------------------------------------------//

//отправка форматированного сообщения
function sendFormattedMessage($chat_id, $message, $markup)
{
	file_get_contents($GLOBALS['api'].'/sendMessage?chat_id='.$chat_id.'&text='.urlencode($message).'&parse_mode='.$markup);
}


//отправка сообщения с клавиатурой
function sendInlineMessage($chat_id, $message, $keyboard)
{
	file_get_contents($GLOBALS['api'].'/sendMessage?chat_id='.$chat_id.'&text='.urlencode($message).'&reply_markup='.json_encode($keyboard));
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

//редактирование сообщения со вставкой новой клавиатуры
function updateInlineMessage($callback_chat_id, $callback_message_id, $new_message, $new_keyboard)
{
	file_get_contents($GLOBALS['api'].'/editMessageText?chat_id='.$callback_chat_id.'&message_id='.$callback_message_id.'&text='.urlencode($new_message).'&reply_markup='.json_encode($new_keyboard));
}

//Ляли больше нет.
function sendLalaPhoto($chat_id)
{
	file_get_contents($GLOBALS['api'].'/sendPhoto?chat_id='.$chat_id.'&photo=https://i.ytimg.com/vi/RRcI--QZNow/hqdefault.jpg');
}

//А что думает Верник?
function sendVernick($chat_id, $photo)
{
	file_get_contents($GLOBALS['api'].'/sendPhoto?chat_id='.$chat_id.'&photo='.$photo);
}

//механизм бана пользоателей
function banUser($chat_id, $user_id)
{
	file_get_contents($GLOBALS['api'].'/restrictChatMember?chat_id='.$chat_id.'&user_id='.$user_id.'&until_date='.strtotime('+1 hour').'&can_send_messages=false');
}

//отправка простого сообщения
function sendMessage($chat_id, $message)
{
	file_get_contents($GLOBALS['api'].'/sendMessage?chat_id='.$chat_id.'&text='.urlencode($message));
}

//поиск картинок в GUGL
function sendGoogleImage($chat_id, $queue, $moreFlag)
{
	if ($moreFlag == '0') {
		$search_result = json_decode(file_get_contents('https://www.googleapis.com/customsearch/v1?key=AIzaSyCUZZZy89L0YEWAztQjbslPJEl8qHSFShw&cx=013003578290987184015:nuvp1p9mlyy&q='.urlencode($queue).'&gl=ru&num=1&start=1&searchType=image&highRange=100'), TRUE);	
		$image = $search_result['items'][0]['link'];
		file_get_contents($GLOBALS['api'].'/sendChatAction?chat_id='.$chat_id.'&action=upload_photo');
		file_get_contents($GLOBALS['api'].'/sendPhoto?chat_id='.$chat_id.'&photo='.$image.'&caption='.urlencode('Наберите /more чтобы увидеть больше'));

		mysqli_query($GLOBALS['db'], 'update chat_stats set imgcount=2 where id=1');
	} elseif ($moreFlag == '1') {
		$query = mysqli_query($GLOBALS['db'], 'select imgcount, imgquery from chat_stats where id=1');
		while ($stats = mysqli_fetch_object($query)) {
			$imgcount = $stats->imgcount;
			$last_queue = $stats->imgquery;
		}

		$search_result = json_decode(file_get_contents('https://www.googleapis.com/customsearch/v1?key=AIzaSyCUZZZy89L0YEWAztQjbslPJEl8qHSFShw&cx=013003578290987184015:nuvp1p9mlyy&q='.urlencode($last_queue).'&gl=ru&num=1&start='.$imgcount.'&searchType=image&highRange=100'), TRUE);

		$image = $search_result['items'][0]['link'];
		file_get_contents($GLOBALS['api'].'/sendChatAction?chat_id='.$chat_id.'&action=upload_photo');
		file_get_contents($GLOBALS['api'].'/sendPhoto?chat_id='.$chat_id.'&photo='.$image.'&caption='.urlencode('Наберите /more чтобы увидеть больше'));
		
		mysqli_query($GLOBALS['db'], 'update chat_stats set imgcount=imgcount+1 where id=1');
	}
}

mysqli_close($db);
echo '<script>console.log(\'End script\')</script>';
?>