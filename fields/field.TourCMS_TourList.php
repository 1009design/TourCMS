<?php

	/**
	 * @package toolkit
	 */
	
	 
	/**
	 * @package extension
	 */
	require_once(EXTENSIONS . '/tourcms/lib/tourcms.php');

	/**
	 *
	 */
	Class fieldTourCMS_TourList extends Field {
	
		const DATA_DIVIDER = ',';

		public function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = __('TourCMS: Tour List');
			$this->_required = true;

			// Set default
			$this->set('show_column', 'no');
			$this->set('location', 'sidebar');
			$this->set('required', 'no');
		}

		public function canToggle(){
			return false;
		}

		public function allowDatasourceOutputGrouping(){
			return false;
		}

		public function allowDatasourceParamOutput(){
			return true;
		}

		public function canFilter(){
			return false;
		}

		public function canImport(){
			return true;
		}

		public function canPrePopulate(){
			return true;
		}

		public function isSortable(){
			return true;
		}

		public function appendFormattedElement(&$wrapper, $data, $encode=false){
			if (!is_array($data) or is_null($data['value'])) return;

			$list = new XMLElement($this->get('element_name'));

			if (!is_array($data['handle']) and !is_array($data['value'])) {
				$data = array(
					'handle'	=> array($data['handle']),
					'value'		=> array($data['value'])
				);
			}

			foreach ($data['value'] as $index => $value) {
				$list->appendChild(new XMLElement(
					'item',
					General::sanitize($value),
					array(
						'handle'		=> $data['handle'][$index]
					)
				));
			}

			$wrapper->appendChild($list);
		}
		
		public function getTourCMSTourList() {
		
			//
			// the tour list should come from a cached page;
			//
			
			//$env = FrontendPage::instance()->Page()->Env();
			//var_dump($this->_env);
			
			$config = Symphony::Configuration();
			
			$marketplace_account_id = $config->get('marketplace-account-id','tour-cms');
			$channel_id             = $config->get('channel-id','tour-cms');
			$api_private_key        = $config->get('api-private-key','tour-cms');
			$result_type            = 'simplexml';
			
			$tc = new TourCMS($marketplace_account_id, $api_private_key, $result_type);
			
			return $tc->list_tours($channel_id);
			//return $tc->list_tours(2);
		}
		
		public function displayPublishPanel(&$wrapper, $data=null, $flagWithError=null, $fieldnamePrefix=null, $fieldnamePostfix=null){
			if(!is_string($data['tour_id'])) {
				$data['tour_id'] = array();
			} else {
				$data['tour_id'] = explode(self::DATA_DIVIDER,$data['tour_id']);
			}
			
			//create select box options
			$options = array();
			
			$tours = $this->getTourCMSTourList();
			//print_r($tours);
			if(!$tours) {
				$label = Widget::Label('Unable to connect to TourCMS. Please check your TourCMS settings in the preference');
				$wrapper->appendChild($label);
				return;
			} else if(!isset($tours->error) || $tours->error != 'OK') {
				$label = Widget::Label('TourCMS error: '.$tours->error);
				$wrapper->appendChild($label);
				return;
			}
			
			foreach($tours->tour as $tour) {
				//http://downloads.symphony-cms.com/learn/api/2.2/toolkit/widget/
				//array($value, $selected, $desc, $class, $id, $attr)
				$options[] = array(General::sanitize($tour->tour_id), in_array($tour->tour_id, $data['tour_id']),General::sanitize($tour->tour_name));
			}
			
			//the field name in database
			$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix.'[]';
			
			//create select box ui
			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Select($fieldname, $options, array('multiple' => 'multiple')));

			//wrap the element with error if is error flagged
			if($flagWithError != NULL) {
				$wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			} else {
				$wrapper->appendChild($label);
			}
		}

		public function prepareTableValue($data, XMLElement $link=null){
			$value = $data['value'];

			if(!is_array($value)) {
				$value = array($value);
			}

			return parent::prepareTableValue(array('value' => @implode(', ', $value)), $link);
		}
		
		public function checkPostFieldData($data, &$message=null, $entry_id=null){
			return parent::checkPostFieldData($data,$message,$entry_id);
		}

		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=null){
			$status = self::__OK__;
			
			if(!is_array($data)) {
				return array('tour_id' => '', 'tour_name' => '');
			}

			if(empty($data)) {
				return NULL;
			}
			
			$tours = $this->getTourCMSTourList();
			
			$tour_id = array();
			$tour_name = array();
			foreach($tours->tour as $tour) {
				if( in_array($tour->tour_id,$data) ) {
					$tour_id[] = $tour->tour_id;
					$tour_name[] = General::sanitize($tour->tour_name);
				}
			}
			
			return array('tour_id' => implode(self::DATA_DIVIDER,$tour_id), 
						 'tour_name' => implode(self::DATA_DIVIDER,$tour_name));
		}

		public function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation=false){
			$field_id = $this->get('id');

			if (self::isFilterRegex($data[0])) {
				$this->_key++;

				if (preg_match('/^regexp:/i', $data[0])) {
					$pattern = preg_replace('/regexp:/i', null, $this->cleanValue($data[0]));
					$regex = 'REGEXP';
				} else {
					$pattern = preg_replace('/not-?regexp:/i', null, $this->cleanValue($data[0]));
					$regex = 'NOT REGEXP';
				}

				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND (
						t{$field_id}_{$this->_key}.value {$regex} '{$pattern}'
						OR t{$field_id}_{$this->_key}.handle {$regex} '{$pattern}'
					)
				";

			} elseif ($andOperation) {
				foreach ($data as $value) {
					$this->_key++;
					$value = $this->cleanValue($value);
					$joins .= "
						LEFT JOIN
							`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
							ON (e.id = t{$field_id}_{$this->_key}.entry_id)
					";
					$where .= "
						AND (
							t{$field_id}_{$this->_key}.value = '{$value}'
							OR t{$field_id}_{$this->_key}.handle = '{$value}'
						)
					";
				}

			} else {
				if (!is_array($data)) $data = array($data);

				foreach ($data as &$value) {
					$value = $this->cleanValue($value);
				}

				$this->_key++;
				$data = implode("', '", $data);
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND (
						t{$field_id}_{$this->_key}.value IN ('{$data}')
						OR t{$field_id}_{$this->_key}.handle IN ('{$data}')
					)
				";
			}

			return true;
		}

		public function commit(){
			if(!parent::commit()) {
				return false;
			}

			$id = $this->get('id');

			if($id === false) {
				return false;
			}

			$fields = array();
			$fields['field_id'] = $id;

			Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");

			if(!Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle())) {
				return false;
			}

			return true;
		}

		public function checkFields(&$errors, $checkForDuplicates=true){
			parent::checkFields($errors, $checkForDuplicates);
		}

		public function displaySettingsPanel(&$wrapper, $errors=null){
			parent::displaySettingsPanel($wrapper, $errors);
		}

		public function createTable(){
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `tour_id` varchar(255) default NULL,
				  `tour_name` varchar(255) default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`)
				) ENGINE=MyISAM;"
			);
		}

		public function getExampleFormMarkup(){
			return false;
		}

	}
