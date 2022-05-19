<?php

global $airport_array;
$json = $airport_array;
$airport = $json['airport'];
$iata  = $json['iata'];
$icao = $json['icao'];
$codes = '('.$iata.')';
$city = $json['city'];
$country_name = $json['country_names'];
$lang = substr(get_locale(), 0, -3);

if($lang)
{
	if(array_key_exists($lang, $country_name))
	{
		$country_lang = $country_name[$lang];
	}
	else
	{
		$country_lang = $country_name['en'];
	}
}

$addressArray = array(($airport.' '.$codes), $city, $country_lang);
$address = implode(', ', $addressArray);

$translations = pll_the_languages(array('raw'=>1));

foreach ($translations as $k => $v)
{
	if($v['slug'] == pll_default_language())
	{
		$hreflang = $v['slug'].'" href="'.$v['url'].'fly/'.Dynamic_Aviation_Public::cleanURL($airport);
		echo '<link rel="alternate" hreflang="'.($hreflang).'/" />';		
	}
	else
	{
		$hreflang = $v['slug'].'" href="'.home_url('/').$v['slug'].'/fly/'.Dynamic_Aviation_Public::cleanURL($airport);
		echo '<link rel="alternate" hreflang="'.($hreflang).'/" />';				
	}
}


?>

<meta name="description" content="<?php echo esc_html(__('Private Charter Flight', 'dynamicaviation')); ?> <?php echo esc_html($address); ?>. <?php echo esc_html(__('Airplanes and helicopter rides in', 'dynamicaviation')); ?> <?php echo esc_html($airport.', '.$city); ?>." />

<link rel="canonical" href="<?php echo esc_url(home_lang()); ?>fly/<?php echo esc_html(Dynamic_Aviation_Public::cleanURL($airport)); ?>/" />
