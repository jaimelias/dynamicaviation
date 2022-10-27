<?php 


class Dynamic_Aviation_Estimate_Confirmation
{
    public function __construct($plugin_name, $version, $utilities)
    {
        $this->utilities = $utilities;
        $this->plugin_dir_path = plugin_dir_path( dirname( __FILE__ ) );
        add_action('init', array(&$this, 'init'));
        add_action( 'parse_query', array( &$this, 'submit' ), 1);
        add_filter( 'pre_get_document_title', array(&$this, 'modify_wp_title'), 100);
		add_filter('wp_title', array(&$this, 'modify_wp_title'), 100);
        add_filter('the_title', array(&$this, 'modify_title'), 100);
        add_filter('the_content', array(&$this, 'modify_content'), 100);
        add_filter('template_include', array(&$this, 'locate_template'), 100 );
        add_action('pre_get_posts', array(&$this, 'main_wp_query'), 100);	
    }

    public function init()
    {
		$this->site_name = get_bloginfo('name');
		$this->current_language = current_language();
    }

    public function main_wp_query($query)
    {
        if($query->is_main_query() && isset($query->query_vars['request_submitted']))
        {
            $query->set('post_type', 'page');
            $query->set( 'posts_per_page', 1 );            
        }
    }

    public function locate_template($template)
    {
		if(get_query_var('request_submitted'))
		{
			$template = locate_template( array( 'page.php' ) );	
		}
        
        return $template;
    }

    public function modify_content($content)
    {
		if(Dynamic_Aviation_Validators::valid_aircraft_quote())
		{
			global $VALID_JET_RECAPTCHA;
			
			if(isset($VALID_JET_RECAPTCHA))
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
		if(Dynamic_Aviation_Validators::valid_aircraft_quote())
		{
			$title = esc_html(__('Request Submitted', "dynamicaviation"));
		}
        
        return $title;
    }

    public function modify_wp_title($title)
    {
		if(Dynamic_Aviation_Validators::valid_aircraft_quote())
		{
			$title =  __('Request Submitted', 'dynamicaviation').' | '.$this->site_name;
		}
        
        return $title;
    }

	public function submit()
	{
		global $VALID_JET_RECAPTCHA;
		
		if(!isset($VALID_JET_RECAPTCHA))
		{
			if(Dynamic_Aviation_Validators::valid_aircraft_quote())
			{
				if(Dynamic_Aviation_Validators::validate_recaptcha())
				{
					$data = $_POST;
					$data['lang'] = $this->current_language;
					
					if(!isset($_POST['aircraft_id']))
					{
						$subject = sprintf(__('%s, Your request has been sent to our specialists at %s!', 'dynamicaviation'), sanitize_text_field($data['first_name']), get_bloginfo('name'));
						require_once( $this->plugin_dir_path . 'public/general_email_template.php');
					}
					else{
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

					//self::webhook(json_encode($data));
					$GLOBALS['VALID_JET_RECAPTCHA'] = true;
				}
			}			
		}
	}    

}