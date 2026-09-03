<?php 

#[AllowDynamicProperties]
class Dynamic_Aviation_Estimate_Page
{

	static $cache = [];

	public function __construct($plugin_name, $version, $utilities)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->utilities = $utilities;

		$this->plugin_dir_path = plugin_dir_path(dirname(__FILE__));
		$this->plugin_dir_url = plugin_dir_url(__DIR__);

		$this->pathname = 'instant_quote';
		$this->get_languages = get_languages();
		$this->default_language = default_language();
		$this->site_name = get_bloginfo('name');

		// Filters custom WordPress outputs.
		add_filter('pre_get_document_title', [$this, 'modify_wp_title'], 100);
		add_filter('wp_title', [$this, 'modify_wp_title'], 100);
		add_filter('the_title', [$this, 'modify_title'], 100);
		add_filter('the_content', [$this, 'modify_content'], 100);

		// Changes the template to page.php in the theme.
		add_filter('template_include', [$this, 'locate_template'], 100);

		// Sets custom params to the post before WP_Query.
		add_action('pre_get_posts', [$this, 'main_wp_query'], 100);

		// Adds the query var and rewrite rules.
		add_filter('query_vars', [$this, 'registering_custom_query_var']);
		add_action('init', [$this, 'add_rewrite_rule'], 100);
		add_action('init', [$this, 'add_rewrite_tag'], 100);

		// Enqueue scripts.
		add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
		add_action('parse_query', [$this, 'load_recaptcha_scripts']);
	}

	public function add_rewrite_rule()
	{
		$pathname = preg_quote($this->pathname, '#');

		// /instant_quote/{value}
		add_rewrite_rule(
			'^' . $pathname . '/([^/]+)/?$',
			'index.php?' . $this->pathname . '=$matches[1]',
			'top'
		);

		$languages = array_values(
			array_unique(
				array_filter(
					$this->get_languages,
					fn($language) =>
						is_string($language)
						&& $language !== ''
						&& $language !== $this->default_language
				)
			)
		);

		if (!$languages) {
			return;
		}

		$languages = array_map(
			fn($language) => preg_quote($language, '#'),
			$languages
		);

		$language_pattern = implode('|', $languages);

		// /{language}/instant_quote/{value}
		add_rewrite_rule(
			'^(?:' . $language_pattern . ')/' . $pathname . '/([^/]+)/?$',
			'index.php?' . $this->pathname . '=$matches[1]',
			'top'
		);
	}

	public function add_rewrite_tag()
	{
		add_rewrite_tag(
			'%' . $this->pathname . '%',
			'([^&]+)'
		);
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
        return get_query_var($this->pathname) 
			? locate_template(['page.php']) 
			: $template;
    }

    public function modify_content($content)
    {
        return $this->validate_form_search() 
			? (string) apply_filters('dy_aviation_aircrafts_table', '') 
			: $content;
    }

    public function modify_title($title)
    {
        return in_the_loop() && $this->validate_form_search() 
			? esc_html(__('Find an Aircraft', 'dynamicaviation')) 
			: $title;
    }

    public function modify_wp_title($title)
    {        
        return $this->validate_form_search() 
			? sprintf( 
					__('Find an Aircraft %s - %s | %s', 'dynamicaviation'), 
					secure_get('aircraft_origin'),  
					secure_get('aircraft_destination'),
					$this->site_name
				) 
			: $title;
    }

	public function enqueue_scripts()
	{
		if($this->validate_form_search())
		{
			wp_enqueue_script($this->plugin_name.'_'.$this->pathname, $this->plugin_dir_url . 'public/js/estimate-page.js', ['jquery', 'turnstile-compat', 'dy-core-utilities'], $this->version, true );
		}
	}

	public function validate_form_search()
	{
		$cache_key = 'validate_form_search';

		if(array_key_exists($cache_key, self::$cache))
		{
			return self::$cache[$cache_key];
		}

        if ($_SERVER['REQUEST_METHOD'] !== 'GET')
        {
            return self::$cache[$cache_key] = false;
        }

		if(!get_query_var($this->pathname)) {
			return self::$cache[$cache_key] = false;
		}

		$output = true;
		$required_params = [
			'aircraft_origin' => function($name) { return !empty(secure_get($name)); },
			'aircraft_destination' => function($name) { 

				$aircraft_destination = secure_get('aircraft_destination');
				$aircraft_origin = secure_get('aircraft_origin');
				return !empty(secure_get('aircraft_destination')) && $aircraft_destination !== $aircraft_origin;
			 },
			'pax_num' => function($name) { 
				$pax_num = secure_get($name, 0, 'absint');
				return $pax_num >= 1 && $pax_num <= 20;
			 },
			'aircraft_flight' => function($name) { return in_array(secure_get($name, 0, 'absint'), [0, 1]);  },
			'start_date' => function($name) { return is_valid_date(secure_get($name)); },
			'start_time' => function($name) { return is_valid_time(secure_get($name)); },
			'end_date' => function($name) { return ((secure_get('aircraft_flight', 0, 'absint') === 0 && secure_get($name) === '') || (secure_get('aircraft_flight', 0, 'absint') === 1 && is_valid_date(secure_get($name)))); },
			'end_time' => function($name) { return ((secure_get('aircraft_flight', 0, 'absint') === 0 && secure_get($name) === '') || (secure_get('aircraft_flight', 0, 'absint') === 1 && is_valid_time(secure_get($name)))); }
		];

		$invalids = [];

		foreach($required_params as $param_name => $validation_callback)
		{
			if(!$validation_callback($param_name))
			{
				$invalids[] = sprintf(__('The required parameter "%s" is missing or invalid.', 'dynamicaviation'), $param_name);
			}
		}

		if(count($invalids) > 0)
		{
			$output = false;
			dy_errors::add($invalids, 400);
		}



		return self::$cache[$cache_key] = $output;
	}


	public function load_recaptcha_scripts($query)
	{
		global $dy_load_turnstile_scripts;

		if(!isset($dy_load_turnstile_scripts))
		{
			if(isset($query->query_vars[$this->pathname]))
			{
				if($query->query_vars[$this->pathname])
				{
					$GLOBALS['dy_load_turnstile_scripts'] = true;
					$GLOBALS['dy_load_request_form_utilities_scripts'] = true;
				}
			}
		}
	}
}