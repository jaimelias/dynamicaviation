<?php

#[AllowDynamicProperties]
class Dynamic_Aviation_Submit
{
    static $cache = [];

    public function __construct($id, $version, $utilities)
    {
        $this->id = $id;
        $this->utilities = $utilities;
        $this->plugin_dir_path = plugin_dir_path(dirname(__FILE__));
        $this->pathname = 'tx-submit';
        $this->default_language = default_language();
        $this->site_name = get_bloginfo('name');

        // Filters custom WordPress outputs
        add_filter('pre_get_document_title', [$this, 'modify_wp_title'], 100);
        add_filter('the_title', [$this, 'modify_title'], 100);
        add_filter('the_content', [$this, 'modify_content'], 100);

        // Changes the template to page.php in the theme
        add_filter('template_include', [$this, 'locate_template'], 100);

        // Sets custom params to the post before wp_query
        add_action('pre_get_posts', [$this, 'main_wp_query'], 100);

        // Adds the query var
        add_filter('query_vars', [$this, 'registering_custom_query_var']);
        add_action('init', [$this, 'add_rewrite_rule'], 100);
        add_action('init', [$this, 'add_rewrite_tag'], 100);

        // Process the submit of the quote form
        add_action('parse_query', [$this, 'form_submit'], 100);

        // Notes
        add_filter('dy_aviation_estimate_notes', [$this, 'estimate_notes']);
        add_filter('dy_aviation_estimate_subject', [$this, 'subject']);
    }

    public function add_rewrite_rule()
    {
        $pathname = preg_quote($this->pathname, '/');

        add_rewrite_rule(
            '^' . $pathname . '/([^/]+)/?$',
            'index.php?' . $this->pathname . '=$matches[1]',
            'top'
        );

        $languages = array_values(array_filter(
            get_languages(),
            fn($language) => $language !== $this->default_language
        ));


        if (!empty($languages))
        {
            $languages = array_map(
                fn($language) => preg_quote($language, '/'),
                $languages
            );

            $languages = implode('|', $languages);

            add_rewrite_rule(
                '^(?:' . $languages . ')/' . $pathname . '/([^/]+)/?$',
                'index.php?' . $this->pathname . '=$matches[1]',
                'top'
            );
        }
    }

    public function add_rewrite_tag()
    {
        add_rewrite_tag('%' . $this->pathname . '%', '([^&]+)');
    }

    public function registering_custom_query_var($query_vars)
    {
        $query_vars[] = $this->pathname;

        return $query_vars;
    }

