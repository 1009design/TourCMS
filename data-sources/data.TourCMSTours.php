<?php

	require_once(TOOLKIT . '/class.datasource.php');
	require_once(EXTENSIONS . '/tourcms/lib/tourcms.php');
	
	Class DatasourceTourCMSTours extends Datasource{
		
		function __construct(&$parent, $env=NULL, $process_params=true){
			parent::__construct($parent, $env, $process_params);
		}
		
		function about(){
			return array(
				 'name' => 'Tour list from TourCMS',
				 'version' => '0.1a',
				 'release-date' => '2011-04-27');	
		}
		
		function grab(&$param_pool){
		
			$config = Symphony::Configuration();
			
			$marketplace_account_id = $config->get('marketplace-account-id','tour-cms');
			$channel_id             = $config->get('channel-id','tour-cms');
			$api_private_key        = $config->get('api-private-key','tour-cms');
			$result_type            = $config->get('result-type','tour-cms');
			
			$tc = new TourCMS($marketplace_account_id, $api_private_key, $result_type);
			
			$root = new XMLElement('tourcms');
			
			$result = '';
			if( isset($this->_env['param']['tour-id']) ) {
			
				$id = explode(',',$this->_env['param']['tour-id']);
				
				foreach($id as $k) {
					$result = $tc->show_tour($k,$channel_id);
					$result = substr($result,strpos($result,'<response>'));
					$showTour = new XMLElement('show_tour',$result,array('tour-id'=>$k));
					$root->appendChild($showTour);
				}
			} else {
				$result = $tc->list_tours($channel_id);
				$result = substr($result,strpos($result,'<response>'));
				$listTour = new XMLElement('list-tour',$result);
				
				$result = $tc->list_tour_images($channel_id);
				$result = substr($result,strpos($result,'<response>'));
				$listTourImages = new XMLElement('list-tour-images',$result);
				
				$root->appendChild($listTour);
				$root->appendChild($listTourImages);
			}
			
			return $root;
		}
	}