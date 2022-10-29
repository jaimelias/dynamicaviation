<?php 

class Dynamic_Aviation_Validators{

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