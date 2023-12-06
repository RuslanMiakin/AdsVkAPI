<?php

/*
	github разработчика https://github.com/RuslanMiakin
*/



ini_set('display_errors', 'Off');

$sourceRequest = !empty($_POST['source']) ? $_POST['source'] : '';
//Поля из формы/запроса
$client_id = !empty($_POST['client_id']) ? $_POST['client_id'] : '';
$client_secret = !empty($_POST['client_secret']) ? $_POST['client_secret'] : '';
$group_id = !empty($_POST['group_id']) ? $_POST['group_id'] : '';
//Заголовок/краткий текст/ссылка
if($sourceRequest == 'site'){
  $arrInfo = !empty($_POST['info']) ? json_decode($_POST['info'], true) : '';
}else{
  $arrInfo = !empty($_POST['info'][0]) ? json_decode($_POST['info'][0], true) : '';
}

//Адреса методов ads.vk.com
$API_VK = 'https://ads.vk.com';
$API_PAGE_VK_URLS = '/api/v2/urls.json';
$API_PAGE_VK_CONENT = '/api/v2/content/static.json';
$API_PAGE_VK_AUTH = '/api/v2/oauth2/token.json';
$API_PAGE_VK_ADVERTISEMENTS = '/api/v2/ad_groups/' . $group_id . '/banners.json';
$API_VK_CONTENT = $API_VK . $API_PAGE_VK_CONENT;
$API_VK_AUTH = 	$API_VK	. $API_PAGE_VK_AUTH;
$API_VK_ADVERTISEMENTS = $API_VK . $API_PAGE_VK_ADVERTISEMENTS;
$API_VK_URLS = $API_VK . $API_PAGE_VK_URLS;

//Текущее время на сервере
$dateToday = date("Y-m-d H:i:s");


//Логируем входные данные
logService($dateToday, $arrInfo, 'Поля информации');
logService($dateToday, $_FILES, 'Файлы в запросе');

//Подсказки
$FIELD_EXISTS = '<p>Требуется заполнить все поля.</p>';
$FIELD_SUCCESS = '<p>Объявление загружено</p>';

//Основная логика
if(!empty($client_id) && !empty($client_secret) 
	&& !empty($group_id) && !empty($_FILES) && !empty($arrInfo[0])){

	//Проверяем, если ранее авторизации небыло, делаем запрос на получение токенов 
	//(создаем фаил с названием client_id)
	if(!checkFileExist($client_id)){
		$authResult = auth($API_PAGE_VK_AUTH, $API_VK_AUTH, $client_id, $client_secret);
		logService($dateToday, $authResult, 'Первичная авторизация');
	}

	$access_token = getTokens($client_id, 0);
	$refresh_token = getTokens($client_id, 1);
	
	create($_FILES, $access_token, $refresh_token, $client_id, 
	$client_secret, $API_VK_CONTENT, $dateToday, $arrInfo[0], 
	$API_VK_URLS, $API_VK_ADVERTISEMENTS, 
	$API_PAGE_VK_ADVERTISEMENTS,$group_id,$FIELD_SUCCESS,$API_PAGE_VK_AUTH,$API_VK_AUTH);

}else{
	echo $FIELD_EXISTS;
}




