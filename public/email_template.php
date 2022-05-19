<?php 

$first_name = sanitize_text_field($_POST['first_name']);
$aircraft_pax = sanitize_text_field($_POST['aircraft_pax']);
$hello = sprintf(__('Hello %s,', 'dynamicaviation'), $first_name);
$passengers = sprintf(__('Passengers: %s', 'dynamicaviation'), $aircraft_pax);
$greeting = sprintf(__('Thank you for contacting %s!', 'dynamicaviation'), get_bloginfo('name'));
$message = __('There are some questions we will have in order to better focus our attention on the right aircraft for the mission. Please expect a call and/or email soon to discuss your preferences. Feel free to ask any question.', 'dynamicaviation');
$aircraft = (isset($_POST['aircraft_name'])) ? sprintf(__('Aircraft: %s', 'dynamicaviation'), sanitize_text_field($_POST['aircraft_name'])) : null;
$itinerary = (isset($_POST['departure_itinerary'])) ? sprintf(__('Departure: %s', 'dynamicaviation'), sanitize_text_field($_POST['departure_itinerary'])) : null;
$itinerary = (isset($_POST['return_itinerary'])) ? $itinerary . '<br/>' . sprintf(__('Return: %s', 'dynamicaviation'), sanitize_text_field($_POST['return_itinerary'])) : $itinerary;
$estimate = (isset($_POST['aircraft_price'])) ? sprintf(__('Estimate: %s%s', 'dynamicaviation'), '$', sanitize_text_field($_POST['aircraft_price'])) : null;
$contact_whatsapp = __('Feel free to contact us using Whatsapp', 'dynamicaviation');
$whatsapp = get_theme_mod('whatsapp');
$contact_tel = __('To speak immediately to a Charter Specialist standing by, please call', 'dynamicaviation');
$tel = get_theme_mod('min_tel');

$email_template = <<<EOD
<div style="line-height: 1.5; max-width: 100%; width: 600px; margin: 0 auto; color: #000000;">
<div style="padding: 20px; background-color: #ffffff; border: solid 1px #ddd;">
<p>$hello</p>
<h2>$greeting</h2>
<p>$message</p>
<p>$aircraft</p>
<p>$passengers</p>
<p>$itinerary</p>
<p>$estimate</p>
<p>$contact_whatsapp:</p>
<p style="font-size: 20px; text-align: center; font-weight: 900;"><a style="display: block; text-decoration: none; padding: 10px 15px; color: #ffffff; background-color: #25d366;" href="https://wa.me/$whatsapp">&iexcl;Whatsapp!</a></p>
<p>$contact_tel:</p>
 <p style="font-size: 20px; text-align: center; font-weight: 900;"><span style="display: block; text-decoration: none; padding: 10px 15px; color: #000000; background-color: #f7f7f7;" >$tel</span></p>
</div>
</div>
EOD;

?>