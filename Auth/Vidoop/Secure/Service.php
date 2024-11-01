<?php

/**
 * Abstract class for all VidoopSecure Services.
 */
abstract class Auth_Vidoop_Secure_Service {

	/**
	 * VidoopSecure maanger that created this service.
	 *
	 * @var Auth_Vidoop_Secure
	 */
	protected $vs;

	/**
	 * Base URL to use for VidoopSecure API
	 *
	 * @var string
	 */
	protected $api_base;

	/**
	 * VidoopSecure site this service is for.
	 *
	 * @var Auth_Vidoop_Secure_Site
	 */
	protected $site;

	/**
	 * Constructor.
	 *
	 * @param object $vs Auth_Vidoop_Secure instance
	 * @param object $site Auth_Vidoop_Secure_Site instance
	 * @param string $api_base Base URL to use for VidoopSecure API
	 */
	public function __construct(&$vs, $site, $api_base) {
		$this->vs =& $vs;
		$this->site = $site;
		$this->api_base = $api_base;
	}

}

?>
