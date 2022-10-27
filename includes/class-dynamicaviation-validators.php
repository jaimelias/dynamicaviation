<?php 

class Dynamic_Aviation_Validators{
	
	public static function validate_recaptcha()
	{
		$output = false;
		$which_var = 'aviation_validate_recaptcha';
		global $$which_var;

		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			if((isset($_POST['g-recaptcha-response'])) && get_option('captcha_secret_key'))
			{				
				$url = 'https://www.google.com/recaptcha/api/siteverify';			

				$params = array(
					'secret' => get_option('captcha_secret_key'),
					'remoteip' => $_SERVER['REMOTE_ADDR'],
					'response' => sanitize_text_field($_POST['g-recaptcha-response']),
				);
				
				$resp = wp_remote_post($url, array(
					'body' => $params
				));

				if($resp['response']['code'] === 200)
				{
					$output = true;
				}
				else
				{
					$output = false;
				}
			}
			else
			{
				$output = false;
			}

			$GLOBALS[$which_var] = $output;
		}
		
		return $output;
	}	

	public static function valid_aircraft_search()
	{
		$output = false;
		$which_var = 'aviation_valid_aircraft_search';
		global $$which_var;

		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			if(get_query_var('instant_quote') && isset($_GET['aircraft_origin']) && isset($_GET['aircraft_destination']) && isset($_GET['aircraft_pax']) && isset($_GET['aircraft_flight']) && isset($_GET['aircraft_departure_date']) && isset($_GET['aircraft_departure_hour']) && isset($_GET['aircraft_return_date']) && isset($_GET['aircraft_return_hour']) && isset($_GET['aircraft_origin_l']) && isset($_GET['aircraft_destination_l']))
			{
				$output = true;
			}
			else
			{
				$output = false;
			}

			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}

	public static function validate_hash()
	{
		$output = false;
		$which_var = 'aviation_validate_hash';
		global $$which_var;

		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			$hash = hash('sha512', $_GET['aircraft_pax'].$_GET['aircraft_departure_date']);

			if($hash == get_query_var('instant_quote'))
			{
				$output = true;
			}
			else
			{
				$output = false;
			}

			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}	
	
}

?>