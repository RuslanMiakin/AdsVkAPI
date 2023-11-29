<?php 

/*
	github разработчика https://github.com/RuslanMiakin
*/


/* В массивах $arrayInfo/$field могут быть другие ключи отвечающие за наполнение контента, 
в зависимости от типа объявления. 
Подробнее в ads.vk.com/doc/api/
В данном примере настроено объявление с целевым действием "САЙТ". */


$url = 'https://srv0.ru/vkapi/form.php'; // Адресс скрипта

$arrayInfo = array(
		array(
		'title_40_vkads' => 'Заголовок2',
		'text_90' => 'Краткое описание2',
		'primary' => 'http://dauken.ru/' //ссылка на сайт (должна пройти модерацию)
));
   
$fields = array(
	//source не трогаем
	     'source' => 'site',
       'client_id' => '7z4u0wkZJOzXGcqQ',
       'client_secret' => 'wTLhCjFQQHRGgg4mA5tiASKKOOsgzpXMDRCL0s2PDXEBwxPe2FEWA8fDpjdm1XFqVeDxjCZbp57dXWNLTerjHG7LPSRyGSY26CsktlOu9zc62f5g2HhseTG0jmZT9E4IS3Q4L1Q7cGZeIFyQNAJjnC39kwZeQ6lL8c3hWrU5H8zta9lduwajWYUJULngJSz7GlCqZLvPxeAZWpHwbuaehxOuyR', 
       'group_id' => '88019190',
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