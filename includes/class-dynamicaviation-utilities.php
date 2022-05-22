<?php 


class Dynamic_Aviation_Utilities {

	public function json_src_url()
	{
		return 'const jsonsrc = () => { return "'.esc_url(plugin_dir_url( __DIR__ )).'/public/";}';
	}
    
	public function algoliasearch_after()
	{
		$output = '';

		if(get_option('algolia_token') && get_option('algolia_index') && get_option('algolia_id'))
		{
			$output .= 'const algoliaClient = algoliasearch(getAlgoliaId, getAlgoliaToken);';
			$output .= 'const algoliaIndex = algoliaClient.initIndex(getAlgoliaIndex);';
		}

		return $output;
	}
	public function algoliasearch_before()
	{
		$output = null;
		$algolia_token = get_option('algolia_token');
		$algolia_index = get_option('algolia_index');
		$algolia_id = get_option('algolia_id');
		
		if($algolia_token && $algolia_index && $algolia_id)
		{
			$output .= 'const getAlgoliaToken = "'.esc_html($algolia_token).'";';	
			$output .= 'const getAlgoliaIndex = "'.esc_html($algolia_index).'";';
			$output .= 'const getAlgoliaId = "'.esc_html($algolia_id).'";';
		}
		return $output;
	}
}


?>