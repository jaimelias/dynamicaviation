<?php

$today = date_i18n(get_option('date_format'), strtotime(null));
$label_doc = __('Estimate', 'dynamicpackages');
$greeting = sprintf(__('Hello %s,', 'dynamicpackages'), sanitize_text_field($_POST['first_name']));
$passengers = sanitize_text_field($_POST['aircraft_pax']);
$currency_symbol = '$';
$company_name = get_bloginfo('name');
$company_phone = get_option('dy_phone');
$company_email = sanitize_email(get_option('dy_email'));
$company_contact = ($company_phone) ?  $company_phone . ' / ' . $company_email : $company_email;
$company_address = get_option('dy_address');
$company_tax_id = get_option('dy_tax_id');
$label_client = __('Client', 'dynamicpackages');
$client_name = sanitize_text_field($_POST['first_name']) . ' ' . sanitize_text_field($_POST['lastname']);
$client_email = sanitize_email($_POST['email']);
$client_phone = sanitize_text_field($_POST['phone']);
$label_item = __('Service', 'dynamicpackages');
$label_total = __('Total', 'dynamicpackages');
$label_subtotal = __('Subtotal', 'dynamicpackages');
$departure_itinerary = sanitize_text_field($_POST['departure_itinerary']);
$return_itinerary = sanitize_text_field($_POST['return_itinerary']);


$departure_itinerary = ($departure_itinerary) ? __('Departure', 'dynamicaviation') . ': ' . $departure_itinerary : '';
$return_itinerary = ($return_itinerary) ? __('Return', 'dynamicaviation') . ': ' . $return_itinerary : '';
$itinerary_html = ($return_itinerary) ? $departure_itinerary.'<br/><br/>'.$return_itinerary : $departure_itinerary;
$itinerary_html .= '<br/><br/>' . __('Passengers', 'dynamicaviation') . ': ' . $passengers;
$itinerary_text = ($return_itinerary) ? $departure_itinerary . ' || ' . $return_itinerary : $departure_itinerary;
$itinerary_text .= ' || ' . __('Passengers', 'dynamicaviation') . ': ' . $passengers;


$label_notes = __('Notes', 'dynamicpackages');
$notes = nl2br(apply_filters('dy_aviation_estimate_notes', ''));
$footer = $company_address;
$whatsapp_url = esc_url('https://wa.me/' . get_option('dy_whatsapp') . '?text=' . urlencode($itinerary_text));
$whatsapp = (get_option('dy_whatsapp')) ? '<a style="border: 16px solid #25d366; text-align: center; background-color: #25d366; color: #fff; font-size: 18px; line-height: 18px; display: block; width: 100%; box-sizing: border-box; text-decoration: none; font-weight: 900;" href="'.esc_url($whatsapp_url).'">'.__('Whatsapp Advisory', 'dynamicpackages').'</a>' : null;

$email_template = <<<EOT
<!DOCTYPE html>
<html>
	<head>
		<title>${company_name}</title>
		<meta http-equiv="x-ua-compatible" content="ie=edge">
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<style type="text/css">
			@media (max-width: 600px) {
				.sm-hide
				{
					display: none;
				}
				.doc_box {
					font-size: 14px;
				}
				.doc_box table tr.top table td {
					width: 100%;
					display: block;
					text-align: center;
				}
				.doc_box table tr.information table td {
					width: 100%;
					display: block;
					text-align: center;
				}
			}
			body, table, td, a
			{
				-ms-text-size-adjust: 100%;
				-webkit-text-size-adjust: 100%;
			}
			table, td 
			{
				mso-table-rspace: 0pt;
				mso-table-lspace: 0pt;
			}
			img {
				-ms-interpolation-mode: bicubic;
			}
			a[x-apple-data-detectors] 
			{
				font-family: inherit !important;
				font-size: inherit !important;
				font-weight: inherit !important;
				line-height: inherit !important;
				color: inherit !important;
				text-decoration: none !important;
			}
			body 
			{
				width: 100% !important;
				height: 100% !important;
				padding: 0 !important;
				margin: 0 !important;
			}
			table {
				border-collapse: collapse !important;
			}
			img {
				height: auto;
				line-height: 100%;
				text-decoration: none;
				border: 0;
				outline: none;
			}			
		</style>
	</head>
	<body style="font-family: Arial, sans-serif; line-height: 1.5; font-size: 14px;">
		<div style="max-width: 800px; width: 100%; margin: 0 auto 0 auto;">
			<div class="preheader" style="display: none; max-width: 0; max-height: 0; overflow: hidden; font-size: 1px; line-height: 1px; color: #fff; opacity: 0;">${itinerary_html}</div>
		
			<div style="margin: 20px 0 40px 0; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
				<p>${greeting}</p>
				<p>${subject}</p>
			</div>
		
			<div class="doc_box" style="margin-bottom: 40px; padding: 20px; border: 1px solid #eee; box-sizing: border-box">
                <p>${itinerary_html}</p>
			</div>
			
			${whatsapp}
		</div>		
	</body>
</html>
EOT;

?>