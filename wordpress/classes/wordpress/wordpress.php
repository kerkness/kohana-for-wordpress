<?php

class Wordpress_Wordpress
{
	public static function slug()
	{
		global $wpdb;
		global $post;
		$slug = $wpdb->get_var("SELECT post_name FROM $wpdb->posts WHERE ID = " . $post->ID );
		return $slug;
	}	
	
	public static function postid()
	{
		
	}
	
	final private function __construct()
	{
		// This is a static class
	}
}