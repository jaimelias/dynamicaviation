<?php 


class Dynamic_Aviation_Mapbox {

    public function __construct($utilities)
    {
        $this->utilities = $utilities;
        $this->init();
    }

    public function init()
    {
        add_filter('template_include', array(&$this, 'geojson'), 100);
    }

	public function geojson($output)
	{
		if(isset($_GET['geojson']))
		{
			if($_GET['geojson'] === 'aviation')
			{
				$arr = array();
				$browse_json = $this->utilities->return_json();
				$hits = $browse_json['hits'];
				$count_hits = count($hits);

				for($x = 0; $x < $count_hits; $x++)
				{
					array_push($arr, $hits[$x]);
				}

				if(count($arr) > 0)
				{
					exit(json_encode($arr));
				}
			}
		}

		return $output;
	}
}

?>