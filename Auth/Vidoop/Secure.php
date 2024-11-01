<?php
/**
 * This library provides convenient access to the suite of VidoopSecure Services.
 *
 * You will first need to register with VidoopSecure in order to receive an API 
 * username and password which are used to identify your application to the 
 * service.
 *
 * This library requires PHP5, compiled with SimpleXML and libcurl.
 *
 * @see http://www.vidoopsecure.com/
 */


/**
 * VidoopSecure Service.
 */
class Auth_Vidoop_Secure {

	/** 
	 * Default base URL for VidoopSecure API 
	 */
	const DEFAULT_API_BASE = 'https://api.vidoop.com';

	/**
	 * Base URL to use for VidoopSecure API (sans trailing slash)
	 *
	 * @var string
	 */
	private $api_base;

	/**
	 * ID of customer account for VidoopSecure
	 *
	 * @var string
	 */
	private $customer;

	/**
	 * Password of customer account for VidoopSecure
	 *
	 * @var string
	 */
	private $password;

	/**
	 * Name of site registered in VidoopSecure
	 *
	 * @var Auth_Vidoop_Secure_Site
	 */
	private $site;

	/**
	 * Whether this VidoopSecure instance is running in debug mode.
	 *
	 * @var boolean
	 */
	private $debug;


	/**
	 * Constructor.
	 *
	 * @param string $customer ID of customer account for VidoopSecure
	 * @param string $password password of customer account for VidoopSecure (not currently used)
	 * @param string $api_base Base URL to use for VidoopSecure API
	 */
	public function __construct($customer, $password = null, $api_base = '') {
		if (!empty($api_base)) {
			$this->api_base = $api_base;
		} else {
			$this->api_base = self::DEFAULT_API_BASE;
		}

		$this->customer = $customer;
		$this->password = $password;
		$this->debug = false;
	}


	/**
	 * Set whether this VidoopSecure instance is running in debug mode.  Debug 
	 * mode will enable things liks additional logging and will disable 
	 * certificate validation when making API calls.  Do not ever set this to 
	 * TRUE in a production environment!
	 *
	 * @param boolean $debug new value for debug flag
	 */
	public function setDebug($debug) {
		$this->debug = $debug;
	}


	/**
	 * Select which site to use for any subsequent site-specific API calls.
	 *
	 * @param string $name name of site registered in VidoopSecure
	 * @param string $username VidoopSecure API username for this site
	 * @param string $password VidoopSecure API password for this site
	 */
	public function site($name = null, $username = null, $password = null) {
		if ($name && $username && $password) {
			$this->site = new Auth_Vidoop_Secure_Site($this->customer, $name, $username, $password);
		}

		return $this->site;
	}


	/**
	 * Get a VidoopSecure service.
	 *
	 * @param string $name name of service.  Valid values are: captcha.
	 * @param object $site Auth_Vidoop_Secure_Site to get service for.  If null, will use default site set using site().
	 * @see Auth_Vidoop_Secure::site
	 * @return Auth_Vidoop_Secure_Service
	 */
	public function get_service($name, $site = null) {
		if ($site == null) {
			$site = $this->site();
		}

		if ($site == null) {
			throw new Exception('Must provide site');
		}

		switch (strtolower($name)) {
			case 'captcha':
				require_once dirname(__FILE__) . '/Secure/CaptchaService.php';
				$service = new Auth_Vidoop_Secure_CaptchaService($this, $site, $this->api_base);
				break;

			case 'imageshield':
				require_once dirname(__FILE__) . '/Secure/ImageShieldService.php';
				$service = new Auth_Vidoop_Secure_ImageShieldService($this, $site, $this->api_base);
				break;

			case 'enrollment':
				require_once dirname(__FILE__) . '/Secure/EnrollmentService.php';
				$service = new Auth_Vidoop_Secure_EnrollmentService($this, $site, $this->api_base);
				break;

			case 'smsotp':
				break;

			case 'voiceptl':
				break;
		}

		return $service;
	}


	/**
	 * Get list of all available VidoopSecure services.
	 *
	 * @param object $site Auth_Vidoop_Secure_Site to get service for.  If null, will use default site set using site().
	 * @see Auth_Vidoop_Secure::site
	 * @return array names of available services
	 */
	public function get_services($site = null) {
		if ($site == null) {
			$site = $this->site();
		}

		if ($site == null) {
			throw new Exception('Must provide site');
		}

		$api_url = $this->api_base . '/vs/customers/' . $site->customer . '/sites/' . $site->name . '/services';

		$credentials = $site->username . ':' . $site->password;
		$response = $this->api_call($api_url, 'GET', null, $credentials);

		if ($response['status'] != 200) {
			return false;
		}

		$services = array();

		$simplexml = simplexml_load_string($response['body']);
		foreach ($simplexml->service as $s) {
			if (((string) $s['enabled']) == 'true') { 
				$services[] = (string) $s['id'];
			}
		}

		return $services;
	}


	/**
	 * Make API call to VidoopSecure.
	 *
	 * @param string $url absolute URL for API endpoint
	 * @param string $method HTTP method for API call ("GET" or "POST")
	 * @param array $params associative array of parameters to include in API call
	 * @param string $credentials HTTP BasicAuth credentials of the form "username:password"
	 * @return array API response with the following array keys:
	 *      status   => HTTP response status code
	 *      body     => HTTP response body
	 *      error    => if a cURL error occurred, the cURL error message
	 */
	public function api_call($url, $method = "POST", $params = null, $credentials = null) {
		$ch = curl_init();

		if ($credentials) {
			curl_setopt($ch, CURLOPT_USERPWD, $credentials);
		}

		if (strtolower($method) == 'post') {
			curl_setopt($ch, CURLOPT_POST, true);

			if ($params) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			}
		} elseif (strtolower($method) == 'get') {
			if ($params) {
				$url .= '?' . http_build_query($params);
			}
		}

		curl_setopt($ch, CURLOPT_URL, $url);

		if ($this->debug) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$body = curl_exec($ch);

		$response = array(
			'status' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
			'body' => $body,
		);

		if ($body === false) {
			$response['error'] = curl_error($ch);
			error_log($response['error']);
		}

		curl_close($ch);
		return $response;
	}


	/**
	 * Parse string into boolean.  The word 'true' (case insensitive) and the 
	 * number 1 result in TRUE.  Anything else results in FALSE.
	 *
	 * @param string $str string to parse into a boolean
	 * @return boolean
	 */
	public static function parse_boolean($str) {
		if (strtolower($str) == 'true') {
			return true;
		} else if ((int) $str === 1) {
			return true;
		} else {
			return false;
		}
	}
}


/**
 * Site registered with VidoopSecure.
 */
class Auth_Vidoop_Secure_Site {

	/**
	 * ID of customer account.
	 *
	 * @var string
	 */
	public $customer;

	/**
	 * Site name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * API username for this site.
	 *
	 * @var string
	 */
	public $username;

	/**
	 * API Password for this site.
	 *
	 * @var string
	 */
	public $password;

	/**
	 * Constructor.
	 *
	 * @param string $customer ID of the customer account
	 * @param string $name site name
	 * @param string $username API username
	 * @param string $password API password
	 */
	public function __construct($customer, $name, $username, $password) {
		$this->customer = $customer;
		$this->name = $name;
		$this->username = $username;
		$this->password = $password;
	}
}

?>
