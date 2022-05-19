<?php


class Dynamic_Aviation_YoastSEO_Fix
{
	
	public static function wpseo_exclude( $tag )
	{
		global $post;
		
		if(get_query_var( 'fly' ) || get_query_var( 'cacheimg' ) || is_singular('destinations') || is_singular('jet'))
		{
			$tag = false;
		}
		return $tag;
	}
	
	public static function yoast_fixes()
	{
		add_filter('wpseo_canonical', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));		
		add_filter('wpseo_title', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));		
		add_filter('wpseo_metadesc', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));		
		add_filter('wpseo_author_link', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));			
		add_filter('wpseo_author_link', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));		
		add_filter('wpseo_locale', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));

		
		//open graph
		add_filter('wpseo_opengraph_title', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));		
		add_filter('wpseo_opengraph_url', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));		
		add_filter('wpseo_opengraph_site_name', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));		
		add_filter('wpseo_opengraph_type', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));		
		add_filter('wpseo_opengraph_image', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));		
		add_filter('wpseo_opengraph_image_size', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));
		add_filter('wpseo_opengraph_desc', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));
		
		add_filter('wpseo_prev_rel_link', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));		
		add_filter('wpseo_next_rel_link', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));

		//image
		add_filter('wpseo_xml_sitemap_img_src', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));
		
		//twitter
		add_filter('wpseo_twitter_card_type', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));
		add_filter('wpseo_twitter_image', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));
		add_filter('wpseo_twitter_description', array('Dynamic_Aviation_YoastSEO_Fix', 'wpseo_exclude'));		
	}	
}

