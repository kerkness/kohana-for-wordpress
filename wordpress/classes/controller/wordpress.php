<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Wordpress extends Controller
{
	public function action_page()
	{
		$this->request->response = View::factory('wp/page');
	}
	
	public function action_index()
	{
		$this->request->response = View::factory('wp/index');
	}
	
	public function action_sidebar()
	{
		$this->request->response = View::factory('wp/sidebar');
	}
	
	public function action_header()
	{
		$this->request->response = View::factory('wp/header');
	}
	
	public function action_footer()
	{
		$this->request->response = View::factory('wp/footer');
	}
	
}