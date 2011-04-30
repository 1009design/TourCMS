<?php
	
	Class Extension_TourCMS extends Extension {

		public function __construct(Array $args) {
			parent::__construct($args);
		}
	
		public function about() {
			return array(
				'name' => 'TourCMS',
				'version' => '1.0',
				'release-date' => '2011-04-27',
				'author' => array(
					'name' => 'Willy Tseng',
					'website' => 'http://1009design.com/',
					'email' => 'willy@1009design.com'
				),
				'description' => 'A library of components that pulls data off Tour CMS via its API.'
			);
		}
	
		public function getSubscribedDelegates() {
			return array(
				// Append form controls to prefrence page
				array(
					'page' => '/system/preferences/',
					'delegate' => 'AddCustomPreferenceFieldsets',
					'callback' => 'appendPreferences'
				),
			);
		}

		public function uninstall() {
			// Drop field configuration table
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_tourcms_tourlist`");
		
			Symphony::Configuration()->remove('tour-cms');
			Administration::instance()->saveConfig();
		}
		
		public function install() {
			// Create field configuration table
			return $this->_Parent->Database->query("CREATE TABLE `tbl_fields_tourcms_tourlist` (
				`id` int(11) unsigned NOT NULL auto_increment,
				`field_id` int(11) unsigned NOT NULL,
				PRIMARY KEY (`id`),
				KEY `field_id` (`field_id`))"
			);
		}
		
		/**
		 * Append TourCMS preferences
		 *
		 * @param array $context
		 *  delegate context
		 */
		public function appendPreferences($context) {

			// Create preference group
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Tour CMS')));
			
			$config = Symphony::Configuration();
			
			// Append settings
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');

			// API Private Key
			$api_key = $config->get('api-private-key','tour-cms');
			$api_key_input = Widget::Input('settings[tour-cms][api-private-key]', empty($api_key) ? '' : $api_key, 'text');
			$api_key_label = Widget::Label('API Private Key', $api_key_input);

			// Result Type
			$result_type = $config->get('result-type','tour-cms');
			$result_type_options = array(
				array('simplexml', $result_type == 'simplexml' ? true : false, __('SimpleXML')),
				array('raw', $result_type == 'raw' ? true : false, __('Raw')),
			);
			$result_type_input = Widget::Select('settings[tour-cms][result-type]', $result_type_options);
			$result_type_label = Widget::Label('Result Type', $result_type_input);

			// Market Place Account ID
			$mpa_id = $config->get('marketplace-account-id','tour-cms');
			$mpa_id_input = Widget::Input('settings[tour-cms][marketplace-account-id]', empty($mpa_id) ? '0' : $mpa_id, 'text');
			$mpa_id_label = Widget::Label('Marketplace account ID', $mpa_id_input);
			$mpa_id_help = new XMLElement('p', __('Leave this as zero if you are a supplier (i.e. not a Marketplace partner)'), array('class' => 'help'));

			// Channel ID
			$ch_id = $config->get('channel-id','tour-cms');
			$ch_id_input = Widget::Input('settings[tour-cms][channel-id]', empty($ch_id) ? '0' : $ch_id, 'text');
			$ch_id_label = Widget::Label('Channel ID', $ch_id_input);
			$ch_id_help = new XMLElement('p', __('Leave this as zero if you are a Marketplace partner (i.e. not a supplier)'), array('class' => 'help'));
			
			// URL of the cached Xml
			$url = $config->get('url','tour-cms');
			$url_input = Widget::Input('settings[tour-cms][url]', empty($url) ? '' : $url, 'text');
			$url_label = Widget::Label('Cache Url', $url_input);
			$url_help = new XMLElement('p', __('URL of the cached data from TourCMS here. e.g. http://your.domain/api/tourcms-cache/'), array('class' => 'help'));

			// Append widgets to output
			$div->appendChild($api_key_label);
			$div->appendChild($result_type_label);

			$group->appendChild($div);
			
			$group->appendChild($mpa_id_label);
			$group->appendChild($mpa_id_help);

			$group->appendChild($ch_id_label);
			$group->appendChild($ch_id_help);
			
			$group->appendChild($url_label);
			$group->appendChild($url_help);

			// Append to preferences group
			$context['wrapper']->appendChild($group);
		}
	}