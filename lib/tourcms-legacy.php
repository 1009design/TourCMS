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
		
		protected $customerFields = null;
		protected $customerIgnores = null;
		
		protected $enquiryFields = null;
		protected $enquiryIgnores = null;
		
		public function __construct($apiUrl, $password) {
			$this->apiUrl = $apiUrl;
			$this->password = $password;
			
			$this->customerFields = $this->getCustomerFields();
			$this->customerIgnores = $this->getCustomerIgnores();
			
			$this->enquiryFields = $this->getEnquiryFields();
			$this->enquiryIgnores = $this->getEnquiryIgnores();
		}
		
		public function getCustomerFields() {
			// TourCMS specific fields (if they exist) will be added to specific XML nodes
			$fields  = 'title,firstname,middlename,surname,address,city,county,postcode,country,gender,nationality,dob,agecat,';
			$fields .= 'passportnumber,passportplaceofissue,passportissuedate,passportexpirydate,';
			$fields .= 'agentid,wherehear,';
			$fields .= 'email,fax,telhome,telwork,telmobile,telsms,contactnote,';
			$fields .= 'diet,medical,nokname,nokrelationship,noktel,nokcontact,notes,permemail';
			
			return explode(',',$fields);
		}
		
		public function getCustomerIgnores() {
			// Hard code any fields to ignore here, comma separated
			$fields = '';
			return explode(',',$fields);
		}
		
		public function getEnquiryFields() {
			// enquiry specific fields (if they exist) will be added to specific XML nodes
			$fields = 'type,category,detail,note,username,value,outcome,followup_date,send_email';
			return explode(',',$fields);
		}
		
		public function getEnquiryIgnores() {
			// Hard code any fields to ignore here, comma separated
			$fields = '';
			return explode(',',$fields);
		}
		
		public function push($data) {
			if(empty($data) || !is_array($data)) {
				return false;
			}
		
			$xml = '';
			foreach($data as $k => $v) {
				$k = strtolower($k);
				
				if( in_array($k,$this->customerFields) && !in_array($k,$this->customerIgnores) ) {
					$xml .= sprintf('<%s>%s</%s>',$k,htmlspecialchars($v),$k);
				} else if( $k == 'enquiry' ) {
					$xml .= $this->createEnquiry($v);
				}
			}
			
			$response = false;
			if( !empty($xml) ) {
				$xml .= '<transaction>NewCustomer</transaction>';
				$xml .= '<apipassword>' . $this->password . '</apipassword>';
				
				$xml = '<query>' . $xml . '</query>';
				$response = $this->sendPost($xml);
			}
			
			return $response;
		}
		
		protected function createEnquiry($data) {
			if(empty($data) || !is_array($data)) {
				return '';
			}
			
			$enquiry = '';
			foreach($data as $k => $v) {
				$k = strtolower($k);
				
				if( in_array($k,$this->enquiryFields) || !in_array($k,$this->enquiryIgnores) ) {
					$enquiry .= sprintf('<%s>%s</%s>',$k,htmlspecialchars($v),$k);
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

