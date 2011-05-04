<?php

	/**
	 * API - New customer / enquiry
	 *
	 * reference http://www.tourcms.com/support/api/detail/api_newcustomer.php#examples
	 *
	 * XML NODE				NOTES
	 * title				Customer title e.g. Mr, Mrs etc
	 * firstname			First name
	 * middlename			Middle name (or initial)
	 * surname				Surname
	 * address				Address (can be multi-line)
	 * city					City
	 * county				County / State
	 * postcode				Postcode / Zipcode
	 * country				2 digit country code (uppercase)
	 * gender				Gender - either m, f or blank (for unknown)
	 * nationality			2 digit country code (uppercase)
	 * dob					Date of Birth (YYYY-MM-DD)
	 * agecat				Age category - 1 digit code (i-Infant, c-Child, a-Adult, s-Senior)
	 * passportnumber		Passport number
	 * passportplaceofissue	Passport place of issue
	 * passportissuedate	Passport issue date (YYYY-MM-DD)
	 * passportexpirydate	Passport expiry date (YYYY-MM-DD)
	 * agentid				Travel agent ID (internal ID number)
	 * wherehear			Where did the customer hear about us (doesn't have to be pre-configured)
	 * email				Email address
	 * fax					Fax number
	 * telhome				Tel home / evening
	 * telwork				Tel work / day
	 * telmobile			Tel mobile
	 * telsms				Tel sms
	 * contactnote			Contact note (e.g. don't call before 8pm)
	 * diet					Dietary requirements
	 * medical				Medical conditions
	 * nokname				Emergency contact name
	 * nokrelationship		Emergency contact relationship
	 * noktel				Emergency contact telephone number
	 * nokcontact			Emergency contact other note (can be multi-line)
	 * notes				Text to add to customer notes (can be multi-line)
	 * permemail			Set to 0 for no email marketing permission. 1 for email marketing permission. Blank if you want the defaults (configured in Configuration & Setup) to be applied
	 * enquiry	
	 * 		XML NODE		NOTES
	 * 		type			"Brochure", "Tailor-made tour" request, contact us form etc
	 * 		category		Sub-category for type (e.g. if for a brochure, the brochure name)
	 * 		detail			The main note for the enquiry (can be multi-line)
	 * 		note			Internal note for the enquiry (can be multi-line)
	 * 		username		TourCMS staff user enquiry should be assigned to
	 * 		value			Value (e.g. financial value) (Doesn't have to be numeric)
	 * 		outcome			Outcome
	 * 		followup_date	Followup date (YYYY-MM-DD)
	 * 		send_email		Set to 0 to override the settings inside TourCMS for whether to send email to staff user for this enquiry
	 */

	class TourCMS_Legacy {
		protected $apiUrl = null;
		protected $password = null;
		
		public function __construct($apiUrl, $password) {
			$this->apiUrl = $apiUrl;
			$this->password = $password;
		}
		
		public function push($data) {
			if(empty($data) || !is_array($data)) {
				return false;
			}
		
			$xml = '';
		
			foreach($data as $k => $v) {
				$k = strtolower($k);
				switch($k) {
					case 'title':
					case 'firstname':
					case 'middlename':
					case 'surname':
					case 'address':
					case 'city':
					case 'country':
					case 'postcode':
					case 'country':
					case 'gender':
					case 'nationality':
					case 'dob':
					case 'agecat':
					case 'passportnumber':
					case 'passportplaceofissue':
					case 'passportexpirydate':
					case 'agentid':
					case 'wherehear':
					case 'email':
					case 'fax':
					case 'telhome':
					case 'telwork':
					case 'telmobile':
					case 'telsms':
					case 'contactnote':
					case 'diet':
					case 'medical':
					case 'nokname':
					case 'nokrelationship':
					case 'noktel':
					case 'nokcontact':
					case 'notes':
					case 'permemail':
						$xml .= sprintf('<%s>%s</%s>',$k,htmlspecialchars($v),$k);
						break;
						
					case 'enquiry':
						$xml .= $this->createEnquiry($v);
						break;
					
				}
			}
			
			if( empty($xml) ) {
				return false;
			}
			$xml .= '<transaction>NewCustomer</transaction>';
			$xml .= '<apipassword>' . $this->password . '</apipassword>';
			$xml = '<query>' . $xml . '</query>';
			/*
			$XPost = '' .
				'<query>' . 
					'<transaction>NewCustomer</transaction>' .
					'<apipassword>' . $this->password . '</apipassword>' .
					'<surname>John Doe</surname>' .
				'</query>';
			*/
			return $this->sendPost($xml);
		}
		
		protected function createEnquiry($data) {
			if(empty($data) || !is_array($data)) {
				return '';
			}
			
			$enquiry = '';
			foreach($data as $k => $v) {
				$k = strtolower($k);
				switch($k) {
					case 'type':
					case 'category':
					case 'detail':
					case 'note':
					case 'username':
					case 'value':
					case 'outcome':
					case 'followup_date':
					case 'send_email':
						$enquiry .= sprintf('<%s>%s</%s>',$k,htmlspecialchars($v),$k);
						break;
				}
			}
			
			if( empty($enquiry) ) {
				return '';
			}
			
			$enquiry = '<enquiry>' . $enquiry . '</enquiry>';
			return $enquiry;
		}
		
		protected function sendPost($xml) {
			//print_r($xml);
			$url = $this->apiUrl; 
 
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, $url); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 		// return into a variable 
			curl_setopt($ch, CURLOPT_TIMEOUT, 4); 				// times out after 4s 
			curl_setopt($ch, CURLOPT_HEADER, 0); 
			$result = curl_exec($ch); 							// run the whole process 
			 
			return $result; //contains response from server 
		}
	}

