<?php 


if ( !defined( 'WPINC' ) ) exit;

#[AllowDynamicProperties]
class Dynamic_Aviation_WP_JSON {


    public function __construct($id, $plugin_version, $utilities)
    {
		$this->id = $id;
        $this->utilities = $utilities;
        add_action( 'rest_api_init', array($this, 'register_aviation_core_args') );
    }

    public function register_aviation_core_args()
    {
        register_rest_route( $this->id, 'airports.json', array(
            'methods' => 'GET',
            'callback' => array($this, 'core_args_callback'),
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
