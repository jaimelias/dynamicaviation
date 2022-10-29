<?php 


class Dynamic_Aviation_Estimate_Confirmation
{
    public function __construct($plugin_name, $version, $utilities)
    {
		$this->plugin_name = $plugin_name;
        $this->utilities = $utilities;
        $this->plugin_dir_path = plugin_dir_path( dirname( __FILE__ ) );
		$this->pathname = 'request_submitted';

		//sets OOP vars
        add_action('init', array(&$this, 'init'), 1);
        
		//filters custom wordpress outputs
        add_filter( 'pre_get_document_title', array(&$this, 'modify_wp_title'), 100);
		add_filter('wp_title', array(&$this, 'modify_wp_title'), 100);
        add_filter('the_title', array(&$this, 'modify_title'), 100);
        add_filter('the_content', array(&$this, 'modify_content'), 100);

		//changes the template to page.php in the theme
        add_filter('template_include', array(&$this, 'locate_template'), 100 );

		//sets custom params to the post before wp_query
        add_action('pre_get_posts', array(&$this, 'main_wp_query'), 100);

		//adds the query var
		add_filter('query_vars', array(&$this, 'registering_custom_query_var'));
		add_action('init', array(&$this, 'add_rewrite_rule'), 100);
		add_action('init', array(&$this, 'add_rewrite_tag'), 100);

		//process the submit of the quote form
		add_action( 'parse_query', array( &$this, 'form_submit' ), 100);
    }

    public function init()
    {
		$this->get_languages = get_languages();
		$this->site_name = get_bloginfo('name');
		$this->current_language = current_language();
		$this->valid_recaptcha = $this->validate_recaptcha();
    }	

	public function add_rewrite_rule()
	{
		add_rewrite_rule('^'.$this->pathname.'/([^/]*)/?', 'index.php?'.$this->pathname.'=$matches[1]','top');
		$languages = $this->get_languages;
		$arr = array();

		for($x = 0; $x < count($languages); $x++)
		{
			if($languages[$x] != pll_default_language())
			{
				$arr[] = $languages[$x];
			}
		}

		if(count($arr) > 0)
		{
			$arr = implode('|', $arr);
			add_rewrite_rule('('.$arr.')/'.$this->pathname.'/([^/]*)/?', 'index.php?'.$this->pathname.'=$matches[2]','top');
		}		
	}

	public function add_rewrite_tag()
	{
		add_rewrite_tag('%'.$this->pathname.'%', '([^&]+)');
	}

	public function registering_custom_query_var($query_vars)
	{
		$query_vars[] = $this->pathname;
		return $query_vars;
	}

    public function main_wp_query($query)
    {
        if($query->is_main_query() && isset($query->query_vars[$this->pathname]))
        {
            $query->set('post_type', 'page');
            $query->set( 'posts_per_page', 1 );            
        }
    }

    public function locate_template($template)
    {
		if(get_query_var($this->pathname))
		{
			$template = locate_template( array( 'page.php' ) );	
		}
        
        return $template;
    }

    public function modify_content($content)
    {
		if($this->validate_form_submit())
		{
			
			if($this->valid_recaptcha)
			{				
				$content = '<p class="minimal_success">'.esc_html(__('Request received. Our sales team will be in touch with you soon.', 'dynamicaviation')).'</p>';
			}
			else
			{
				$content = '<p class="minimal_alert">'.esc_html(__('Invalid Recaptcha', 'dynamicaviation')).'</p>';
			}
		}

        return $content;
    }

    public function modify_title($title)
    {
		if(in_the_loop() && $this->validate_form_submit())
		{
			$title = esc_html(__('Request Submitted', "dynamicaviation"));
		}
        
        return $title;
    }

    public function modify_wp_title($title)
    {
		if($this->validate_form_submit())
		{
			$title =  __('Request Submitted', 'dynamicaviation').' | '.$this->site_name;
		}
        
        return $title;
    }

	public function form_submit()
	{
		$which_var = $this->plugin_name.'_'.$this->pathname . '_form_submit';
		global $$which_var;

		if(!isset($$which_var))
		{
			if($this->valid_recaptcha)
			{
				if($this->validate_form_submit())
				{
					$data = $_POST;
					$data['lang'] = $this->current_language;
					
					if(!isset($_POST['aircraft_id']))
					{
						$subject = sprintf(__('%s, Your request has been sent to our specialists at %s!', 'dynamicaviation'), sanitize_text_field($data['first_name']), get_bloginfo('name'));
						require_once( $this->plugin_dir_path . 'public/general_email_template.php');
					}
					else
					{
						$this_id = sanitize_text_field($_POST['aircraft_id']);
						$subject = sprintf(__('%s, %s has sent you an estimate for $%s', 'dynamicaviation'), sanitize_text_field($data['first_name']), get_bloginfo('name'), sanitize_text_field($data['aircraft_price']));
						$is_commercial = (aviation_field('aircraft_commercial', $this_id) == 1) ? true : false;
						$transport_title = $this->utilities->transport_title_singular($this_id);
						require_once($this->plugin_dir_path . 'public/quote_email_template.php');
					}
					
					
					$args = array(
						'subject' => $subject,
						'to' => sanitize_email($_POST['email']),
						'message' => $email_template
					);


					sg_mail($args);

					$GLOBALS[$which_var] = true;
					//self::webhook(json_encode($data));
				}			
			}
		}
	}


	public function validate_form_submit()
	{
		$output = false;
		$which_var = $this->plugin_name . '_' . $this->pathname . 'validate_form_submit';
		global $$which_var;
		
		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			if(get_query_var($this->pathname) && isset($_POST['aircraft_origin_l']) && isset($_POST['aircraft_destination_l']) && isset($_POST['first_name']) && isset($_POST['lastname']) && isset($_POST['email']) && isset($_POST['phone']) && isset($_POST['country']) && isset($_POST['g-recaptcha-response']) && isset($_POST['aircraft_origin'])  && isset($_POST['aircraft_destination'])  && isset($_POST['aircraft_departure_date'])  && isset($_POST['aircraft_departure_hour']) && isset($_POST['departure_itinerary']) && isset($_POST['aircraft_return_date']) && isset($_POST['aircraft_return_hour']) && isset($_POST['return_itinerary']))
			{
				$output = true;
				$GLOBALS[$which_var] = $output;
			}	
		}

		return $output;
	}

	public function validate_recaptcha()
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
			if((isset($_POST['g-recaptcha-response'])))
			{
				$secret_key = get_option('captcha_secret_key');

				if($secret_key)
				{
					$url = 'https://www.google.com/recaptcha/api/siteverify';			

					$ip = (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) 
						? $_SERVER['HTTP_CF_CONNECTING_IP'] : 
						$_SERVER['REMOTE_ADDR'];

					$params = array(
						'secret' => $secret_key,
						'remoteip' => $ip,
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