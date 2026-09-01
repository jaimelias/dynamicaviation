<?php

#[AllowDynamicProperties]
class Dynamic_Aviation_Estimate_Confirmation
{
    static $cache = [];

    public function __construct($plugin_name, $version, $utilities)
    {
        $this->plugin_name = $plugin_name;
        $this->utilities = $utilities;
        $this->plugin_dir_path = plugin_dir_path(dirname(__FILE__));
        $this->pathname = 'request_submitted';
        $this->default_language = default_language();
        $this->site_name = get_bloginfo('name');

        // Filters custom WordPress outputs
        add_filter('pre_get_document_title', [$this, 'modify_wp_title'], 100);
        add_filter('wp_title', [$this, 'modify_wp_title'], 100);
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

        if (post_has('aircraft_id') && $price > 0)
        {
            require_once($this->plugin_dir_path . 'public/email_templates/quote.php');
        }
        else
        {
            require_once($this->plugin_dir_path . 'public/email_templates/general.php');
        }

        $email = secure_post('email', '', 'sanitize_email');

        $headers = [
            'Content-Type: text/html; charset=UTF-8'
        ];

        $subject = (string) apply_filters('dy_aviation_estimate_subject', '');

        wp_mail(
            $email,
            $subject,
            $email_template,
            $headers
        );

        return self::$cache[$cache_key] = true;
    }

    public function validate_form_submit()
    {
        $cache_key = 'validate_form_submit';

        if (array_key_exists($cache_key, self::$cache))
        {
            return self::$cache[$cache_key];
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            return self::$cache[$cache_key] = false;
        }

        if (!get_query_var($this->pathname))
        {
            return self::$cache[$cache_key] = false;
        }

        $param_names = $this->utilities->request_form_hash_param_names();

        return self::$cache[$cache_key] = (
            $this->utilities->validate_params($param_names)
            && validate_turnstile()
        );
    }

    public function estimate_notes()
    {
        return get_option(
            'dy_aviation_estimate_note_' . current_language()
        );
    }
}