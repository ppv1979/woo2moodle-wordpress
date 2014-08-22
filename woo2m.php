<?php
/*
Plugin Name: woo2moodle
Plugin URI: https://github.com/ppv1979/woo2moodle-wordpress
Description: A plugin that sends the authenticated users details to a moodle site for authentication, enrols them in the specified cohort
Requires: Moodle 2.6 site with the woo2moodle (Moodle) auth plugin enabled
Version: 0.2
Author: Pavel Pisklakov
Based on: Tim St.Clair's wp2moodle plugin (https://github.com/frumbert/wp2moodle--wordpress-)
License: GPL2
*/

/*  Copyright 2014

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?><?php

// some definition we will use
define( 'WOO2M_PUGIN_NAME', 'WooCommerce 2 Moodle (SSO)');
define( 'WOO2M_PLUGIN_DIRECTORY', 'woo2moodle');
define( 'WOO2M_CURRENT_VERSION', '0.2' );
define( 'WOO2M_CURRENT_BUILD', '1' );
define( 'EMU2_I18N_DOMAIN', 'woo2m' );
define( 'WOO2M_MOODLE_PLUGIN_URL', '/auth/woo2moodle/login.php?data=');

function woo2m_set_lang_file() {
	$currentLocale = get_locale();
	if(!empty($currentLocale)) {
		$moFile = dirname(__FILE__) . "/lang/" . $currentLocale . ".mo";
		if (@file_exists($moFile) && is_readable($moFile)) {
			load_textdomain(EMU2_I18N_DOMAIN, $moFile);
		}

	}
}
woo2m_set_lang_file();

//shortcodes - register the shortcode this plugin uses and the handler to insert it
add_shortcode('woo2moodle', 'woo2moodle_handler');

// actions - register the plugin itself, it's settings pages and its wordpress hooks
add_action( 'admin_menu', 'woo2m_create_menu' );
add_action( 'admin_init', 'woo2m_register_settings' );
register_activation_hook(__FILE__, 'woo2m_activate');
register_deactivation_hook(__FILE__, 'woo2m_deactivate');
register_uninstall_hook(__FILE__, 'woo2m_uninstall');

// on page load, init the handlers for the editor to insert the shortcodes (javascript)
add_action('init', 'woo2m_add_button');

/**
 * activating the default values
*/
function woo2m_activate() {
	add_option('woo2m_moodle_url', 'http://localhost/moodle');
	add_option('woo2m_shared_secret', 'enter a random sequence of letters, numbers and symbols here');
	add_option('woo2m_update_details', 'true');
}

/**
 * deactivating requires deleting any options set
 */
function woo2m_deactivate() {
	delete_option('woo2m_moodle_url');
	delete_option('woo2m_shared_secret');
	delete_option( 'woo2m_update_details' );
}

/**
 * uninstall routine
 */
function woo2m_uninstall() {
	delete_option('woo2m_moodle_url');
	delete_option('woo2m_shared_secret');
	delete_option( 'woo2m_update_details' );
}

/**
 * Creates a sub menu in the settings menu for the Link2Moodle settings
 */
function woo2m_create_menu() {
	add_menu_page( 
		__('woo2Moodle', EMU2_I18N_DOMAIN),
		__('woo2Moodle', EMU2_I18N_DOMAIN),
		'administrator',
		WOO2M_PLUGIN_DIRECTORY.'/woo2m_settings_page.php',
		'',
		plugins_url('woo2moodle/icon.png', WOO2M_PLUGIN_DIRECTORY) //__FILE__));
	);
}

/**
 * Registers the settings that this plugin will read and write
 */
function woo2m_register_settings() {
	//register settings against a grouping (how wp-admin/options.php works)
	register_setting( 'woo2m-settings-group', 'woo2m_moodle_url' );
	register_setting( 'woo2m-settings-group', 'woo2m_shared_secret' );
	register_setting( 'woo2m-settings-group', 'woo2m_update_details' );
}

/**
 * Given a string and key, return the encrypted version (hard coded to use rijndael because it's tough)
 */
function encrypt_string($value, $key) { 
	if (!$value) {return "";}
	$text = $value;
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	$crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key.$key), $text, MCRYPT_MODE_ECB, $iv);

	// encode data so that $_GET won't urldecode it and mess up some characters
	$data = base64_encode($crypttext);
    $data = str_replace(array('+','/','='),array('-','_',''),$data);
    return trim($data);
}