function create($files_data, $access_token, 
	$refresh_token, $client_id, 
	$client_secret, $url, $dateToday, $dataInfo,$urlUrls,
	$API_VK_ADVERTISEMENTS,$API_PAGE_VK_ADVERTISEMENTS,$group_id,$FIELD_SUCCESS,$API_PAGE_VK_AUTH,$API_VK_AUTH){

	$arraySizeForm = array(
		'icon_256x256' => array(
			'width' => 256,
			'height' => 256
		),
		'image_1080x607' => array(
			'width' => 1080,
			'height' => 607
		),
		'image_600x600' => array(
			'width' => 600,
			'height' => 600
		),
		'image_1080x1350' => array(
			'width' => 1080,
			'height' => 1350
		)
	);


	//Загрузка файлов в API VK Content
	$arrayFieldsAD = array();

	$arrayFieldsAD['name'] = 'Объявление ' . $dateToday;
	
	foreach ($files_data as $key => $value) {

		$access_token = getTokens($client_id, 0);
		//Получаем размеры изображения
		$imageSize = getimagesize($value['tmp_name']);
		$width = $imageSize[0];
		$height = $imageSize[1];

		foreach ($arraySizeForm as $keyForm => $valueForm) {
			if($key == $keyForm && ($width != $valueForm['width'] || $height != $valueForm['height'])){
				return apiError("<p>Внимание! Изображение $key имеет не правильный размер. $width x $height</p>");
			}
		}

		$resultRecuerst = uploadFileInContent($key, 
			0, 
			$url, 
			$width, 
			$height, 
			$access_token);
		
		logService($dateToday, $resultRecuerst, 'Загрузка изображения в content');
		//Если истек срок действия токена, перевыпускаем. 
		if(!empty($resultRecuerst['error']['code']) && ($resultRecuerst['error']['code'] == 'expired_token' 
		|| $resultRecuerst['error']['code'] == 'invalid_token')){
			$updateTokens = refreshAuth($API_PAGE_VK_AUTH, 
				$API_VK_AUTH, $refresh_token, $client_id, $client_secret);
			logService($dateToday, $updateTokens, 'Перевыпуск токена');
			//Если токен успешно получен, делаем запрос еще раз с новым access_token.
			if(!empty($updateTokens['access_token'])){
				$resultRecuerst = uploadFileInContent($key, 
					0, 
					$url, 
					$width, 
					$height, 
					$updateTokens['access_token']);
				logService($dateToday, $updateTokens, 'Загрузка изображения в content (с новым токеном)');
			}else{
				//Если токен не получен выводим ответ из API 
				return apiError($updateTokens);
			}
		}
		//Если запрос не удалось выполнить по какой-то другой причине, выводим Ошибку
		if(empty($resultRecuerst['id'])){
			return apiError($resultRecuerst);
		}

		//Если запрос успешно выполнен, то запишим Id полученного изображения в массив 
		$arrayFieldsAD['content'][$key] = array('id' => $resultRecuerst['id']);
		
	}

	//Заполняем описание
	foreach ($dataInfo as $key => $value) {
		if($key != 'primary'){
			$arrayFieldsAD['textblocks'][$key] = array('text' => $value);
		}
	}

	$access_token = getTokens($client_id, 0);

	//Отправляем ссылку на проверку/записываем id
	$primaryId = getIdByUrl($dataInfo['primary'], $access_token, $urlUrls);

	logService($dateToday, $primaryId, 'Получение id ссылки');

	$arrayFieldsAD['urls']['primary']['id'] = $primaryId;

	//Выбираем действие по кнопке
	$arrayFieldsAD['textblocks']['cta_sites_full']['text'] = 'visitSite';

	// Создание объявления 
	$resultRecuerst = createAdvertisements(json_encode($arrayFieldsAD, JSON_UNESCAPED_UNICODE), 
		$access_token, 
		$API_PAGE_VK_ADVERTISEMENTS, 
		$API_VK_ADVERTISEMENTS,$group_id);
	logService($dateToday, $resultRecuerst, 'Создание объявления');
	//Обработка обновления токена

	if(!empty($resultRecuerst['error']['code']) && ($resultRecuerst['error']['code'] == 'expired_token' 
		|| $resultRecuerst['error']['code'] == 'invalid_token')){
		$updateTokens = refreshAuth($API_PAGE_VK_AUTH, 
				$API_VK_AUTH, $refresh_token, $client_id, $client_secret);
		logService($dateToday, $updateTokens, 'Перевыпуск токена');
	if(!empty($updateTokens['access_token'])){
		$resultRecuerst = createAdvertisements($arrayFieldsAD, 
			$updateTokens['access_token'], 
			$API_PAGE_VK_ADVERTISEMENTS, 
			$API_VK_ADVERTISEMENTS);
		logService($dateToday, $resultRecuerst, 'Создание объявления (с новым токеном)');
	}else{
		//Если токен не получен выводим ответ из API 
		return apiError($updateTokens);
	}}

	//Если запрос не удалось выполнить по какой-то другой причине, выводим Ошибку
	if(empty($resultRecuerst['id'])){
		return apiError($resultRecuerst);
	}

	return apiError($FIELD_SUCCESS);
}


//Создаем объявление в указанной группе (group_id)
function createAdvertisements($postField, $access_token, $page, $url, $group_id){
	$header = array(
			"POST /api/v2/ad_groups/$group_id/banners.json",
        	"Host: ads.vk.com",
        	"Authorization: Bearer $access_token"
        );
	$curlResult = curlRecuest($url,$header,$postField);
    return $curlResult;
}

