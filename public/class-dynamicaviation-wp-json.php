<?php 


if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamic_Aviation_WP_JSON {


    public function __construct($plugin_name, $plugin_version, $utilities)
    {
		$this->plugin_name = $plugin_name;
        $this->utilities = $utilities;
        add_action( 'rest_api_init', array(&$this, 'core_args') );
        add_action('rest_api_init', [$this, 'register_transactions_route']);
    }

    public function register_transactions_route(): void
    {
        register_rest_route($this->plugin_name, '/transactions/(?P<dy_id>\d+)', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'transactions_endpoint'],
            'permission_callback' => '__return_true',
            'args' => [
                'dy_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'minimum' => 1,
                    'sanitize_callback' => 'absint',
                ],
                'email' => [
                    'required' => true,
                    'type' => 'string',
                    'validate_callback' => static fn(mixed $value): bool => is_string($value) && (bool) is_email($value),
                    'sanitize_callback' => 'sanitize_email',
                ],
                'dy_request' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['estimate_request'],
                ],
                'action' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['sign-transaction'],
                ],
                'cf-turnstile-response' => [
                    'required' => true,
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 2048,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    public function transactions_endpoint(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $dy_id = absint($request['dy_id']);
        $post = get_post($dy_id);

        // General quote requests use the page hosting the form until an aircraft is selected.
        if (
            !$post instanceof WP_Post
            || !in_array($post->post_type, ['aircrafts', 'page'], true)
            || !(is_post_publicly_viewable($post) || current_user_can('read_post', $dy_id))
        ) {
            return new WP_Error('invalid_post_id', __('Quote not found.', 'dynamicaviation'), ['status' => 404]);
        }

        if (!validate_turnstile($request['cf-turnstile-response'], 'sign-transaction')) {
            return new WP_Error('invalid_turnstile_token', __('Invalid verification.', 'dynamicaviation'), ['status' => 400]);
        }

        $unique_tx_id = wp_generate_uuid4();
        if (!dy_transactions::create($unique_tx_id, [
            'email' => sanitize_email($request['email']),
            'dy_request' => 'estimate_request',
            'dy_id' => $dy_id,
        ])) {
            return new WP_Error('transaction_not_created', __('Unable to start your request.', 'dynamicaviation'), ['status' => 503]);
        }

        $response = new WP_REST_Response(['unique_tx_id' => $unique_tx_id]);
        $response->set_headers([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);

        return $response;
    }

    public function core_args()
    {
        register_rest_route( $this->plugin_name, 'airports.json', array(
            'methods' => 'GET',
            'callback' => array(&$this, 'core_args_callback'),
            'permission_callback' => '__return_true'
        ));
    }

    public function core_args_callback($req)
    {
        return $this->arrayToGeoJSON($this->utilities->all_airports_data());

        
    }

    public function arrayToGeoJSON($inputArray) {
        $geoJSON = [
            'type' => 'FeatureCollection',
            'features' => [],
        ];
    
        foreach ($inputArray as $item) {
            $feature = [
                'type' => 'Feature',
                'properties' => $item,
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$item['_geoloc']['lng'], $item['_geoloc']['lat']],
                ],
            ];
    
            unset($feature['properties']['_geoloc']);
    
            $geoJSON['features'][] = $feature;
        }
    


        return $geoJSON;
    }

}

?>
