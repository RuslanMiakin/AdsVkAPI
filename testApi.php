<?php 

$API_VK = 'https://ads.vk.com';
$API_PAGE_VK_URLS = '/api/v2/urls.json';
$API_PAGE_VK_CONENT = '/api/v2/content/static.json';
$API_PAGE_VK_AUTH = '/api/v2/oauth2/token.json';
$API_VK_CONTENT = $API_VK . $API_PAGE_VK_CONENT;
$API_VK_AUTH = 	$API_VK	. $API_PAGE_VK_AUTH;
$API_VK_URLS = $API_VK . $API_PAGE_VK_URLS;

authTest($API_PAGE_VK_AUTH, $API_VK_AUTH,'iylsawlWHB707Dpt', "caluvRfeJ1q3XbA3awlk06rCP3cugYQq1lZWJb7oAlLwpi67emtgM7mJh7Wn­­kNWCWmsD0UmwwgrNgf7qv7ybndCSKjttXbIn4CNNAnu58xt3wIozXDn3SFS­5­VR80Z077IfHB4taY6825eTrXCECXUADB4L5Ayar1fWgoeykl3tdT01Cb4K­Pe­KQazx73Xs8Smd4IOffUpO3Av4kfZSb60BCiezJnVuRrzso83UjzwrPE2R­vzj­RYk");
function authTest($page, $url, $client_id, $client_secret){

	$header = array(
		"POST ". $page,
		"Host: ads.vk.com",
		"Content-Type: application/x-www-form-urlencoded"
	);

	$resultResponse = curlRecuest($url, $header, "grant_type=client_credentials&client_id=$client_id&client_secret=$client_secret");


	$data = json_decode(str_replace('\u00', '', json_encode('caluvRfeJ1q3XbA3awlk06rCP3cugYQq1lZWJb7oAlLwpi67emtgM7mJh7Wn­­kNWCWmsD0UmwwgrNgf7qv7ybndCSKjttXbIn4CNNAnu58xt3wIozXDn3SFS­5­VR80Z077IfHB4taY6825eTrXCECXUADB4L5Ayar1fWgoeykl3tdT01Cb4K­Pe­KQazx73Xs8Smd4IOffUpO3Av4kfZSb60BCiezJnVuRrzso83UjzwrPE2R­vzj­RYk')));
	print_r(json_encode('caluvRfeJ1q3XbA3awlk06rCP3cugYQq1lZWJb7oAlLwpi67emtgM7mJh7WnkNWCWmsD0UmwwgrNgf7qv7ybndCSKjttXbIn4CNNAnu58xt3wIozXDn3SFS5VR80Z077IfHB4taY6825eTrXCECXUADB4L5Ayar1fWgoeykl3tdT01Cb4KPeKQazx73Xs8Smd4IOffUpO3Av4kfZSb60BCiezJnVuRrzso83UjzwrPE2RvzjRYk'));
	// print_r("----------- ЗАПРОС СО СТОРОНЫ РЕКЛАМОДАТЕЛЯ ------------\n");
	// echo "\n";
	// print_r(json_encode($header,true));
	// echo "\n";
	// print_r("grant_type=client_credentials&client_id=$client_id&client_secret=$client_secret");
	// print_r("\n----------- ЗАПРОС СО СТОРОНЫ РЕКЛАМОДАТЕЛЯ ------------");

	
}


//Метод отправки запросов
function curlRecuest($url, $headers, $postField){
	$curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_HEADER, 1);
    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl_handle, CURLOPT_POST, TRUE);
    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $postField);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($curl_handle);
    $header_size = curl_getinfo($curl_handle, CURLINFO_HEADER_SIZE);
	$header = substr($response, 0, $header_size);
    $response = json_decode($response,true);
    curl_close($curl_handle);
    return $response;
}


?>