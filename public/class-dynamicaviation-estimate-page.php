<?php 

#[AllowDynamicProperties]
class Dynamic_Aviation_Estimate_Page
{
    public function __construct($plugin_name, $version, $utilities)
    {
		$this->plugin_name = $plugin_name;
		$this->version =  $version;
        $this->utilities = $utilities;
        $this->plugin_dir_path = plugin_dir_path( dirname( __FILE__ ) );
        $this->plugin_dir_url = plugin_dir_url( __DIR__ );
		$this->pathname = 'instant_quote';

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

		//enqueue scripts
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
		add_action( 'parse_query', array( &$this, 'load_recaptcha_scripts' ));
    }

    public function init()
    {
		$this->get_languages = get_languages();
		$this->site_name = get_bloginfo('name');
		$this->current_language = current_language();
    }	

	public function add_rewrite_rule()
	{
		add_rewrite_rule('^'.$this->pathname.'/([^/]*)/?', 'index.php?'.$this->pathname.'=$matches[1]','top');
		$languages = $this->get_languages;
		$arr = array();

		for($x = 0; $x < count($languages); $x++)
		{
			if($languages[$x] != default_language())
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
		if($this->validate_form_search())
		{
			return apply_filters('dy_aviation_aircrafts_table', '');	
		}

        return $content;
    }

    public function modify_title($title)
    {
		if(in_the_loop() && $this->validate_form_search())
		{
			$title = esc_html(__('Find an Aircraft', 'dynamicaviation'));
		}
        
        return $title;
    }

    public function modify_wp_title($title)
    {
		if($this->validate_form_search())
		{
			$s1 = sanitize_text_field($_POST['aircraft_origin']);
			$s2 = sanitize_text_field($_POST['aircraft_destination']);
			$title = sprintf( __('Find an Aircraft %s - %s', 'dynamicaviation'), $s1,  $s2) .' | '.$this->site_name;
		}
        
        return $title;
    }

	public function enqueue_scripts()
	{
		if($this->validate_form_search())
		{
			wp_enqueue_script($this->plugin_name.'_'.$this->pathname, $this->plugin_dir_url . 'public/js/estimate-page.js', array('jquery', 'recaptcha-v3', 'dy-core-utilities'), time(), true );
		}
	}

	public function validate_form_search()
	{
		$output = false;
		$which_var = 'aviation_validate_form_search';
		global $$which_var;

		if(isset($$which_var))
		{
			$output = $$which_var;
		}
		else
		{
			if(get_query_var($this->pathname))
			{
				$param_names = $this->utilities->search_form_hash_param_names();

				if($this->utilities->validate_params($param_names))
				{
					$output = true;
				}
			}

			$GLOBALS[$which_var] = $output;
		}

		return $output;
	}


	public function load_recaptcha_scripts($query)
	{
		global $dy_load_recaptcha_scripts;

		if(!isset($dy_load_recaptcha_scripts))
		{
			if(isset($query->query_vars[$this->pathname]))
			{
				if($query->query_vars[$this->pathname])
				{
					$GLOBALS['dy_load_recaptcha_scripts'] = true;
					$GLOBALS['dy_load_request_form_utilities_scripts'] = true;
				}
			}
		}
	}
}