=== VidoopCAPTCHA ===
Tags: captcha, vidoop
Requires at least: 2.7
Tested up to: 2.7.1
Stable tag: 0.9.1

Adds a CAPTCHA to account registration that uses Vidoop's ImageShield.

== Description ==

[VidoopCAPTCHA][] is a free web service that determines the difference between
humans creating a new account on your web site and a computer program aiming to
use the account for spamming purposes.  Compared to conventional text-based
CAPTCHA, VidoopCAPTCHA is easier on the user by employing images rather than
distorted words to verify if the user is human, and delivers the web site a
marketing advantage through superior usability and branded image placements.

This plugin requires PHP5, compiled with SimpleXML and libcurl.

[VidoopCAPTCHA]: http://vidoop.com/captcha


== Installation ==

1. Upload the `vidoopcaptcha` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Sign up for a [VidoopSecure][] account
1. Configure the plugin through the 'VidoopCAPTCHA' section of the 'Options' menu

[VidoopSecure]: http://login.vidoop.com/

== Changelog ==

= version 0.9.1 =
 - update to latest version of VidoopSecure library
 - ensure new minimum security requirements for CAPTCHAs are met
 - check for required PHP version and extensions

= version 0.9 =
 - update to latest version of VidoopSecure library
 - add fly-out captcha form on registration page

= version 0.8.1 =
 - update to latest version of VidoopSecure library. (slight modification to API)
 - don't use captcha for comments... still needs a little work
 - lots of code reorganization

= version 0.8 =
 - initial public release
