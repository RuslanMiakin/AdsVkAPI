<?php 

/*
	github разработчика https://github.com/RuslanMiakin
*/


/* В массивах $arrayInfo/$field могут быть другие ключи отвечающие за наполнение контента, 
в зависимости от типа объявления. 
Подробнее в ads.vk.com/doc/api/
В данном примере настроено объявление с целевым действием "САЙТ". */


$url = ''; // Адресс скрипта

$arrayInfo = array(
		array(
		'title_40_vkads' => '',
		'text_90' => '',
		'primary' => '' //ссылка на сайт (должна пройти модерацию)
));
   
$fields = array(
	//source не трогаем
	   'source' => 'site',
       'client_id' => '',
       'client_secret' => '', 
       'group_id' => '',
       'info' => json_encode($arrayInfo),
    //Ключи не трогаем, меняем данные в curl_file_create (путь до изображения, тип, название)
    //Обязательные размеры (width и heigth должны строго соответствовать значениям)
       'icon_256x256' => curl_file_create('1.jpg','image/jpeg','icon_256x256.jpg'), 
       'image_1080x607' => curl_file_create('2.jpg','image/jpeg','image_1080x607.jpg'),
    //Дополнительные размеры (width и heigth должны строго соответствовать значениям)
       'image_600x600' => curl_file_create('3.jpg','image/jpeg','image_1080x607.jpg'),
       'image_1080x1350' => curl_file_create('4.jpg','image/jpeg','image_1080x607.jpg')
    );

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
$rez = curl_exec($ch);
curl_close($ch);
print_r($rez);

?>