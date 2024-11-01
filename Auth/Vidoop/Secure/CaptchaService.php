<?php

require_once dirname(__FILE__) . '/Service.php';

/**
 * VidoopSecure Captcha Service.
 */
class Auth_Vidoop_Secure_CaptchaService extends Auth_Vidoop_Secure_Service {

	/**
	 * Request a new captcha be generated.  
	 *
	 * Certain minimum security requirements apply:
	 *   - There must be at least 7 images in the captcha (width * height). 
	 *   - For captchas with 7 images, the minimum captcha_length is 5
	 *   - For captchas with 8 - 11 images, the minimum captcha_length is 4
	 *   - For captchas with 12 or more images, the minimum captcha_length is 3
	 *
	 * @param array $args options for generated captcha.  Valid array keys are:
	 * 		captcha_length    => (default: 3) Number of categories that must be entered to solve the captcha.
	 * 		order_matters     => (deprecated: must be true) Whether the categories should be entered in order.
	 * 		width             => (default: 4) Width of the captcha in images.
	 * 		height            => (default: 3) Height of the captcha in images.
	 * 		image_code_color  => Color of the image code.
	 * 		image_code_length => Number of characters in each image code.
	 *
	 * @return Auth_Vidoop_Captcha object
	 */
	public function new_captcha($args = null) {
		$defaults = array(
			'captcha_length' => 3,
			'order_matters' => true,
			'width' => 4,
			'height' => 3,
		);

		$args = array_merge($defaults, (array) $args);

		$api_url = $this->api_base . '/vs/customers/' . $this->site->customer . '/sites/' . $this->site->name . '/services/captcha';

		$credentials = $this->site->username . ':' . $this->site->password;
		$response = $this->vs->api_call($api_url, 'POST', $args, $credentials);

		if ($response['status'] == 201) {
			return Auth_Vidoop_Captcha::from_xml($response['body']);
		}
	}


	/**
	 * Get the requested captcha.
	 *
	 * @param string ID of the captcha
	 * @return Auth_Vidoop_Captcha object
	 */
	public function get_captcha($id) {
		$api_url = $this->api_base . '/vs/captchas/' . $id;

		$response = $this->vs->api_call($api_url, 'GET');

		if ($response['status'] == 200) {
			return Auth_Vidoop_Captcha::from_xml($response['body']);
		}
	}


	/**
	 * Verify the code for a captcha.
	 *
	 * @param string $id ID of captcha
	 * @param string $code code to test for captcha
	 *
	 * @return boolean true if the code is valid for the captcha
	 */
	public function verify_code($id, $code) {
		$api_url = $this->api_base . '/vs/captchas/' . $id;

		$response = $this->vs->api_call($api_url, 'POST', array('code' => $code));

		if ($response['status'] == 200) {
			return true;
		} else {
			return false;
		}
	}

}


/**
 * Captcha provided by the VidoopSecure Captcha service.
 */
class Auth_Vidoop_Captcha {

	/** 
	 * Catpcha ID
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Number of categories that must be entered to solve the captcha.
	 *
	 * @var int
	 */
	public $captcha_length;

	/**
	 * Whether the categories must be entered in order.
	 *
	 * @var boolean
	 */
	public $order_matters;

	/**
	 * Width of the captcha in number of images.
	 *
	 * @var int
	 */
	public $width;

	/**
	 * Height of the captcha in number of images.
	 *
	 * @var int
	 */
	public $height;

	/**
	 * Color of the image code.
	 *
	 * @var string
	 */
	public $image_code_color;

	/**
	 * Number of characters in each image code.
	 */
	public $image_code_length;

	/**
	 * The image categories which should be identified in the captcha.
	 *
	 * @var Array
	 */
	public $categories;

	/**
	 * Instruction text, directing the user how to identify the captcha.
	 *
	 * @var string
	 */
	public $text;

	/**
	 * URL of captcha image to display to the user.
	 *
	 * @var string
	 */
	public $image;

	/**
	 * Has identification of this captcha already been attempted.
	 *
	 * @var boolean
	 */
	public $attempted;

	/**
	 * Was this captcha successfully authenticated.
	 *
	 * @var boolean
	 */
	public $authenticated;

	/**
	 * Raw XML string this CAPTCHA object was created from.
	 *
	 * @var string
	 */
	public $xml;

	/**
	 * Generate a captcha instance from the provided XML.
	 *
	 * @param string $xml XML provided from VidoopSecure API
	 * @return Auth_Vidoop_Captcha captcha instance
	 */
	public static function from_xml($xml) {

		$simplexml = simplexml_load_string($xml);

		$captcha = new Auth_Vidoop_Captcha();
		$captcha->xml = $xml;
		$captcha->id = (string) $simplexml->id;
		$captcha->captcha_length = (int) $simplexml->captcha_length;
		$captcha->order_matters = Auth_Vidoop_Secure::parse_boolean($simplexml->order_matters);
		$captcha->width = (int) $simplexml->width;
		$captcha->height = (int) $simplexml->height;
		$captcha->image_code_color = (string) $simplexml->image_code_color;
		$captcha->image_code_length = (int) $simplexml->image_code_length;
		$captcha->text = (string) $simplexml->text;
		$captcha->image = (string) $simplexml->imageURI;
		$captcha->attempted = Auth_Vidoop_Secure::parse_boolean($simplexml->attempted);
		$captcha->authenticated = Auth_Vidoop_Secure::parse_boolean($simplexml->authenticated);

		$captcha->categories = array();
		foreach($simplexml->category_names->category_name as $category) {
			$captcha->categories[] = (string) $category;
		}

		return $captcha;
	}
}

?>