//Создание/получение id ссылки
function getIdByUrl($postUrl, $access_token,$url){
	$header = array(
			"POST /api/v2/urls.json",
        	"Host: ads.vk.com",
        	"Authorization: Bearer $access_token"
		);
	$postField = "{
		\"url\":\"$postUrl\"
	}";
	$resultRecuerst = curlRecuest($url,$header,$postField);
	return $resultRecuerst['id'];
}


//Добавление токенов авторизации accept/refresh 
function addTokens($token, $fileName){
	$fh = fopen($fileName . '.txt', 'a');
	$parts = $token . "\n";
	fwrite($fh, $parts);
	fclose($fh);
	chmod($fileName . '.txt', 0600);
}

//Получение токенов из файла по номеру кабинета (client_id)
function getTokens($fileName, $num){
	$array = array();
	$file = fopen($fileName . '.txt', 'r');
		for ($i = 0; $i <= 1; $i++) {
    		$array[$i] = fgets($file);
		}
	fclose($file);
	$data = json_decode(str_replace('\n', ' ', json_encode($array[$num])));
	return $data;
}

//Проверка есть ли фаил с наименованием (client_id)
function checkFileExist($fileName){
	return file_exists($fileName . '.txt');
}

function deleteFileTokens($fileName){
	unlink($fileName . '.txt');
}

//Запись лога запросов 
function logService($dateToday, $data, $type){
	$dataCorrect = print_r($data, true);
	$fh = fopen('log.txt', 'a');
	$parts = 'Дата:' . $dateToday . "\n" . 'Метод:' . $type . "\n" . 'Запрос:' . $dataCorrect 
	. "\n----------------------------------\n";
	fwrite($fh, $parts);
	fclose($fh);
	chmod('log.txt', 0600);
}

//Загрузка изображений в API контент. Эмулируем загрузку изображения с фомры
function uploadFileInContent($name, $i=0, $url, $width, $height, $access_token){
        $postField = array();
        $tmpfile = $_FILES[$name]['tmp_name'];
        $filename = basename($_FILES[$name]['name']);
        $postField['file'] =  curl_file_create($tmpfile, $_FILES[$name]['type'], $filename);
        $postField['data'] = "{\"width\":$width, \"height\":$height}";
        $headers = array(
        	"Host: ads.vk.com",
        	"Content-Type: multipart/form-data",
        	"Authorization: Bearer $access_token"
        );
        $curlResult = curlRecuest($url,$headers,$postField);
        return $curlResult;
}



//Первичная авторизация (получение access_token)
function auth($page, $url, $client_id, $client_secret){

	$header = array(
		"POST ". $page ." HTTP/1.1", 
		"Content-Type: application/x-www-form-urlencoded",
		"Host: ads.vk.com"
	);

	$resultResponse = curlRecuest($url, $header, 
		"grant_type=client_credentials&client_id=$client_id&client_secret=$client_secret");

	if(empty($resultResponse['access_token']) 
		|| empty($resultResponse['refresh_token'])){
		return apiError($resultResponse);
	}

	if(checkFileExist($client_id)){
		deleteFileTokens($client_id);
	}


	addTokens($resultResponse['access_token'], $client_id);
	addTokens($resultResponse['refresh_token'], $client_id);

	return $resultResponse['access_token'];

}

//Обновление токена (используем refresh_token)
function refreshAuth($page, $url, $refresh_token, $client_id, $client_secret){

	$header = array(
		"POST ". $page ." HTTP/1.1", 
		"Content-Type: application/x-www-form-urlencoded",
		"Host: ads.vk.com"
	);

	$resultResponse = curlRecuest($url, $header, 
		"grant_type=refresh_token&refresh_token=$refresh_token&client_id=$client_id&client_secret=$client_secret");
	print_r($resultResponse);

	if(empty($resultResponse['access_token']) 
		|| empty($resultResponse['refresh_token'])){
		return apiError($resultResponse);
	}

	if(checkFileExist($client_id)){
		deleteFileTokens($client_id);
	}

	addTokens($resultResponse['access_token'], $client_id);
	addTokens($resultResponse['refresh_token'], $client_id);

	return $resultResponse;
}

//Метод отправки запросов
function curlRecuest($url, $headers, $postField){
	$curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl_handle, CURLOPT_POST, TRUE);
    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $postField);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($curl_handle);
    logService('сейчас',  $response, 'Запросы');
    $response = json_decode($response,true);
    curl_close($curl_handle);
    return $response;
}

//Обработка Ошибок
function apiError($data){
	print_r($data);
}


?>

