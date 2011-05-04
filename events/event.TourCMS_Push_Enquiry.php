<?php

	require_once(TOOLKIT . '/class.event.php');

	Class eventTourCMS_Push_Enquiry extends Event{

		const ROOTELEMENT = 'tourcms_enquiry';

		public $eParamFILTERS = array(
			'campaignmonitor'
		);

		public static function about(){
			return array(
					 'name' => 'TourCMS Push Enquiry',
					 'author' => array(
							'name' => '',
							'website' => 'http://lsrsports.com',
							'email' => ''),
					 'version' => '1.0',
					 'release-date' => '2011-05-03T19:24:00+00:00',
					 'trigger-condition' => 'action[tourcms_push_enquiry]');
		}

		public static function getSource(){
			return false;
		}

		public static function allowEditorToParse(){
			return false;
		}

		public static function documentation(){
			return '
        <h3>Success and Failure XML Examples</h3>
        <p>When saved successfully, the following XML will be returned:</p>
        <pre class="XML"><code>&lt;newsletter result="success" type="create | edit">
  &lt;message>Entry [created | edited] successfully.&lt;/message>
&lt;/newsletter></code></pre>
        <p>When an error occurs during saving, due to either missing or invalid fields, the following XML will be returned:</p>
        <pre class="XML"><code>&lt;newsletter result="error">
  &lt;message>Entry encountered errors when saving.&lt;/message>
  &lt;field-name type="invalid | missing" />
  ...
&lt;/newsletter></code></pre>
        <p>The following is an example of what is returned if any options return an error:</p>
        <pre class="XML"><code>&lt;newsletter result="error">
  &lt;message>Entry encountered errors when saving.&lt;/message>
  &lt;filter name="admin-only" status="failed" />
  &lt;filter name="send-email" status="failed">Recipient username was invalid&lt;/filter>
  ...
&lt;/newsletter></code></pre>
        <h3>Example Front-end Form Markup</h3>
        <p>This is an example of the form markup you can use on your frontend:</p>
        <pre class="XML"><code>&lt;form method="post" action="" enctype="multipart/form-data">
  &lt;input name="MAX_FILE_SIZE" type="hidden" value="5242880" />
  &lt;label>Name
    &lt;input name="fields[name]" type="text" />
  &lt;/label>
  &lt;label>Emaill address
    &lt;input name="fields[emaill-address]" type="text" />
  &lt;/label>
  &lt;input name="action[newsletter]" type="submit" value="Submit" />
&lt;/form></code></pre>
        <p>To edit an existing entry, include the entry ID value of the entry in the form. This is best as a hidden field like so:</p>
        <pre class="XML"><code>&lt;input name="id" type="hidden" value="23" /></code></pre>
        <p>To redirect to a different location upon a successful save, include the redirect location in the form. This is best as a hidden field like so, where the value is the URL to redirect to:</p>
        <pre class="XML"><code>&lt;input name="redirect" type="hidden" value="http://lsrsports.com/success/" /></code></pre>
        <h3>Campaign Monitor Filter</h3>
        <p>
        To use the Campaign Monitor filter, add the following field to your form:
      </p>
        <pre class="XML"><code>&lt;input name="campaignmonitor[list]" value="{$your-list-id}" type="hidden" />
&lt;input name="campaignmonitor[field][Name]" value="$field-first-name, $field-last-name" type="hidden" />
&lt;input name="campaignmonitor[field][Email]" value="$field-email-address" type="hidden" />
&lt;input name="campaignmonitor[field][Custom]" value="Value for field Custom Field..." type="hidden" /></code></pre>
        <p>
        If you require any existing Campaign Monitor subscriber\'s data to be merged, you can provide
        the fields you want to merge like so:
      </p>
        <pre class="XML"><code>&lt;input name="campaignmonitor[merge-fields]" value="Name of Custom Field1, Name of CustomField2" type="hidden" /></code></pre>';
		}

		public function load(){
			if(isset($_POST['action']['tourcms_push_enquiry'])) {
				return $this->__trigger();
			}
		}

		protected function __trigger(){
			include(EXTENSIONS . '/tourcms/lib/tourcms-legacy.php');
			
			//acquire legacy api settings
			$config = Symphony::Configuration();
			$url = $config->get('legacy-api-base-url','tour-cms');
			$password = $config->get('legacy-api-password-key','tour-cms');
			//print_r($url);
			//print_r($password);
			
			//initialize API wrapper
			$legacy = new TourCMS_Legacy($url,$password);
			
			//push to TourCMS and return response
			$response = $legacy->push($_POST);
			if(is_null($response)) {
				$response = '<error>null</error>';
			} else if( $response === false ) {
				$response = '<error>false</error>';
			} else if( $response == '' ) {
				$response = '<error>empty</error>';
			} else {
				$response = substr($response,strpos($response,'<response>'));
			}
			//var_dump($_POST);
			//die();
			$result = new XMLElement(self::ROOTELEMENT,$response);
			//$result[''] = $response;
			//print_r($result);
			//die();
			return $result;
			//return $response;
		}

	}