/**
 * handler for the plugins shortcode (e.g. [woo2moodle cohort='abc123']my link text[/woo2moodle])
 * note: applies do_shortcode() to content to allow other plugins to be handled on links
 * when unauthenticated just returns the inner content (e.g. my link text) without a link
 * This shortcode can be used only in goods in WooCommerce for a particular customer order to generate link
 * to Moodle for customer, not the current user.
 */
function woo2moodle_handler( $atts, $content = null ) {
	
	// clone attribs over any default values, builds variables out of them so we can use them below
	// $class => css class to put on link we build
	// $cohort => text id of the moodle cohort in which to enrol this user
	// $group => text id of the moodle group in which to enrol this user
	extract(shortcode_atts(array(
		"cohort" => '',
		"group" => '',
		"class" => 'woo2moodle',
		"target" => '_self'
	), $atts));
	
	if ($content == null || !is_user_logged_in() ) {
		// return just the content when the user is unauthenticated or the tag wasn't set properly
		$url = do_shortcode($content);
	} else {
		// url = moodle_url + "?data=" + <encrypted-value>
		$url = '<a target="'.esc_attr($target).'" class="'.esc_attr($class).'" href="'.woo2moodle_generate_hyperlink($cohort,$group).'">'.do_shortcode($content).'</a>';
	}		
	return $url;
}

/*
 * Function to build the encrypted hyperlink
 */
function woo2moodle_generate_hyperlink($cohort,$group) {

	// needs authentication; ensure userinfo globals are populated
	global $current_user;
    
    // get customer info
    global $woocommerce, $post;
    
    if(isset($_GET['order'])){
	    $order = new WC_Order( $_GET['order'] );
	    $customer = get_user_by('id', $order->customer_user);    	
    }else if (isset($_GET['order_id'])) {
    	$order = new WC_Order( $_GET['order_id'] );
	    $customer = get_user_by('id', $order->customer_user);    	
    }else if (!empty($post->ID)) {
	    $order = new WC_Order( $post->ID );
	    $customer = get_user_by('id', $order->customer_user);
	    if(empty($customer)){
	    	get_currentuserinfo();
		    $customer = get_user_by('id', $current_user->ID);	    
	    }
    } else {
    	get_currentuserinfo();
	    $customer = get_user_by('id', $current_user->ID);
	}

    $auth_type = get_user_meta($customer->ID, 'wpDirAuthFlag', true) ? 'ldap' : 'woo';
    $customer_country = get_user_meta($customer->ID, 'billing_country', true);
    $customer_city = get_user_meta($customer->ID, 'billing_city', true);

	$update = get_option('woo2m_update_details') ?: "true";

	$enc = array(
		"offset" => rand(1234, 5678),					// set first to randomise the encryption when this string is encoded
		"stamp" => time(),								// unix timestamp so we can check that the link isn't expired
		"firstname" => $customer->user_firstname,		// first name
		"lastname" => $customer->user_lastname,			// last name
		"country" => $customer_country,					// country code
		"city" => $customer_city,						// city
		"email" => $customer->user_email,				// email
		"username" => $customer->user_login,			// username
		"passwordhash" => $customer->user_pass,			// hash of password (we don't know/care about the raw password)
		"idnumber" => $customer->ID,					// int id of user in this db (for user matching on services, etc)
		"cohort" => $cohort,							// string containing cohort to enrol this user into
		"group" => $group,								// string containing group to enrol this user into
		"auth" => $auth_type,							// where user come from - ldap or wordpress
		"updatable" => $update							// if user profile fields can be updated in moodle
	);
	
	// encode array as querystring
	$details = http_build_query($enc);
	
	// encryption = 3des using shared_secret
	return get_option('woo2m_moodle_url').WOO2M_MOODLE_PLUGIN_URL.encrypt_string($details, get_option('woo2m_shared_secret'));
//	return get_option('woo2m_moodle_url').WOO2M_MOODLE_PLUGIN_URL.$details;

}

/**
 * initialiser for registering scripts to the rich editor
 */
function woo2m_add_button() {
	if ( current_user_can('edit_posts') &&  current_user_can('edit_pages') ) {
	    add_filter('mce_external_plugins', 'woo2m_add_plugin');
	    add_filter('mce_buttons', 'woo2m_register_button');
	}
}
function woo2m_register_button($buttons) {
   array_push($buttons,"|","woo2m"); // pipe = break on toolbar
   return $buttons;
}
function woo2m_add_plugin($plugin_array) {
	// __FILE__ breaks if woo2moodle is a symlink, so we have to use the defined directory
   $plugin_array['woo2m'] = plugins_url( 'woo2moodle/woo2m.js', WOO2M_PLUGIN_DIRECTORY); // __FILE__ );
   return $plugin_array;
}

?>
