<?php
/*
 Plugin Name: VidoopCAPTCHA
 Plugin URI: http://wordpress.org/extend/plugins/vidoopcaptcha/
 Description: Adds a CAPTCHA to account registration that uses Vidoop's ImageShield.
 Author: Vidoop
 Author URI: http://vidoop.com/
 Version: trunk
 */

require_once dirname(__FILE__) . '/Auth/Vidoop/Secure.php';

add_action('admin_menu', 'vidoop_captcha_admin_menu');
//add_action('preprocess_comment', 'vidoop_captcha_process_comment', -98);
//add_action('comment_form', 'vidoop_captcha_comment_form');
add_action('login_head', 'vidoop_captcha_login_head');
add_action('register_form', 'vidoop_captcha_register_form');
add_action('register_post', 'vidoop_captcha_register_post', -1, 3);
add_action('vidoop_captcha_page_head', 'vidoop_captcha_page_head');
add_action('update_option_vidoop_captcha', 'vidoop_captcha_update_option', 10, 2);



/**
 * Get the VidoopSecure singleton instance.
 *
 * @return Auth_Vidoop_Secure object
 */
function vidoop_captcha_vs() {
	static $service;

	if (!$service) {
		extract(get_option('vidoop_captcha'));
		$api_base = (defined('VIDOOP_SECURE_API_BASE') ? VIDOOP_SECURE_API_BASE : null);

		$service = new Auth_Vidoop_Secure($customer, null, $api_base);
		$service->site($site, $username, $password);
	}

	return $service;
}


/**
 * Get the captcha service singleton instance.
 *
 * @return Auth_Vidoop_Secure_CaptchaService object
 */
function vidoop_captcha_service() {
	static $service;

	if (!$service) {
		extract(get_option('vidoop_captcha'));
		$api_base = (defined('VIDOOP_SECURE_API_BASE') ? VIDOOP_SECURE_API_BASE : null);

		$vs = vidoop_captcha_vs();
		$service = $vs->get_service('captcha');
	}

	return $service;
}


/**
 * Check if the captcha code is valid.
 *
 * @param string $id captcha id
 * @param string $code captcha code
 * @return boolean true if the code is valid for the captcha
 */
function vidoop_captcha_verify($id=null, $code=null) {
	$service = vidoop_captcha_service();

	if (!$id) {
		$id = $_POST['vidoop_captcha_id'];
		$code = $_POST['vidoop_captcha_code'];
	}

	$verified = $service->verify_code($id, $code);
	$verified = apply_filters('vidoop_captcha_verify', $verified, $id, $code);
	return $verified;
}


/**
 * Include necessary javascript and css styles for Captcha page.
 */
function vidoop_captcha_page_head() {
	$stylesheet = plugins_url('vidoopcaptcha/style.css');
	echo '
		<link rel="stylesheet" type="text/css" href="' . $stylesheet . '" />';
	wp_print_scripts('jquery');
}


/**
 * Display captcha page which prompts user to solve the captcha.
 *
 * @param string $error error message to display to the user
 */
function vidoop_captcha_page($error) {
	$service = vidoop_captcha_service();
	$captcha = $service->new_captcha(array('height' => 4, 'width' => 3, 'captcha_length' => 3, 'order_matters' => true));

	$content = '';

	$content .= '<h1>Security Check</h1>';

	$content .= '<form ction="?action='.$_REQUEST['action'].'" method="POST">';

	if (!$captcha || empty($captcha->categories)) {
		$content .= '<p class="error" style="margin-bottom: 2em;">Unable to load security check.  Please wait a minute or two and try again.</p>';
		$content .= '<p><a id="reload" href="#">Retry</a></p>';
		$content .= '<input type="submit" id="submit" name="submit" value="Submit" style="display: none;" />';
	} else {

		if (!empty($error)) {
			$content .= '<p class="error">'.$error.'</p>';
		}

		$content .= '
		<div id="vidoop_captcha">

			<div style="float: right; margin-left: 1em;">
				<img src="'.$captcha->image.'" height="450px" width="450px" />
				<p>Having trouble? <a href="#" id="reload">Try a different set of images.</a></p>
			</div>
			
			<div style="width: 230px;">
				<p>To continue account registration, <strong>type the letters</strong>, in order,  that correspond with the following categories:</p>
				<ul id="categories">';

		foreach ($captcha->categories as $c) {
			$content .= '<li><strong>' . $c . '</strong></li>';
		}

		$content .= '
				</ul>

				<input type="hidden" name="vidoop_captcha_id" id="captcha_id" value="'.$captcha->id.'" />
				<input type="text" name="vidoop_captcha_code" id="code" maxlength="'.sizeof($captcha->categories).'" />

				<p class="submit"><a href="?action='.$_REQUEST['action'].'">Cancel and go back.</a> <input type="submit" id="submit" name="submit" value="Submit" /></p>
			</div>
		</div>
		
		<div style="clear: both;"></div>
		<p id="vidoop_secured"><a href="http://vidoop.com/" target="_blank">Secured by Vidoop</a></p>';
		$content .= wp_nonce_field('vidoop_captcha', '_wpnonce', true, false);

	}
			
	// add original POST as hidden inputs
	foreach ($_POST as $name => $value) {
		if (in_array($name, array('vidoop_captcha_id', 'vidoop_captcha_code'))) {
			continue;
		}

		$content .= '
		<input type="hidden" name="'.$name.'" value="'.$value.'" />';
	}

	$content .= '
	</form>';

	// give input field focus
	$content .= '
	<script type="text/javascript">
	var click_warning = false;
	jQuery(function() {
		jQuery("#code").focus();
		jQuery("#reload").click(function() {
			jQuery("#captcha_id").val("");
			jQuery("#submit").click();
			return false;
		});
		jQuery("#vidoop_captcha img").click(function() {
			jQuery("#code").focus();
			if (!click_warning) {
				click_warning = true;
				jQuery("#code").before("<p class=\"error\">You must <strong>type</strong> the letters below.</p>");
			}
		});
	});
	</script>';

	vidoop_captcha_display_page($content, 'VidoopCAPTCHA');
}


