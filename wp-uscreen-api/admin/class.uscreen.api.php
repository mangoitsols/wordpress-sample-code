<?php
class uscreenAPI {
	private static $_endpoint;
	private static $_key;

	/**
	 * Private Constructor
	 */
	public function __construct() {
		self::$_endpoint = uscreenAdminInterface::getEndpoint();
		self::$_key = uscreenAdminInterface::getKey();
	}

	/**
	 * Generates the headers to pass to API request.
	 * @since 1.0
	 */
	public static function get_headers() {
		return apply_filters(
			'uscreen_api_request_headers',
			array(
				'Accept'              => 'application/json',
				'Authorization'       => "Authorization : ".base64_encode(self::$_key),
			)
		);
	}

	/**
	 * Fetches data from API file in site.
	 * @param $request_url, request url for API, default=''
	 * @param $method, request action method type, default='POST'
	 * @since 1.0
	 */
	public function APIRequest ($request_url = '', $method = "POST", $data='') {	
		$headers         = self::get_headers();
		if( $request_url != '' ) {
			/*For fetching data through 'curl' */
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "".self::$_endpoint.$request_url."");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			if ($data!='') {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			}

			curl_setopt($ch, CURLOPT_POST, 1);
			$headers = array();
			$headers[] = "Accept: application/json";
			$headers[] = "X-Store-Token: ".self::$_key."";
			$headers[] = "Content-Type: application/json";
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);

			if (curl_errno($ch)) {
			    WC_Uscreen_Logger::log( self::$_endpoint.$request_url." error: " . curl_error($ch) );
			} else {
				WC_Uscreen_Logger::log( self::$_endpoint.$request_url." response: " . print_r( $result, true ) );
				return $result;
			}

			curl_close ($ch);	
		}
	}

	/**
	 * Fetches programs with pagination
	 * @param $pageno, pagination, default = 1
	 * @return programs with $pageno
	 * @since 1.0
	 */
	public function getProgramsUrl( $pageno = 1 ) {
		return	'/programs?page='.$pageno;
	}

	/**
	 * Fetches session API
	 * @return sessions
	 * @since 1.0
	 */
	public function getSessionsUrl() {
		return	'/sessions';
	}

	/**
	 * Fetches chapters of program API through program id.
	 * @param $program_id
	 * @return chapters
	 * @since 1.0
	 */
	public function getProgramChaptersUrl($program_id) {
		return	'/programs/'.$program_id.'/chapters';
	}

	/**
	 * Fetches program API through program id.
	 * @param $program_id
	 * @return chapters
	 * @since 1.0
	 */
	public function getSingleProgramUrl($program_id) {
		return	'/programs/'.$program_id;
	}

	/**
	 * Fetches session token.
	 * @return $token
	 * @since 1.0
	 */
	public function getSessionToken() {
	    $email = uscreenAdminInterface::getUscreenEmail();
	    $password = uscreenAdminInterface::getUscreenPassword();

	    if (isset($email) && isset($password)) {
	        $data = "{\"email\": \"".$email."\",\"password\": \"".$password."\"}";
	    }

	    $response_arr = self::APIRequest(self::getSessionsUrl(), "POST", $data);
	    if ($response_arr) {
		    $responses = json_decode($response_arr);
		    foreach ($responses as $response) {
		    	$token = $response->token;
		    }

		    if (isset($token) && $token!='') {
		    	WC_Uscreen_Logger::log("Token: " . $token);
		    	return $token;
		    }
		}
	}

	/**
	 * Fetches session token.
	 * @return $token
	 * @since 1.0
	 */
	public function getPrograms($page_no=1) {
	    $response_arr = self::APIRequest(self::getProgramsUrl($page_no), "GET");
	    if ($response_arr) {
		    $responses = json_decode($response_arr);
	    	WC_Uscreen_Logger::log("Program Lists: " . print_r( $responses, true ));
	    	return $responses;
		}
	}
}
?>