    public function main_wp_query($query)
    {
        if ($query->is_main_query() && isset($query->query_vars[$this->pathname]))
        {
            $query->set('post_type', 'page');
            $query->set('posts_per_page', 1);
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
        return $this->validate_form_submit()
            ? '<p class="minimal_success">' . esc_html(__('Request received. Our sales team will be in touch with you soon.', 'dynamicaviation')) . '</p>'
            : $content;
    }

    public function modify_title($title)
    {
        return in_the_loop() && $this->validate_form_submit()
            ? esc_html(__('Request Submitted', 'dynamicaviation'))
            : $title;
    }

    public function modify_wp_title($title)
    {
        return $this->validate_form_submit()
            ? __('Request Submitted', 'dynamicaviation') . ' | ' . $this->site_name
            : $title;
    }

    public function subject($output)
    {
        $price = secure_post('charter_price', 0, 'floatval');

        if (post_has('aircraft_id') && $price > 0)
        {
            $output = sprintf(
                __('%s, %s has sent you an estimate for $%s', 'dynamicaviation'),
                secure_post('first_name'),
                $this->site_name,
                money($price)
            );
        }
        else
        {
            $output = sprintf(
                __('%s, Your request has been sent to our specialists at %s!', 'dynamicaviation'),
                secure_post('first_name'),
                $this->site_name
            );
        }

        return $output;
    }

    public function form_submit($query)
    {
        $cache_key = 'form_submit';

        if (array_key_exists($cache_key, self::$cache))
        {
            return self::$cache[$cache_key];
        }

        if (!isset($query->query_vars[$this->pathname]))
        {
            return self::$cache[$cache_key] = false;
        }

        if (!$this->validate_form_submit())
        {
            return self::$cache[$cache_key] = false;
        }

        $price = secure_post('charter_price', 0, 'floatval');
        $email_template = '';

        if (post_has('aircraft_id') && $price > 0)
        {
            require($this->plugin_dir_path . 'public/email_templates/quote.php');
        }
        else
        {
            require($this->plugin_dir_path . 'public/email_templates/general.php');
        }

        $email = secure_post('email', '', 'sanitize_email');

        $headers = [
            'Content-Type: text/html; charset=UTF-8'
        ];

        $subject = (string) apply_filters('dy_aviation_estimate_subject', '');

        $sent = wp_mail(
            $email,
            $subject,
            $email_template,
            $headers
        );

        if (!$sent) {
            dy_errors::add(__('Unable to send your request. Please try again.', 'dynamicaviation'), 502);
            self::$cache['validate_form_submit'] = false;
            return self::$cache[$cache_key] = false;
        }

        dy_tx::update(secure_post('unique_tx_id'), 'success');

        return self::$cache[$cache_key] = true;
    }

    public function validate_form_submit()
    {
        $cache_key = 'validate_form_submit';

        if (array_key_exists($cache_key, self::$cache))
        {
            return self::$cache[$cache_key];
        }

        if (secure_server('REQUEST_METHOD') !== 'POST')
        {
            return self::$cache[$cache_key] = false;
        }

        if (!get_query_var($this->pathname))
        {
            return self::$cache[$cache_key] = false;
        }

        if (!wp_verify_nonce(get_query_var($this->pathname), 'dy_nonce')) {
            dy_errors::add(__('Invalid request. Please reload the quote and try again.', 'dynamicaviation'), 400);
            return self::$cache[$cache_key] = false;
        }

        if (!$this->validate_unique_tx_id()) {
            dy_errors::add(__('Invalid or expired transaction. Please submit a new request.', 'dynamicaviation'), 400);
            return self::$cache[$cache_key] = false;
        }

        $output = true;

        $required_params = [
			'aircraft_origin' => function($name) { return !empty(secure_post($name)); },
			'aircraft_destination' => function($name) { return !empty(secure_post('aircraft_destination')); },
			'pax_num' => function($name) { return secure_post($name, 0, 'absint') > 0; },
			'aircraft_flight' => function($name) { return in_array(secure_post($name, 0, 'absint'), [0, 1]);  },
			'start_date' => function($name) { return is_valid_date(secure_post($name)); },
			'start_time' => function($name) { return is_valid_time(secure_post($name)); },
			'end_date' => function($name) { return ((secure_post('aircraft_flight', 0, 'absint') === 0 && secure_post($name) === '') || (secure_post('aircraft_flight', 0, 'absint') === 1 && is_valid_date(secure_post($name)))); },
			'end_time' => function($name) { return ((secure_post('aircraft_flight', 0, 'absint') === 0 && secure_post($name) === '') || (secure_post('aircraft_flight', 0, 'absint') === 1 && is_valid_time(secure_post($name)))); },
            'first_name' => function($name) { return !empty(secure_post($name)); }, 
            'lastname' => function($name) { return !empty(secure_post($name)); },
            'email' => function($name) { return is_email(secure_post($name)); },
            'repeat_email' => function($name) { return is_email(secure_post($name)) && secure_post($name) === secure_post('email'); },
            'phone' => function($name) { return secure_post($name, 0, 'absint') > 0; },
            'country_calling_code' => function($name) { return secure_post($name, 0, 'absint') > 0; },
            'aircraft_id' => function($name) { 
                // The general request form has no selected aircraft.
                if (!post_has($name)) {
                    return true;
                }

                $aircraft_id = secure_post($name, 0, 'absint');

                if($aircraft_id === 0)
                {
                    return false;
                }

                $post = get_post($aircraft_id);
                return $post instanceof WP_Post && $post->post_type === 'aircrafts';
            },
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

		if ($output && !validate_turnstile(secure_post('cf-turnstile-response'), 'submit-transaction')) {
			$output = false;
		}

		return self::$cache[$cache_key] = $output;
    }

    public function validate_unique_tx_id(): bool
    {
        $unique_tx_id = secure_post('unique_tx_id');
        $email = secure_post('email', '', 'sanitize_email');
        $dy_request = secure_post('dy_request', '', 'sanitize_key');
        $dy_id = secure_post('dy_id', 0, 'absint');

        if (!is_string($unique_tx_id) || $unique_tx_id === '' || !is_email($email) || $dy_id <= 0 || $dy_request !== 'estimate_request') {
            return false;
        }

        $post = get_post($dy_id);
        $has_aircraft = post_has('aircraft_id');
        $post_type = $has_aircraft ? 'aircrafts' : 'page';

        if (
            !$post instanceof WP_Post
            || $post->post_type !== $post_type
            || !(is_post_publicly_viewable($post) || current_user_can('read_post', $dy_id))
            || ($has_aircraft && secure_post('aircraft_id', 0, 'absint') !== $dy_id)
        ) {
            return false;
        }

        if (!dy_tx::validate($unique_tx_id, [$unique_tx_id, $email, $dy_request, $dy_id])) {
            return false;
        }

        return dy_tx::get($unique_tx_id)?->status === 'started';
    }

    public function estimate_notes()
    {
        return get_option(
            'dy_aviation_estimate_note_' . current_language()
        );
    }
}