/**
 * Display a standard page, formatted to match WordPress's style.  This is 
 * effectively the same thing as wp_die(), but doesn't return a 500 HTTP 
 * status.
 *
 * @param string $message message to display on the page.  HTML is allowed.
 * @param string $title   title of the page
 * @see wp_die()
 */
function vidoop_captcha_display_page($message, $title = '') {
	global $wp_locale;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php if ( function_exists( 'language_attributes' ) ) language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $title ?></title>
<?php
	wp_admin_css('install', true);
	if ( ($wp_locale) && ('rtl' == $wp_locale->text_direction) ) {
		wp_admin_css('login-rtl', true);
	}

	do_action('admin_head');
	do_action('vidoop_captcha_page_head');
?>
</head>
<body id="vidoop_captcha_page">
	<?php echo $message; ?>
</body>
</html>
<?php
	die();
}


// ---------- Plugin Administration ---------- //


/**
 * Add WordPress admin menus for managing VidoopCAPTCHA.
 */
function vidoop_captcha_admin_menu() {
	$hookname = add_options_page('VidoopCAPTCHA', 'VidoopCAPTCHA', 8, 'vidoopcaptcha', 'vidoop_captcha_options_page');

	register_setting('vidoop_captcha', 'vidoop_captcha');
}


/**
 * When the VidoopSecure API settings are updated, check to see that the new 
 * values are valid.
 */
function vidoop_captcha_update_option($old_value, $new_value) {
	try {
		$service = vidoop_captcha_vs();
		$services = $service->get_services();
	} catch (Exception $e) {
		update_option('vidoop_captcha_valid', false);
	}

	if ($services) {
		update_option('vidoop_captcha_valid', true);
	} else {
		update_option('vidoop_captcha_valid', false);
	}
}


/**
 * WordPress options page for VidoopCAPTCHA
 */
function vidoop_captcha_options_page() {

	$settings = get_option('vidoop_captcha');

	$vidoop_errors = array();
	if ( version_compare('5.0', phpversion(), '>') ) {
		$vidoop_errors[] = 'VidoopCAPTCHA requires PHP 5 or higher.';
	}
	if ( !function_exists('simplexml_load_string') ) {
		$vidoop_errors[] = 'VidoopCAPTCHA requires the <a href="http://www.php.net/simplexml">SimpleXML PHP extension</a>.';
	}
	if ( !function_exists('curl_init') ) {
		$vidoop_errors[] = 'VidoopCAPTCHA requires the <a href="http://www.php.net/curl">cURL PHP extension</a>.';
	}

	screen_icon('vidoopcaptcha');
?>

	<style type="text/css"> #icon-vidoopcaptcha { background-image: url("<?php echo plugins_url('vidoopcaptcha/images/icon.png'); ?>"); } </style>

<?php

	if (!empty($vidoop_errors)) {
		echo '<div class="error">';
		foreach ($vidoop_errors as $error) {
			echo '<p><strong>' . $error . '</strong></p>';
		}
		echo '</div>';
	}

?>
	<div class="wrap">
		<h2>VidoopCAPTCHA</h2>
		<form method="post" action="options.php">

		<h3>VidoopSecure API</h3>


		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><?php _e('Customer ID') ?></th>
					<td>
						<input type="text" name="vidoop_captcha[customer]" id="vidoop_captcha_customer" value="<?php echo @$settings['customer'] ?>" size="50" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Site ID') ?></th>
					<td>
						<input type="text" name="vidoop_captcha[site]" id="vidoop_captcha_site" value="<?php echo @$settings['site'] ?>" size="50" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('API Username') ?></th>
					<td>
						<input type="text" name="vidoop_captcha[username]" id="vidoop_captcha_username" value="<?php echo @$settings['username'] ?>" size="50" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('API Password') ?></th>
					<td>
						<input type="text" name="vidoop_captcha[password]" id="vidoop_captcha_password" value="<?php echo @$settings['password'] ?>" size="50" />
					</td>
				</tr>
			</tbody>
		</table>

		<?php settings_fields('vidoop_captcha'); ?>
		<p class="submit"><input type="submit" name="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>

		</form>
	</div>

<?php
	if ( @empty($settings['customer']) || @empty($settings['site']) || @empty($settings['username']) || @empty($settings['password']) ) {
		echo '
		<div class="updated"><p><strong>To use VidoopCAPTCHA, you must first sign up for an account at 
		<a href="http://login.vidoop.com/" target="_blank">VidoopSecure</a>.  Once you have done so, 
		enter your API account information below.</strong></p></div>';
	} else {
		if (get_option('vidoop_captcha_valid')) {
			echo '<div class="updated" style="border-color: #008800; background-color: #A1FFA1;"><p><strong>API Information is Valid.</strong></p></div>';
		} else {
			echo '<div class="error"><p><strong>API Information is not valid.  Please double check your 
			account settings at <a href="http://login.vidoop.com/" target="_blank">VidoopSecure</a>.
			</strong></p></div>';
		}
	}

}


// ---------- New Account Registration ---------- //


/**
 * Add necessary javascript to the WordPress login form.
 */
function vidoop_captcha_login_head() {
	global $action;
	if ($action == 'register') {
		wp_register_script('vidoopcaptcha', plugins_url('vidoopcaptcha/captcha.min.js'), array('jquery'));
		wp_print_scripts('vidoopcaptcha');
	}
}


/**
 * Add text to the registration form informing the user that they will be required to complete a 
 * captcha before account registration is complete.
 */
function vidoop_captcha_register_form() {
	$service = vidoop_captcha_service();
	$captcha = $service->new_captcha(array('height' => 3, 'width' => 4, 'captcha_length' => 3, 'order_matters' => true));

	$instructions = 'Type in the letters next to <strong>' . $captcha->categories[0] . '</strong>, '
		. '<strong>' . $captcha->categories[1] . '</strong>, and <strong>' . $captcha->categories[2] . '</strong>, in order.';

	echo '
	<style type="text/css">
		.vs_bubble p { width: auto; margin-right: 4em; }
		.vs_bubble img { margin-top: 0.7em; }
		.vidoop_secure .vs_logo { background: url("' . plugins_url('vidoopcaptcha/images/vidoop_captcha_logo_small.gif') . '") center center no-repeat; }
	</style>

	<script type="text/javascript">
	var vidoop_secure = {
		    instructions: "' . $instructions . '"
	};
	jQuery(function() {
        jQuery("#registerform").submit(function() {
            if (jQuery("#user_pass").val() == "Click here to fill me in" || jQuery("#user_pass").val() == "") {
                jQuery("#user_pass").focus();
                return false;
            }
        });
    });
	</script>

	<div class="vidoop_secure" style="clear: left; margin-top: 1em; ">
		<label for="user_pass">Verification code<br />
			<input type="text" class="input" name="vidoop_captcha_code" tabindex="30" id="user_pass" maxlength="'.sizeof($captcha->categories).'" />
		</label>
		<input type="hidden" name="vidoop_captcha_id" id="captcha_id" value="'.$captcha->id.'" />
	</div>';
}


/**
 * Process account registration request.
 *
 * @param string $username username requested for new account
 * @param string $email email address used to register new account
 * @param Array  $errors any errors which have occurred during the registration process
 */
function vidoop_captcha_register_post($username, $email, $errors) {
	if (function_exists('openid_clean_registration_errors')) {
		$errors = openid_clean_registration_errors($errors);
	}

	if ($errors->get_error_code()) return;

	if (!@empty($_POST['vidoop_captcha_id'])) {
		$verified = vidoop_captcha_verify($_POST['vidoop_captcha_id'], $_POST['vidoop_captcha_code']);

		if ($verified) {
			return $username;
		} else {
			vidoop_captcha_page('The letters that you typed were incorrect.  Please try again.');
		}
	}

	vidoop_captcha_page('');
}


// ---------- Comments ---------- //


/**
 * Process a submitted comment to ensure the captcha has been answered 
 * correctly.
 */
function vidoop_captcha_process_comment($comment) {
	if (!@empty($_POST['vidoop_captcha_id'])) {
		$verified = vidoop_captcha_verify($_POST['vidoop_captcha_id'], 
			$_POST['vidoop_captcha_code']);

		if ($verified) {
			return $comment;
		} else {
			vidoop_captcha_page('The letters that you typed were incorrect.  Please try again.');
		}
	}

	vidoop_captcha_page('');
}


/**
 * Add text to the comment form informing the user that they will be required 
 * to complete a captcha before their comment is accepted.
 */
function vidoop_captcha_comment_form() {
	echo '<p>You will be required to complete a security check before your 
		comment is submitted.</p>';
}


?>
