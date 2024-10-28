<?php

set_include_path(get_include_path() . PATH_SEPARATOR . 'phpseclib');
include_once('Net/SSH2.php');

global $wpdb;
global $current_user;

/**
@package Acclaim-Cloud-Platform
*/

/*
Plugin Name: Acclaim Cloud Platform
Plugin URI:  https://acclaimconsulting.com/?page_id=873
Description: Acclaim Cloud Platform Plugin
Author:      Acclaim Consulting Group
Author URI:  https://acclaimconsulting.com
Version:     1.7
Text Domain: acclaim-cloud-platform
Domain Path: /languages
License:     GPLv2 or later

Acclaim Cloud Platform is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Acclaim Cloud Platform. If not, see http://www.gnu.org/licenses/gpl-2.0.html
*/

register_activation_hook( __FILE__, 'acp_db_init' );
register_uninstall_hook( __FILE__, 'acp_db_remove' );

//tables
define('ACP_SERVERS_TABLE', $wpdb->prefix . 'acp_servers');
define('ACP_USERPROFILE_TABLE', $wpdb->prefix . 'acp_userprofile');
define('ACP_ERROR_TABLE', $wpdb->prefix . 'acp_error');
define('ACP_RIDE_TABLE', $wpdb->prefix . 'acp_rideprofile');

//Acclaim Home
define('ACP_ACCLAIM_HOME', "/opt/acclaim");
define('ACP_ERROR_SOURCE', "ACP");
define('ACP_ERROR_INFO', "INFO");
define('ACP_ERROR_WARN', "WARN");
define('ACP_ERROR_ERROR', "ERROR");
define('ACP_ERROR_FATAL', "FATAL");

//server limit for free version
define('ACP_SERVER_LIMIT', 5);
define('ACP_SERVER_LIMIT_ERROR',"You have exceeded the number of guests. Please consider getting the <a href='https://acclaimconsulting.com/?page_id=1081'>Pro Addon Plugin</a> to request a free developer license key, or remove some guests.");
define('ACP_PLUGIN_SETTINGS_ERROR',"<div class='update-nag notice'><p>Acclaim Cloud Platform Plugin setup is incomplete. Please go to Settings -> ACP Settings and Save/Update ACP Settings</p></div>");

//Options Menu - this needs to be set first before proceeding
$acp_settings_fields = [];
$acp_settings_fields[] = 'Acclaim Home';   			//0
$acp_settings_fields[] = 'Guest Network';			//1
$acp_settings_fields[] = 'VPN DNS';				//2
$acp_settings_fields[] = 'VPN Port';                  		//3
$acp_settings_fields[] = 'Primary Virtualization Host';		//4
$acp_settings_fields[] = 'Virtualization Account';		//5
$acp_settings_fields[] = 'Virtualization Account Password';	//6
$acp_settings_fields[] = 'Mysql Admin';				//7
$acp_settings_fields[] = 'Mysql Admin Password';		//8
$acp_settings_fields[] = 'Signup Shortcode ID';			//9
$acp_settings_fields[] = 'Update Server Shortcode ID';		//10
$acp_settings_fields[] = 'User Profile Table';			//11
$acp_settings_fields[] = 'Servers Table';			//12
$acp_settings_fields[] = 'Error Table';				//13
$acp_settings_fields[] = 'Cluster ID';                          //14
$acp_settings_fields[] = 'Ride Signup Shortcode ID';            //15
$acp_settings_fields[] = 'Ride Table';                          //16

function acp_add_admin_menu() {

    add_options_page( 'Acclaim Cloud Platform Settings', 'ACP Settings', 'manage_options', 'acp_options', 'acp_options_page' );

};
add_action( 'admin_menu', 'acp_add_admin_menu' );

function acp_settings_init() {

        global $acp_settings_fields;
	global $wpdb;

        register_setting( 'acp_pluginPage', 'acp_settings' );

        add_settings_section("acp_pluginPage_section",__( "Acclaim Cloud Platform Plugin Settings Page", "wordpress" ),"acp_settings_section_callback",'acp_pluginPage');

        foreach ($acp_settings_fields as $id => $item) {
                add_settings_field($item,__( $item, "wordpress" ),'acp_render_field',"acp_pluginPage","acp_pluginPage_section",array('field' => $item));
        }
}
add_action('admin_init', 'acp_settings_init');

//send notice if settings have not been made after activating plugin
//https://premium.wpmudev.org/blog/adding-admin-notices/
function acp_plugin_settings_warn() {

      	if (get_option('acp_settings')['Acclaim Home'] != ACP_ACCLAIM_HOME){

        	echo ACP_PLUGIN_SETTINGS_ERROR;
    	}
}
add_action( 'admin_notices', 'acp_plugin_settings_warn' );

//default values
function acp_render_field($args) {

        $options = get_option('acp_settings');
        global $acp_settings_fields;

        if (isset($options[$args['field']]))
        {
                $val=$options[$args['field']];
        } else {
		//defaults
		if ($args['field'] == $acp_settings_fields[1]){

			$val="192.168.8.0";

		} else if ($args['field'] == $acp_settings_fields[2]){

                        $val="acp.acclaimconsulting.com";

                } else if ($args['field'] == $acp_settings_fields[3]){

			$val="443";

		} else if ($args['field'] == $acp_settings_fields[4]) { 

                        $val="io";

             	} else if ($args['field'] == $acp_settings_fields[5] || $args['field'] == $acp_settings_fields[7]){

                        $val="acclaim";

                } else if ($args['field'] == $acp_settings_fields[6] || $args['field'] == $acp_settings_fields[8]){

                        $val="changeme";

		} else if ($args['field'] == $acp_settings_fields[9]){

                        $val="CF5dbf79fccb876";

  		} else if ($args['field'] == $acp_settings_fields[10]){

                        $val="CF5dc47a900a59f";

                } else if ($args['field'] == $acp_settings_fields[14]){

			$val=acp_generate_server_guidv4();

                } else if ($args['field'] == $acp_settings_fields[15]){

                        $val="CF61069cb29df9d";

                } else {

	                $val="";
		}
        }

        if (strpos($args['field'], 'Password') && !strpos($args['field'], 'ID')) {   // for masking passsword fields
                echo '<input type=password name="acp_settings['.$args['field'].']" value='.$val.'>';
        } else if ($args['field'] == 'User Profile Table'){
                echo '<input type=text name="acp_settings['.$args['field'].']" value=' . DB_NAME . "." . ACP_USERPROFILE_TABLE . ' readonly>';
        } else if ($args['field'] == 'Servers Table'){
                echo '<input type=text name="acp_settings['.$args['field'].']" value=' . DB_NAME . "." . ACP_SERVERS_TABLE . ' readonly>';
        } else if ($args['field'] == 'Error Table'){
                echo '<input type=text name="acp_settings['.$args['field'].']" value=' . DB_NAME . "." . ACP_ERROR_TABLE . ' readonly>';
        } else if ($args['field'] == 'Ride Table'){
                echo '<input type=text name="acp_settings['.$args['field'].']" value=' . DB_NAME . "." . ACP_RIDE_TABLE . ' readonly>';
	} else if ($args['field'] == 'Acclaim Home'){
                echo '<input type=text name="acp_settings['.$args['field'].']" value=' . ACP_ACCLAIM_HOME . ' readonly>';
        } else {
                echo '<input type=text name="acp_settings['.$args['field'].']" value='.$val.'>';
        }
};

function acp_settings_section_callback($posted_options) {

    	echo gettext('Host may be an IP or a resolvable DNS name (resolvable on the server with Wordpress). <p> All service accounts must have sudo rights, and should be treated as privileged (root) accounts subject to strict password handling rules per your organizational security policies');

	//make sure we are admin and the admin saved the ACP Settings and none of settings are not null
	$acp_settings_section_name = "acp_pluginPage_section";  //must match value in add_settings_section() call
	$acp_posted_options_string = implode("|",$posted_options);
	$acp_options_page_indicator =  $_GET['page'];

	if ($acp_options_page_indicator == 'acp_options' && strpos($acp_posted_options_string, $acp_settings_section_name) !== false) {

		if (is_admin() && ACP_GUEST_NETWORK && ACP_VPN_HOST && ACP_VIRTUALIZATION_HOST_PRIMARY && ACP_VIRTUALIZATION_ACCOUNT && ACP_VIRTUALIZATION_ACCOUNT_PASSWORD && ACP_SIGNUP_SHORTCODE_ID && ACP_RIDESIGNUP_SHORTCODE_ID && ACP_UPDATE_SERVER_SHORTCODE_ID) {

	        	//file_put_contents('logs.txt', "session: ".PHP_EOL , FILE_APPEND | LOCK_EX);

        		$acp_settings = get_option('acp_settings');
        		$arr_acp_settings=array();

	       	 	$i=0;
        		foreach($acp_settings as $key=>$val) {

                		$arr_acp_settings[$i] = "$key : $val";
                		$i++;
        		}

	        	//TODO add error handling**
        		$str_acp_settings=implode("|",$arr_acp_settings);
        		acp_write_settings($str_acp_settings);

		} else {
			echo "Please fill in all Settings values and resubmit";
		}
	}
        return $posted_options;
}

function acp_options_page() {

    ?>
        <form action='options.php' method='post'>

                <?php
                settings_fields( 'acp_pluginPage' );
                do_settings_sections( 'acp_pluginPage' );
                submit_button();
                ?>
        </form>
        <?php
}

//end Options Menu

//other constants
define('ACP_VPN_HOST', get_option('acp_settings')['VPN DNS']);
define('ACP_GUEST_NETWORK', get_option('acp_settings')['Guest Network']);

define('ACP_VIRTUALIZATION_HOST_PRIMARY', get_option('acp_settings')['Primary Virtualization Host']);
define('ACP_VIRTUALIZATION_HOST', gethostname());
define('ACP_VIRTUALIZATION_ACCOUNT', get_option('acp_settings')['Virtualization Account']);
define('ACP_VIRTUALIZATION_ACCOUNT_PASSWORD', get_option('acp_settings')['Virtualization Account Password']);

define('ACP_SIGNUP_SHORTCODE_ID', get_option('acp_settings')['Signup Shortcode ID']);
define('ACP_RIDESIGNUP_SHORTCODE_ID', get_option('acp_settings')['Ride Signup Shortcode ID']);
define('ACP_UPDATE_SERVER_SHORTCODE_ID', get_option('acp_settings')['Update Server Shortcode ID']);

define('ACP_SIGNUP_PAGE_ID', get_page_by_title('ACP Sign Up', OBJECT, 'page')->ID);
define('ACP_ERROR_PAGE_ID', get_page_by_title('ACP Error', OBJECT, 'page')->ID);
define('ACP_SET_PASSWORD_PAGE_ID', get_page_by_title('ACP Password', OBJECT, 'page')->ID);
define('ACP_DASHBOARD_PAGE_ID', get_page_by_title('Acclaim Cloud Platform Dashboard', OBJECT, 'page')->ID);
define('ACP_LOGIN_PAGE_ID', get_page_by_title('ACP Login', OBJECT, 'page')->ID);
define('ACP_DOCUMENTATION_PAGE_ID', get_page_by_title('ACP Documentation', OBJECT, 'page')->ID);


//write out settings in on virtualization server
function acp_write_settings($str_acp_settings) {

        global $wpdb;
        $run_script = "sudo " . ACP_ACCLAIM_HOME . "/bin/settings Settings '$str_acp_settings' ACP &> /dev/null &";
        $line_number = __LINE__;
        acp_connect_server(ACP_VIRTUALIZATION_HOST_PRIMARY,ACP_VIRTUALIZATION_ACCOUNT,ACP_VIRTUALIZATION_ACCOUNT_PASSWORD,$run_script,$line_number,$line_number);
	//echo $results;
}

//create table when plugin is activated
function acp_db_init() {

	global $wpdb;

	global $acp_db_version;
	$acp_db_version = '1.7';
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php' );

	$sql = file_get_contents( plugin_dir_path(__FILE__) . "/wp_acp_servers.sql" );
	dbDelta($sql);

        $sql = file_get_contents( plugin_dir_path(__FILE__) . "/wp_acp_userprofile.sql" );
        dbDelta($sql);

        $sql = file_get_contents( plugin_dir_path(__FILE__) . "/wp_acp_error.sql" );
        dbDelta($sql);

	add_option('acp_db_version', $acp_db_version);

        //write settings to primary host
        $run_script = "sudo " . ACP_ACCLAIM_HOME . "/bin/settings Init dummy ACP &> /dev/null &";
        $line_number = __LINE__;
        acp_connect_server(ACP_VIRTUALIZATION_HOST_PRIMARY,ACP_VIRTUALIZATION_ACCOUNT,ACP_VIRTUALIZATION_ACCOUNT_PASSWORD,$run_script,$line_number,$line_number);
	//echo "results in activation: $results";
}

// Delete table when deleted
function acp_db_remove() {

	global $wpdb;

     	$sql = "DROP TABLE IF EXISTS " . ACP_SERVERS_TABLE . ";";
     	$wpdb->query($sql);
        $sql = "DROP TABLE IF EXISTS " . ACP_USERPROFILE_TABLE . ";";
        $wpdb->query($sql);
        $sql = "DROP TABLE IF EXISTS " . ACP_ERROR_TABLE . ";";
        $wpdb->query($sql);
       $sql = "DROP TABLE IF EXISTS " . ACP_RIDE_TABLE . ";";
        $wpdb->query($sql);

     	delete_option("acp_db_version");
	delete_option("acp_settings");

	//delete settings
	$run_script = "sudo " . ACP_ACCLAIM_HOME . "/bin/settings Delete dummy ACP &> /dev/null &";
        $line_number = __LINE__;
        $results = acp_connect_server(ACP_VIRTUALIZATION_HOST_PRIMARY,ACP_VIRTUALIZATION_ACCOUNT,ACP_VIRTUALIZATION_ACCOUNT_PASSWORD,$run_script,$line_number,$line_number);
}

//allow redirection, even if plugin starts to send output to the browser
function acp_do_output_buffer() {
        ob_start();
}
add_action('init', 'acp_do_output_buffer');

//register user in Wordpress
function acp_process_register($form_data) {

        global $wpdb;

	$data   = array();

	$user_temp_pass = acp_generate_random_string();
        $user_email = strtolower(sanitize_email($form_data['email_addr']));
	$user_name = $user_email;
	$client_goal  = sanitize_text_field($form_data['goal']);

 	$date_now = date("Y-m-d H:i:s");
        $user_id = username_exists($user_name);

	//validate client goal
        $client_goal_valid_values = array("Development", "Migration", "DIY", "Unsure/Don't Know");
       	$line_number = __LINE__;
        acp_validate_value($user_id,$user_name,$line_number,$client_goal,$client_goal_valid_values,1,"");

        //limit to 32 characters
        $user_name_unix = substr(preg_replace('/[^a-zA-Z0-9]/','',$user_name),0,31);

	if (!$user_id and email_exists($user_email) == false and is_email($user_email)) {

		$user_password = acp_generate_random_string();
		$user_id = wp_create_user($user_name, $user_password, $user_email);

		//create user profile in custom table
		$wpdb->insert(ACP_USERPROFILE_TABLE,array('user_id' => $user_id,'user_name' => $user_name,'user_name_unix' => $user_name_unix,'client_goal' => $client_goal,'user_temp_pass' => $user_temp_pass,'user_last_login' => $date_now),array('%d','%s','%s','%s','%s','%s'));

		//log the user in
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                do_action('wp_login', $user_name);
                //redirect to password set page
		wp_safe_redirect("/?page_id=" . ACP_SET_PASSWORD_PAGE_ID);
                die();

	} else {

		$random_password = 'User already exists.  Password inherited.';
	}
	return;
}
add_filter('acp-register','acp_process_register');

//register ride user in Wordpress
function acp_process_rideregister($form_data) {

        global $wpdb;

        $data   = array();

        $user_temp_pass = acp_generate_random_string();
        $user_email = strtolower(sanitize_email($form_data['email_addr']));
        $user_name = $user_email;
        $user_phone  = sanitize_text_field($form_data['phone_number']);

        $date_now = date("Y-m-d H:i:s");
        $user_id = username_exists($user_name);

        //*TO DO validate user phone
	//https://mburumaxwell.medium.com/phone-number-input-validation-in-kenya-6efd82256ea7
        //$client_goal_valid_values = array("Development", "Migration", "DIY", "Unsure/Don't Know");
        //$line_number = __LINE__;
        //acp_validate_value($user_id,$user_name,$line_number,$client_goal,$client_goal_valid_values,1,"");
        //*TO DO*//

        //limit to 32 characters
        $user_name_unix = substr(preg_replace('/[^a-zA-Z0-9]/','',$user_name),0,31);

        if (!$user_id and email_exists($user_email) == false and is_email($user_email)) {

                $user_password = acp_generate_random_string();
                $user_id = wp_create_user($user_name, $user_password, $user_email);

                //create user profile in custom table
                $wpdb->insert(ACP_RIDE_TABLE,array('user_id' => $user_id,'user_name' => $user_name,'user_name_unix' => $user_name_unix,'user_phone' => $user_phone,'user_temp_pass' => $user_temp_pass,'user_last_login' => $date_now),array('%d','%s','%s','%s','%s','%s'));

                //log the user in
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                do_action('wp_login', $user_name);
                //redirect to password set page
                wp_safe_redirect("/?page_id=" . ACP_SET_PASSWORD_PAGE_ID);
                die();

        } else {

                $random_password = 'User already exists.  Password inherited.';
        }
        return;
}
add_filter('acp-rideregister','acp_process_rideregister');

//set initial user password in Wordpress
function acp_process_password_set($form_data) {

	global $wpdb;

	$current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_name = $current_user->user_login;

	//sanitize
	//wp_set_password below hashes the password, so sanitization is not needed (as it would yield undesirable effects of a weaker password than user intended).

	//user definitely exists if we end up here
	$user_password = $form_data['password'];
	wp_set_password ($user_password,$user_id); //this hashes the password, so sanitization is not needed. 

	//update password attribute to indicate the user has chosen their own password
	$value = 1;
        $wpdb->update(ACP_USERPROFILE_TABLE,array('user_psswd_set'=>$value), array('user_id'=> $user_id)); 
}
add_filter( 'acp-password-set', 'acp_process_password_set' );

//set initial provisioning of VPN
function acp_process_vpn_provision() {

        global $wpdb;
        global $result;

	$current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_name = $current_user->user_login;

        $date_now = date("Y-m-d H:i:s");
        $error_code = acp_generate_guidv4();

        if ($user_id) {
		//user already registered in Wordpress

 		//check if user has generated their VPN files. Returns one result
        	$result = $wpdb->get_row ( "SELECT user_vpn_set,user_name_unix FROM " . ACP_USERPROFILE_TABLE . " WHERE user_id = $user_id" );
		$user_name_unix = $result->user_name_unix;

		if (!$result->user_vpn_set) {

			//provision vpn files - created on VPN server then copied to the user's home directory on VirtualBox Server
			//this script needs wordpress username (email)
			//done on primary host
                        $run_script = "sudo " . ACP_ACCLAIM_HOME . "/bin/vpn $user_name $user_name_unix &> /dev/null &";
                        $line_number = __LINE__;
        		$results = acp_connect_server(ACP_VIRTUALIZATION_HOST_PRIMARY,ACP_VIRTUALIZATION_ACCOUNT,ACP_VIRTUALIZATION_ACCOUNT_PASSWORD,$run_script,$line_number,$line_number);

			//update table to indicate user has generated VPN
        		$value = 1;
        		$wpdb->update(ACP_USERPROFILE_TABLE,array('user_vpn_set' => $value),array('user_id'=>$user_id));
			return ("VPN Set Successfully");
		} else {
			//user has set their VPN
			return ("VPN Check Successful");
		}

	} else {
		return ("User Not Logged in....");
	}
}
add_filter('acp-vpn-provision', 'acp_process_vpn_provision' );

//Create virtualbox account for user
function acp_process_virtualbox_provision($user_name_unix) {

	global $wpdb;
        global $result;

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_name = $current_user->user_login;

	//make sure user was created in Wordpress

	//provision user in virtualbox if the user is already registered on wordpress
	if ($user_id) {

		// open a SSH connection (hostname = IP or network name of the remote computer)
        	$date_now = date("Y-m-d H:i:s");
                $run_script = "sudo " . ACP_ACCLAIM_HOME . "/bin/user $user_name_unix " . ACP_VIRTUALIZATION_HOST_PRIMARY . " &> /dev/null";
		$line_number = __LINE__;

                //file_put_contents('logs.txt', "script: $run_script".PHP_EOL , FILE_APPEND | LOCK_EX);

                $results = acp_connect_server(ACP_VIRTUALIZATION_HOST_PRIMARY,ACP_VIRTUALIZATION_ACCOUNT,ACP_VIRTUALIZATION_ACCOUNT_PASSWORD,$run_script,$line_number,$line_number);

		//update table to indicate user has created svc account
                $value = 1;
        	$wpdb->update(ACP_USERPROFILE_TABLE,array('user_svc_acct_set'=>$value), array('user_id'=> $user_id));
	} else {
		exit("User has not been created in Wordpress");
	}
	return;
}
add_filter('acp-virtualbox-provision', 'acp_process_virtualbox_provision' );

//Create VM Guest and add server details to custom Wordpress DB
function acp_process_server_provision ($form_data) {

        global $wpdb;
	global $result;

	//current user context
	$current_user = wp_get_current_user();
	$user_id = $current_user->ID;
	$user_name = $current_user->user_login;

	//sanitize form fields
        $server_name = sanitize_text_field($form_data['server_name']);
	$server_type = sanitize_text_field($form_data['server_type']);
        $server_cpus = (int)sanitize_text_field($form_data['server_cpus']);
        $server_memory = (int)sanitize_text_field($form_data['server_memory']);
	$server_disksize= sanitize_text_field($form_data['server_disksize']);
        $server_meta= sanitize_text_field($form_data['server_meta']);
	$server_env = sanitize_text_field($form_data['server_env']);

	//validate form fields

	//servername and server_meta validate
        $line_number = __LINE__;
	acp_validate_value($user_id,$user_name,$line_number,$server_name,array('/[^A-Za-z0-9]/'),2,"");  //must contain only letter or numbers
        $line_number = __LINE__;
       	//acp_validate_value($user_id,$user_name,$line_number,$server_meta,array('/[^A-Za-z0-9]/'),2,"");  //must contain only letter or numbers

	//server_type validate
      	$line_number = __LINE__;
        $server_type_valid_values = array("RedHat","Fedora","Centos","Ubuntu","Debian","Windows");
        acp_validate_value($user_id,$user_name,$line_number,$server_type,$server_type_valid_values,1,"");

	//server_cpus validate
       	$line_number = __LINE__;
	acp_validate_value($user_id,$user_name,$line_number,$server_cpus,"",3,array(1,4)); //must be between 1-4

 	//server_memory validate
        $line_number = __LINE__;
        acp_validate_value($user_id,$user_name,$line_number,$server_memory,"",3,array(512,32768)); //must be between 512,32768

	//server_disksize validate
        $server_disksize_valid_values = array("10GB","20GB","50GB","100GB");
        $line_number = __LINE__;
        acp_validate_value($user_id,$user_name,$line_number,$server_disksize,$server_disksize_valid_values,1,"");

	//server_env validate
        $server_env_valid_values = array("Prod","Dev","QA","Sandbox","Other");
        $line_number = __LINE__;
        acp_validate_value($user_id,$user_name,$line_number,$server_env,$server_env_valid_values,1,"");

	$server_status = 'starting';
	$server_id = acp_generate_server_guidv4();
	$user_temp_pass = acp_generate_random_string();
	$rand_pass = acp_generate_random_string();
	$date_now  = date("Y-m-d H:i:s");

	//Limit server name to 32 characters
	$server_name = substr($server_name,0,32);

	//Ips are static and randomy assigned in the network segment 192.168.8.0 (set as plugin option) until they run out
        $addresses = $wpdb->get_results ("SELECT server_ip FROM " . ACP_SERVERS_TABLE);
        $ips = $wpdb->get_row ("SELECT count(server_ip) as total_ips FROM " . ACP_SERVERS_TABLE);
	$result = $wpdb->get_row ("SELECT user_name_unix FROM " . ACP_USERPROFILE_TABLE . " WHERE user_id = $user_id");
	$user_name_unix = $result->user_name_unix;
	$acp_ips = $ips->total_ips;

	//selected server
	$line_number = __LINE__;
        $selected_host=acp_select_acphost($line_number);

	//if select returns a server, it MUST be up. Else the function returns -1 

	if ($selected_host == 'none') {

 		//die. Cannot continue

             	$error_msg="Server Select Error";
                $line_number = __LINE__;
               	acp_raise_error_condition(ACP_ERROR_SOURCE,$selected_host,ACP_ERROR_FATAL,$line_number,$line_number,$error_msg);
               	die();

	} else {

		$selected_host_status='up';
	}

	//make sure IP has not been assigned previously
	$new_ip = rand(2,253); //random ip in {2,253} range
	$server_ip_tokens = explode(".",ACP_GUEST_NETWORK);
	if (sizeof($server_ip_tokens) != 4) {
		$error_msg="Bad Guest Network Configuration";
                $line_number = __LINE__;
		acp_raise_error_condition(ACP_ERROR_SOURCE,$selected_host,ACP_ERROR_FATAL,$line_number,$line_number,$error_msg);
	} else {
		$server_ip = substr(ACP_GUEST_NETWORK,0,strlen(ACP_GUEST_NETWORK)-1) . (string)$new_ip;
	}
	//$add_str=explode("^",$addresses->server_ip);

        if ($acp_ips > 0) {

		//put all existing IPs in an array and make sure the new one is not in that list
		$assigned=array_fill(0,$acp_ips,0);
		$count=0;
		foreach ($addresses as $address) {
                	$server_ip_array = explode(".", $address->server_ip);
                	$server_ip_host = (int)$server_ip_array[sizeof($server_ip_array)-1];
			$assigned[$count] = (int)$server_ip_host;
			$count++;
		}

		$found=FALSE;
		//$new_ip=-1;
		while (!$found) {
			//$new_ip = rand(2,253); //random ip in {2,253} range
			if (!in_array($new_ip,$assigned)) {
				$found=TRUE;
			} else {
				$new_ip = rand(2,253);
			}
		}
        	$server_ip = substr(ACP_GUEST_NETWORK,0,strlen(ACP_GUEST_NETWORK)-1) . (string)$new_ip;
	}

 	//compliance checks
        $error_line=2**13;
       	$acp_error_result = $wpdb->get_row ("SELECT count(error_line) as acp_error FROM " . ACP_ERROR_TABLE . " WHERE error_line > $error_line");
        $acp_error = (int)$acp_error_result->acp_error;

        if ($acp_error > 0){

          	$currurl="/?page_id=" . ACP_DASHBOARD_PAGE_ID . "&acp_action=acp_license";
                //wp_safe_redirect($currurl);
		echo ACP_SERVER_LIMIT_ERROR;
               	die();

	} else if (!$user_id) {

		//die ignominously as we don't know you
		die();

	} else {

		// open a SSH connection (hostname = IP or network name of the remote computer)
                $date_now = date("Y-m-d H:i:s");
		$run_script = "sudo " . ACP_ACCLAIM_HOME . "/bin/server $server_type $server_id $server_ip $server_name $server_memory $server_disksize $server_cpus $user_name $user_name_unix $user_temp_pass $rand_pass " . ACP_VIRTUALIZATION_ACCOUNT . " $acp_ips &> /dev/null &";
		$line_number = __LINE__;
                $acp_results=acp_connect_server($selected_host,ACP_VIRTUALIZATION_ACCOUNT,ACP_VIRTUALIZATION_ACCOUNT_PASSWORD,$run_script,$line_number,$line_number);

                //file_put_contents('logs.txt', "selected server: $selected_host, script: $run_script".PHP_EOL , FILE_APPEND | LOCK_EX);

		//first insert server details into DB

		$wpdb->insert(ACP_SERVERS_TABLE,array('user_id'=>$user_id,'user_name'=>$user_name_unix,'user_temp_pass'=>$user_temp_pass,'server_id'=>$server_id,'server_name'=>$server_name,'server_ip'=>$server_ip,'server_env'=>$server_env,'server_type'=>$server_type,'server_host'=>$selected_host,'server_host_status'=>$selected_host_status,'server_memory'=>$server_memory,'server_disksize'=>$server_disksize,'server_cpus'=>$server_cpus,'server_status'=>$server_status,'server_created'=>$date_now,'server_meta'=>$server_meta),array('%d','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%d','%s','%s','%s'));

		//update user profile machine cache (0 is stale, 1 is up to date)
                $sql = "UPDATE " . ACP_USERPROFILE_TABLE . " SET machine_cache = 0 WHERE user_id = $user_id";
                $wpdb->query($sql);
		return;
	}
	return;
}
add_filter('acp-server-provision', 'acp_process_server_provision' );

//shortcode to populate table with servers data
function acp_pop_table_shortcode () {

	global $current_user;
	$current_user = wp_get_current_user();
	$user_id = $current_user->ID;
        $user_name = $current_user->user_login;

	global $wpdb;

       	$acp_nonce = wp_create_nonce('acp_manage');

	//this is not for wordpress admins
	if (current_user_can('administrator')) {
		return;
	}

        //get URL parameters
        //selected server 
	$selected_server_id = "";
        if(isset($_GET['acp_server_id'])){

                //sanitize
                $selected_server_id = sanitize_key($_GET['acp_server_id']);

  		//validate
                $line_number = __LINE__;
		acp_validate_value($user_id,$user_name,$line_number,$selected_server_id,array('/[^A-Za-z0-9\-]/'),2,"");  //must contain only letter or numbers
		acp_validate_value($user_id,$user_name,$line_number,strlen($selected_server_id),"",3,array(36,36)); //must be 8 characters in length
        }

	$selected_action = "";
        //Last context {action,server_id}. action is either {started,stopped,deleted,details}
        if(isset($_GET['acp_action'])){

                //sanitize
                $selected_action = sanitize_key($_GET['acp_action']);
                $mesg = $selected_action;

                //validate
                $line_number = __LINE__;
                $acp_action_valid_values = array("acp_license","dashboard","login", "logout", "started", "stopped", "deleted", "details", "update", "restored", "in-activated", "Storage shrinking unsupported", "no changes made");
		acp_validate_value($user_id,$user_name,$line_number,$selected_action,$acp_action_valid_values,1,"");
        }

	//check if user has set their password. Returns one result
        $result = $wpdb->get_row ( "SELECT * FROM " . ACP_USERPROFILE_TABLE . " WHERE user_id = $user_id" );
	$user_name_unix = $result->user_name_unix;
	if (!$result->user_psswd_set) {
                wp_safe_redirect("/?page_id=" . ACP_SET_PASSWORD_PAGE_ID);
                die();
	}

	//check is user has created guest account on virtualization server
	if (!$result->user_svc_acct_set) {
		acp_process_virtualbox_provision($user_name_unix);
        }

	$user_set_pass = $result->user_psswd_set; //password is a boolean in DB, so here comes in as 0 or 1

	if ($user_set_pass > 0 ) {
		;
	} else {
		//user has not set password
                wp_safe_redirect(esc_url("/?page_id=" . ACP_SET_PASSWORD_PAGE_ID));
		die();
	}

	//restrict number of instances in Plugin without Pro Addon
   	$result = $wpdb->get_row ("SELECT count(server_id) AS cerebus FROM " . ACP_SERVERS_TABLE);
	$err="";
	if ($result->cerebus > 0 && $selected_action == 'acp_license'){
	   	$err=ACP_SERVER_LIMIT_ERROR;
	}

 	//get updated values and display. Sorted by sticky so deleted one (0) are at bottom to facilitate display below
        $result = $wpdb->get_results ("SELECT * FROM " . ACP_SERVERS_TABLE . " WHERE user_id = $user_id ORDER BY server_sticky DESC, server_created");
        $vpnlink = "<a href=/?acp_f1=vpn&acp_nonce=$acp_nonce> vpn files </a>";

	$ret = "<!-- wp:table -->";
        $ret = $ret . "<h3>Active Servers</h3>";
	$ret = $ret . "<p><font color='red'>$err</font>";
	$ret = $ret . "<table class='wp-block-table'><tbody><tr><td><strong>Server Name / ID</strong></td><td><strong>OS / CPUs / Mem / Disk<strong></td><td><strong>Status / Actions </strong></td><td><strong>Details  </strong> &nbsp $vpnlink</td><td><strong>Host / Status</strong></td></tr>";

	$ret_status = "";
	$found_stickyless = 0;  //tracks border of active vis a vis inactive

	$i = 0; //count

  	foreach($result as $value) {

 		$server_status = $value->server_status;
        	$server_id = $value->server_id;
        	$server_type = $value->server_type;
        	$server_name = $value->server_name;
                $server_host = $value->server_host;
                $server_host_status = $value->server_host_status;
        	$server_ip = $value->server_ip;
                $server_cpus = $value->server_cpus;
                $server_memory = round(((int)$value->server_memory)/1000,1);
		$server_disksize = substr($value->server_disksize,0,strlen($value->server_disksize)-2);
                $server_env = $value->server_env;
		$server_meta = $value->server_meta;
		$user_temp_pass = $value->user_temp_pass;
		$user_name = $value->user_name;
		$server_sticky = $value->server_sticky;

		//if value is 'starting', it means the machine is being spun up
		$start=0;
		$stop=0;
		$destroy=1;  //always
		$refresh=0;
		$link="";

		//do prep work based on server state
                if ($server_status == 'running') {

			$server_status = "<font color='green'>up</font>"; 
			$stop=1;

		} else if ($server_status == 'starting') {

   			$server_status = "<font color='amber'>starting</font>";
 			$destroy=1;

		} else if ($server_status == 'poweroff') {

			$server_status = "<font color='red'>off</font>";
			$start=1;

                } else if ($server_status == 'aborted') {

                        $server_status = "<font color='red'>aborted</font>";
                        $start=1;


  		} else if ($server_status == 'notcreated') {

                        $server_status = "<font color='red'>not created</font>";
                        $start=1;

		} else {

                        //should not reach here ** error
			$error_msg="Server Status Unknown";

 			$line_number = __LINE__;
			acp_raise_error_condition(ACP_ERROR_SOURCE,$server_host,ACP_ERROR_FATAL,$line_number,$line_number,$error_msg);
		}

		//server host status
                if ($server_host_status == 'up') {

                        $server_host_status = "<font color='green'>up</font>";

                } else if ($server_host_status == 'down') {

                        $server_host_status = "<font color='red'>off</font>";

		} else {

		        $error_msg="Server Host Status Unknown";

                        $line_number = __LINE__;
                        acp_raise_error_condition(ACP_ERROR_SOURCE,$server_host,ACP_ERROR_FATAL,$line_number,$line_number,$error_msg);
		}

		//prepare links based on server state
		$inactivemsg = "Deleting an Active Server Will Shut it down and Make it Inactive.  Are You Sure You Want to Proceed?";
                $deletemsg = "Deleting an InActive Server Will Completely Remove it. All Data will be lost. This operation cannot be Undone. Are You Sure You Want to Proceed?";

		if ($start){$link = "<a href=/?acp_f1=sm&acp_f2=start&acp_id=$server_id&acp_nonce=$acp_nonce title='start server'> start </a>";}
                if ($stop){$link = $link . "<a href=/?acp_f1=sm&acp_f2=stop&acp_id=$server_id&acp_nonce=$acp_nonce title='stop server'> stop </a>";}
                if ($destroy && $server_sticky){$link = $link . "<a href=/?acp_f1=sm&acp_f2=destroy&acp_id=$server_id&acp_sticky=$server_sticky&acp_nonce=$acp_nonce onClick=\"javascript: return confirm('$inactivemsg');\" title='delete server'> delete </a>";}
                if ($destroy && !$server_sticky){$link = $link . "<a href=/?acp_f1=sm&acp_f2=destroy&acp_id=$server_id&acp_sticky=$server_sticky&acp_nonce=$acp_nonce onClick=\"javascript: return confirm('$deletemsg');\" title='delete server'> delete </a>";}

		//always add refresh and details links
                $link = $link . "<a href=/?page_id=". ACP_DASHBOARD_PAGE_ID . "> refresh </a>";
		$detailslink = "<a href=/?acp_f1=sm&acp_f2=details&acp_id=$server_id&acp_nonce=$acp_nonce> details </a>";
                $updatelink = "<a href=/?acp_f1=sm&acp_f2=update&acp_id=$server_id&acp_nonce=$acp_nonce> reconfigure </a>";
                $sshlink = "<a href=/?acp_f1=ssh&acp_id=$server_id&acp_nonce=$acp_nonce> ssh key</a>";

		//this undelete
                if (!$server_sticky) {$link = $link . "&nbsp &nbsp <a href=/?acp_f1=sm&acp_f2=restore&acp_id=$server_id&acp_nonce=$acp_nonce title='restore server'> restore </a>";}

		//messaging for server management actions
		if (strcasecmp($selected_server_id,$server_id) == 0){

			$link = "$link  <font color='blue'><i> $mesg </i></font>";

			//if user clicks details link, toggle details
			if ($selected_action == 'details') {
				$detailslink = $detailslink . "<br>{Env: $server_env, <br> IP: $server_ip <br> Admin: $user_name <br> Temp Pass: $user_temp_pass <br> Meta: $server_meta}";
			}

			if ($selected_action == 'update'){

				//make sure server is not running
				if ($stop){    //confusing ... stop means the server is up. see above
					$updatelink = "<p>Please stop server before attempting to update it";
				} else {
                                        $updatelink = do_shortcode("[caldera_form id='" . ACP_UPDATE_SERVER_SHORTCODE_ID . "']");
				}
			}
			//if user clicks update link, show then update form
		}

		//here, we detect border of active vs inactive servers, then we close active table and open inactive one. Must be done once (by setting found_stickless to 1 and making it a condition.
		if (!$server_sticky && !$found_stickyless) {
 			$ret = $ret . "</tbody></table>";
        		$ret = $ret . "<hr>";
        		$ret = $ret . "<h3>Inactive Servers</h3>";
			$ret = $ret . "<table class='wp-block-table'><tbody><tr><td><strong>Server Name / ID</strong></td><td><strong>OS/CPUs/Mem/Disk</strong></td><td><strong>Manage Status</strong></td><td><strong>Details</strong> </td><td><strong>Host</strong></td></tr>";
			$found_stickyless = 1;
		}

		$details = "{Env: $server_env, <br> IP: $server_ip <br> Admin: $user_name <br> Pass: $user_temp_pass <br> Meta: $server_meta}";
                $ret = $ret . "<tr><td>  $server_name  /  $server_id </td><td>  $server_type  / $server_cpus / " . $server_memory . "GB / " . $server_disksize . "GB </td><td> $server_status  $link </td><td> $detailslink &nbsp $updatelink &nbsp $sshlink </td></td><td> $server_host  $server_host_status</td></tr>";
		$ret = $ret;
		$i++;
  	}

        $ret = $ret . "</tbody></table>";
 	$ret = $ret . "<!-- /wp:table -->";
	return $ret;	//I believe this returns escaped, sanitized html
}
add_shortcode('acp-pop-table', 'acp_pop_table_shortcode');

//shortcode to populate signup page
function acp_pop_signup_shortcode () {

        global $current_user;

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_name = $current_user->user_login;

	//check if user is logged in, then don't display signup page
	$ret = "";
	if ( $user_id ) {
		// user is logged in. Take us to dashboard<p>
                $ret = "<p style='text-align:center'><a href='/?page_id=" . ACP_DASHBOARD_PAGE_ID . ">Dashboard</a></p>";
	} else {
		//user not logged in OR not registered. Display Register form and login form
		$ret = do_shortcode("[caldera_form id='" . ACP_SIGNUP_SHORTCODE_ID . "']");
		$ret = $ret . "<p><a href='/?page_id=" . ACP_LOGIN_PAGE_ID . "'>Login</a> if you have an account already.</p>";
	}
	return $ret; //I believe this returns escaped, sanitized html
}
add_shortcode('acp-pop-signup', 'acp_pop_signup_shortcode');

//shortcode to populate signup page - ride share
function acp_pop_ridesignup_shortcode () {

        global $current_user;

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_name = $current_user->user_login;

        //check if user is logged in, then don't display signup page
        $ret = "";
        if ( $user_id ) {
                // user is logged in. Take us to dashboard<p>
                $ret = "<p style='text-align:center'><a href='/?page_id=" . ACP_DASHBOARD_PAGE_ID . ">Dashboard</a></p>";
        } else {
                //user not logged in OR not registered. Display Register form and login form
                $ret = do_shortcode("[caldera_form id='" . ACP_RIDESIGNUP_SHORTCODE_ID . "']");
                //$ret = $ret . "<p><a href='/?page_id=" . ACP_LOGIN_PAGE_ID . "'>Login</a> if you have an account already.</p>";
        }
        return $ret; //I believe this returns escaped, sanitized html
}
add_shortcode('acp-pop-ridesignup', 'acp_pop_ridesignup_shortcode');

//update server data
function acp_process_server_update ($form_data) {

 	global $wpdb;
        global $result;

        //current user
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_name = $current_user->user_login;

	//sanitize
	$server_id = sanitize_text_field($form_data['server_id']);
       	$server_id = (strtoupper($server_id));
        $server_cpus = (int)sanitize_text_field($form_data['server_cpus']);
        $server_memory = (int)sanitize_text_field($form_data['server_memory']);
        $server_disksize= sanitize_text_field($form_data['server_disksize']);
        $server_meta= sanitize_text_field($form_data['server_meta']);

 	 //server meta validate
       	$line_number = __LINE__;
      	acp_validate_value($user_id,$user_name,$line_number,$server_id,array('/[^A-Za-z0-9\-]/'),2,"");  //must contain only letter or numbers
       //acp_validate_value($user_id,$user_name,$line_number,$server_meta,array('/[^A-Za-z0-9]/'),2,"");  //must contain only letter or numbers

	//server_cpus validate
 	$line_number = __LINE__;
        acp_validate_value($user_id,$user_name,$line_number,$server_cpus,"",3,array(1,4)); //must be between 1-4

        //server_memory validate
        acp_validate_value($user_id,$user_name,$line_number,$server_memory,"",3,array(512,32768)); 

        //server_disksize validate
        $server_disksize_valid_values = array("10GB","20GB","50GB","100GB");
        $line_number = __LINE__;
        acp_validate_value($user_id,$user_name,$line_number,$server_disksize,$server_disksize_valid_values,1,"");

        $result = $wpdb->get_row ( "SELECT * FROM " . ACP_USERPROFILE_TABLE . " WHERE user_id = $user_id" );
        $user_name_unix = $result->user_name_unix;

	//get current values
	$result = $wpdb->get_results ( "SELECT * FROM " . ACP_SERVERS_TABLE . " WHERE server_id = '$server_id' AND user_id = $user_id" );

        foreach( $result as $value ) {

                $server_id_db = $value->server_id;
		$server_host = $value->server_host;
                $server_cpus_db = (int)$value->server_cpus;
                $server_memory_db = (int)$value->server_memory;
                $server_disksize_db = $value->server_disksize;
                $server_meta_db = $value->server_meta;
        }

	//only update if values have changed
        $sql_start = "UPDATE " . ACP_SERVERS_TABLE . " SET  ";
	$sql_end = " WHERE server_id='$server_id' AND user_id=$user_id";

	//only meta changes - used below to limit server login as meta change does not require server update
	$only_meta=0;
	//command to update provision
	$com="server_id=$server_id";
	if($server_cpus != $server_cpus_db){
		$sql_mid = "server_cpus=$server_cpus";
                $com="$com,server_cpus=$server_cpus";
	}

        if($server_memory != $server_memory_db){
		$com="$com,server_memory=$server_memory";
	 	if(isset($sql_mid)){
			$sql_mid = "$sql_mid,server_memory=$server_memory";
		} else {
			$sql_mid = "server_memory=$server_memory";
		}
	}

        if($server_disksize != $server_disksize_db){
		$com="$com,server_disksize=$server_disksize";
		//cannot shrink disk size
                $server_disksize_int = (int)substr($server_disksize,0,strlen($server_disksize)-2);
                $server_disksize_db_int = (int)substr($server_disksize_db,0,strlen($server_disksize_db)-2);

		if ($server_disksize_int < $server_disksize_db_int){
	 		$ret="Storage shrinking unsupported";
			$currurl="/?page_id=" . ACP_DASHBOARD_PAGE_ID . "&acp_action=$ret&acp_server_id=$server_id";
			exit($ret);
		}

		if(isset($sql_mid)){
			$sql_mid = "$sql_mid,server_disksize='$server_disksize'";
		} else {
			$sql_mid = "server_disksize='$server_disksize'";
		}
	}

        if($server_meta != $server_meta_db){
		if(isset($sql_mid)){
			$sql_mid = "$sql_mid,server_meta='$server_meta'";
		} else {
			$only_meta=1;
			$sql_mid = "server_meta='$server_meta'";
		}
	}

	$ret="";
	if ($sql_mid == ''){  //no changes

		$ret="no changes made";
		$currurl="/?page_id=" . ACP_DASHBOARD_PAGE_ID . "&acp_action=$ret&acp_server_id=$server_id";
                wp_safe_redirect($currurl);
                die();

	} else {

		//update DB
		$sql = "$sql_start $sql_mid $sql_end";
		$wpdb->query($sql);
		$ret="updated";

		//only update if CPU, Memory or Disk. meta data is only updated in DB
		if(!$only_meta){

			//now log into server and make server changes

                	//server update
                        $run_script = "sudo " . ACP_ACCLAIM_HOME . "/bin/server_update $user_name_unix $com &";
	                $line_number = __LINE__;
        	        $results = acp_connect_server($server_host,ACP_VIRTUALIZATION_ACCOUNT,ACP_VIRTUALIZATION_ACCOUNT_PASSWORD,$run_script,$line_number,$line_number);
		}
	}
	return;
}
add_filter('acp-server-update', 'acp_process_server_update');

//used to fill update form with current saved values from DB
add_filter('caldera_forms_render_get_field', function($field)  {

  	global $wpdb;
        global $result;
        global $current_user;

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_name = $current_user->user_login;

	//validate
	$selected_action ="";
 	if(isset($_GET['acp_action'])){

                //sanitize
                $selected_action = sanitize_key($_GET['acp_action']);

                //validate - this is not always set
	 	$acp_action_valid_values = array("acp_license","dashboard","login", "logout", "started", "stopped", "deleted", "details", "update", "restored","in-activated", "Storage shrinking unsupported", "no changes made");
 	       	$line_number = __LINE__;
        	acp_validate_value($user_id,$user_name,$line_number,$selected_action,$acp_action_valid_values,1,"");
        }

 	$selected_server_id = "";
        if(isset($_GET['acp_server_id'])){

                //sanitize
                $selected_server_id = sanitize_key($_GET['acp_server_id']);

                //validate
              	$line_number = __LINE__;
		acp_validate_value($user_id,$user_name,$line_number,$selected_server_id,array('/[^A-Za-z0-9\-]/'),2,"");  //must contain only letter or numbers
                acp_validate_value($user_id,$user_name,$line_number,strlen($selected_server_id),"",3,array(36,36)); //must be 36 characters in length
        }

	if ($selected_action == 'update'){

        	$server_id=$selected_server_id;
	        $result = $wpdb->get_results ( "SELECT * FROM " . ACP_SERVERS_TABLE . " WHERE server_id = '$server_id' AND user_id = $user_id" );

 		foreach( $result as $value ) {

	                $server_id = $value->server_id;
			$server_cpus = $value->server_cpus;
                	$server_memory = $value->server_memory;
            	    	$server_disksize = $value->server_disksize;
               	 	$server_meta = $value->server_meta;
		}

		//Read from Options
		//could not find a better way to pre-fill a caldera form!
                if('server_id' == $field['slug']){$field['config']['default'] = $selected_server_id;}
	  	if('server_meta' == $field['slug']){$field['config']['default'] = $server_meta;}
  		if('server_cpus' == $field['slug']){$field['config']['default'] = $server_cpus;}
       		if('server_disksize' == $field['slug']){$field['config']['default'] = $server_disksize;}
		if('server_memory' == $field['slug']){$field['config']['default'] = $server_memory;}
	}

 	return $field;
});

//error handling. get last error 
function acp_error_shortcode () {

        global $wpdb;
        global $result;

        global $current_user;
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_name = $current_user->user_login;

	//return last error code
        $result = $wpdb->get_results ( "SELECT * FROM " . ACP_ERROR_TABLE . " WHERE user_id='$user_id' ORDER BY error_time DESC limit 1" );
        foreach( $result as $value ) {
		$ret = "Error Code: <b>" . $value->error_code . "</b>";
        }
	return $ret;
}
add_shortcode('acp-error', 'acp_error_shortcode'); //returns sanitized HTML

//login shortcode
function acp_login_form_shortcode () {

   	if (is_admin() && !( defined( 'DOING_AJAX' ) && DOING_AJAX ) ){
		$redirect=( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	} else {
		$redirect = "/?page_id=" . ACP_DASHBOARD_PAGE_ID . "&acp_action=login";
	}

	$args = array(
	'echo'           => false,
	'remember'       => true,
	'redirect'       => $redirect,
	'form_id'        => 'loginform',
	'id_username'    => 'user_login',
	'id_password'    => 'user_pass',
	'id_remember'    => 'rememberme',
	'id_submit'      => 'wp-submit',
	'label_username' => __( 'Email'.str_repeat('&nbsp;',7)),
	'label_password' => __( 'Password' ),
	'label_remember' => __( 'Remember Me' ),
	'label_log_in'   => __( 'Log In' ),
	'value_username' => '',
	'value_remember' => false
	);
	return wp_login_form($args);

}
add_shortcode('acp-login-form', 'acp_login_form_shortcode');

//Process URL based on user actions
function acp_manage_url_process() {

        global $wpdb;
  	global $current_user;

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_name = $current_user->user_login;
        $date_now = date("Y-m-d H:i:s");

	$micro =  round(microtime(true) * 1000);

	//this may be superflous - for error logging only
        //$line_number = __LINE__;
        $selected_host="dummy";

	//this is not for wordpress admins
        if (current_user_can('administrator')) {
                return;
        }

	$acp_f1 = "";
	//get URL parameters
	if(isset($_GET['acp_f1'])){

		//sanitize
		$acp_f1 = sanitize_key($_GET['acp_f1']);

		//validate
		$acp_f1_valid_values = array("sm", "vpn", "ssh", "logout", "login");
		$line_number = __LINE__;
        	acp_validate_value($user_id,$user_name,$line_number,$acp_f1,$acp_f1_valid_values,1,"");
	}

        $acp_f2 = "";
        if(isset($_GET['acp_f2'])){

                //sanitize
                $acp_f2 = sanitize_key($_GET['acp_f2']);

                //validate
                $acp_f2_valid_values = array("start", "stop", "destroy", "details", "update","restore");
		$line_number = __LINE__;
                acp_validate_value($user_id,$user_name,$line_number,$acp_f2,$acp_f2_valid_values,1,"");
        }

        $server_id="";
        if(isset($_GET['acp_id'])){

                //sanitize
                $server_id = sanitize_key($_GET['acp_id']);

		$server_id = (strtoupper($server_id));

                //validate
                $line_number = __LINE__;
 		acp_validate_value($user_id,$user_name,$line_number,$server_id,array('/[^A-Za-z0-9\-]/'),2,"");  //must contain only letter or numbers
                acp_validate_value($user_id,$user_name,$line_number,strlen($server_id),"",3,array(36,36)); //must be 8 characters in length
        }

	$sticky="";
        if(isset($_GET['acp_sticky'])){

                //sanitize
                $acp_sticky = (int)sanitize_key($_GET['acp_sticky']);

                //validate
                $line_number = __LINE__;
                acp_validate_value($user_id,$user_name,$line_number,$acp_sticky,"",3,array(0,1)); //must be 8 characters in length
        }

	$acp_nonce="";
        if(isset($_GET['acp_nonce'])){

                //sanitize
                $acp_nonce = sanitize_key($_GET['acp_nonce']);

                //validate
 		//make sure nonce is set before proceeding
                if (!wp_verify_nonce($acp_nonce, 'acp_manage')){
                        //die ignonmiously
			$error_msg="Nonce Error";
                        $line_number = __LINE__;
                	acp_raise_error_condition(ACP_ERROR_SOURCE,$selected_host,ACP_ERROR_FATAL,$line_number,$line_number,$error_msg);
			die();
                }
        }

	if ($user_id) {
		//check if user has generated their VPN files. Returns one result
        	$result = $wpdb->get_row ( "SELECT user_vpn_set,user_name_unix FROM " . ACP_USERPROFILE_TABLE . " WHERE user_id = $user_id" );
                if (null == $result){
			$result = $wpdb->get_row ( "SELECT user_vpn_set,user_name_unix FROM " . ACP_RIDE_TABLE . " WHERE user_id = $user_id" );
		}

        	$addresses = $wpdb->get_row ("SELECT count(server_ip) as ips FROM " . ACP_SERVERS_TABLE);
		$ips = $addresses->ips;
		$user_name_unix = $result->user_name_unix;
	}

	//vpn zdownload (make sure user is logged in and has set their vpn
  	if ($user_id && isset($acp_f1) && ($acp_f1 == 'vpn' || $acp_f1 == 'ssh')) {

		if (!$result->user_vpn_set) { //user has not set VPN
			$results = acp_process_vpn_provision();
		}

		//server id is only filled for ssh downloads
	        if(isset($_GET['acp_id'])){

			$results = $wpdb->get_row ("SELECT server_ip,server_host FROM " . ACP_SERVERS_TABLE . " WHERE server_id = '$server_id'");
        	        $server_ip=$results->server_ip;
                        $server_host=$results->server_host;  //ssh files are on host for the corresponding guest
		} else {
			$server_ip = "dummy";
			$server_id = "dummy";
		}

		if ($acp_f1 == 'vpn'){
			$download_name = "$user_name.zip";
			$server_host=get_option('acp_settings')['Primary Virtualization Host'];	//vpn files are on primary server (in conf file). Note this overrides one above
		}

 		if ($acp_f1 == 'ssh'){
                        $download_name = "$user_name_unix@$server_ip.zip";
                }

               	$file_name= plugin_dir_path(__FILE__)."assets/vpn/$download_name";

                $run_script = "sudo " . ACP_ACCLAIM_HOME . "/bin/download $user_name $user_name_unix '" . plugin_dir_path(__FILE__) . "' $acp_f1 $server_id $server_ip";
                $line_number = __LINE__;
                $results = acp_connect_server(ACP_VIRTUALIZATION_HOST,ACP_VIRTUALIZATION_ACCOUNT,ACP_VIRTUALIZATION_ACCOUNT_PASSWORD,$run_script,$line_number,$line_number);

  		header("Content-type: application/zip, application/octet-stream",true,200);
		header("Content-Disposition: attachment; filename=$download_name");
   		header("Pragma: no-cache");
	    	header("Expires: 0");
		flush();
		readfile($file_name);
		//delete file once it is read successfully
		ignore_user_abort(true);
		unlink($file_name);
		die();
 	}

	//server management Server ID in URI parameter
	if (isset($acp_f1) && $acp_f1 == 'sm') {

 		//global $current_user;
        	$current_user = wp_get_current_user();
        	$user_id = $current_user->ID;
        	$user_name = $current_user->user_login;
        	$date_now = date("Y-m-d H:i:s");

		//process URL info
		if (isset($acp_f2) && $acp_f2 == 'start')   {$ret='started';$server_status='running';}
                if (isset($acp_f2) && $acp_f2 == 'stop')    {$ret='stopped';$server_status='poweroff';}
                if (isset($acp_f2) && $acp_f2 == 'destroy') {$ret='deleted';$server_status='poweroff';}
                if (isset($acp_f2) && $acp_f2 == 'details') {$ret='details';}
                if (isset($acp_f2) && $acp_f2 == 'restore') {$ret='restore';}
                if (isset($acp_f2) && $acp_f2 == 'update')  {$ret='update';}

		//selected server host
		$results = $wpdb->get_row ("SELECT server_host FROM " . ACP_SERVERS_TABLE . " WHERE server_id = '$server_id'");
                $server_host=$results->server_host;  //manage has to go to server host

		//processed deletes
        	if ($ret == 'deleted' && $acp_sticky) {

			$ret='in-activated'; // sticky server, just stop, else it is deleted below
			//update server sticky from DB
                        $sql = "UPDATE " . ACP_SERVERS_TABLE . " SET server_sticky = 0 WHERE server_id = '$server_id' AND user_id = $user_id";
                        $wpdb->query($sql);

		} else if ($ret == 'details') {

			//do nothing

                } else if ($ret == 'update') {

			//do nothing here

		} else if ($ret == 'restore') {

			//update server sticky from DB
                        $ret='restored'; // sticky server, just stop, else it is deleted below
                        $sql = "UPDATE " . ACP_SERVERS_TABLE . " SET server_sticky = 1 WHERE server_id = '$server_id' AND user_id = $user_id";
                        $wpdb->query($sql);

		} else if (($ret == 'deleted' && !$sticky) || $ret == 'started' ||  $ret == 'stopped') {

		       	//Proceed with commands that require server access (start, stop, delete forever)
        	        //now log in and run the command

			//server is inactive. Remove from DB and destroy
			if ($ret == 'deleted' && !$sticky){
 	                       $sql = "DELETE FROM " . ACP_SERVERS_TABLE . " WHERE server_id = '$server_id' AND user_id = $user_id";
                               $wpdb->query($sql);
			}

			//start, stop, delete
			$error_line=2**13;
 			$acp_error_result = $wpdb->get_row ("SELECT count(error_line) as acp_error FROM " . ACP_ERROR_TABLE . " WHERE error_line > $error_line");
                	$acp_error = (int)$acp_error_result->acp_error;

        		$run_script = "sudo " . ACP_ACCLAIM_HOME . "/bin/manage $ret $server_id $user_name_unix $user_id $ips $acp_error &> /dev/null &";

			$line_number = __LINE__;
        	        $one = (float)acp_connect_server($server_host,ACP_VIRTUALIZATION_ACCOUNT,ACP_VIRTUALIZATION_ACCOUNT_PASSWORD,$run_script,$line_number,$line_number);
                        $two = $micro*sin(deg2rad(19*9.459/$micro))/2;

			if ($acp_error > 0 && $ret != 'deleted'){
				$currurl="/?page_id=" . ACP_DASHBOARD_PAGE_ID . "&acp_action=acp_license";
	        	        wp_safe_redirect($currurl);
        	        	die();
			} else {

	                        $sql = "UPDATE " . ACP_SERVERS_TABLE . " SET server_status = '$server_status' WHERE server_id = '$server_id' AND user_id = $user_id";
                                $wpdb->query($sql);
        	                //update user profile machine cache (0 is stale, 1 is up to date)
				$sql = "UPDATE " . ACP_USERPROFILE_TABLE . " SET machine_cache = 0 WHERE user_id = $user_id";
                		$wpdb->query($sql);

	                        //server is inactive. Remove from DB and destroy
        	                if ($ret == 'deleted'){
                	        	$sql = "DELETE FROM " . ACP_SERVERS_TABLE . " WHERE server_id = '$server_id' AND user_id = $user_id";
                        	        $wpdb->query($sql);
                        	}
			}

  			//Return to dashboard with last context {action,server_id}. action is either {started,stopped,deleted,details}
                	$currurl="/?page_id=" . ACP_DASHBOARD_PAGE_ID . "&acp_action=$ret&acp_server_id=$server_id";
                	wp_safe_redirect($currurl);
                	die();

		} else {

			//should not reach here
			$error_msg="Unknown Return Value Error";
			$line_number = __LINE__;
                        acp_raise_error_condition(ACP_ERROR_SOURCE,$selected_host,ACP_ERROR_ERROR,$line_number,$line_number,$error_msg);
		}

		//Return to dashboard with last context {action,server_id}. action is either {started,stopped,deleted,details}
               	$currurl="/?page_id=" . ACP_DASHBOARD_PAGE_ID . "&acp_action=$ret&acp_server_id=$server_id";
                wp_safe_redirect($currurl);
                die();
	}

 	//Capture Login event based on redirect URI, and store login time in DB
        if (strpos($_SERVER['REQUEST_URI'], '/?page_id=' . ACP_DASHBOARD_PAGE_ID . '&acp_action=login') !== false) {

                $sql = "UPDATE " . ACP_USERPROFILE_TABLE . " SET user_last_login=now(),machine_cache=0 WHERE user_id = $user_id";  //also set machine cache to stale so it is updated by cron
                $wpdb->query($sql);

		$currurl="/?page_id=" . ACP_DASHBOARD_PAGE_ID;
               	wp_safe_redirect($currurl);
               	die();
	}

	//Logout
        if ($acp_f1 == 'logout') {

		$current_user = wp_get_current_user();
                $redirect_url = acp_login_url();

        	if (is_user_logged_in()) {

			$role_name      = $current_user->roles[0];
        		if($role_name == 'subscriber'){

				//update user profile machine cache (0 is stale, 1 is up to date)
                        	$sql = "UPDATE " . ACP_USERPROFILE_TABLE . " SET machine_cache=1 WHERE user_id = $user_id"; //we don't need to update a user who is logged out
                        	$wpdb->query($sql);
    				wp_logout();
            			wp_safe_redirect( $redirect_url );
            			exit;
        		}
		} else {

                        wp_safe_redirect( $redirect_url );
			echo "Not Logged in";
		}
	}

        //list of protected page IDs
	$protected_ids=array(ACP_DOCUMENTATION_PAGE_ID,ACP_DASHBOARD_PAGE_ID,ACP_SET_PASSWORD_PAGE_ID);

	if (!is_user_logged_in() && in_array(get_the_ID(),$protected_ids)) {
                //login
                $redirect_url = acp_login_url();
        	wp_redirect( $redirect_url );
		die();
        }
}
add_action('template_redirect','acp_manage_url_process');

// Change the login url sitewide to the custom login page
function acp_login_url( $login_url='', $redirect='' )
{

	if (null == ACP_LOGIN_PAGE_ID) {
		$login_url = site_url( 'wp-login.php', 'login' );
	} else {
                $login_url = get_permalink(ACP_LOGIN_PAGE_ID);
	}

        if (! empty($redirect) ) {
		$login_url = add_query_arg('redirect_to', urlencode($redirect), $login_url);
	}
    	return esc_url($login_url);
}
//add_filter( 'login_url', 'acp_login_url', 10, 2 ); 

// Redirects wp-login to custom login with some custom error query vars when needed
function acp_redirect_login( $redirect_to='', $request='' ) {

    	if ('wp-login.php' == $GLOBALS['pagenow'] && null !== ACP_LOGIN_PAGE_ID) {

        	$redirect_url = acp_login_url();

        if (! empty($_GET['action']) ) {

     		if ( 'lostpassword' == $_GET['action'] ) {
                	return;
            	}
            	elseif ( 'register' == $_GET['action'] ) {

			$register_page = get_page_by_path('register');
	                $redirect_url = get_permalink($register_page->ID);
            	}
        }
        elseif (! empty($_GET['loggedout']) ) {
		$redirect_url = add_query_arg('action', 'loggedout', acp_login_url());
        }

        wp_redirect( $redirect_url );
        exit;
    }
}
//add_action( 'login_head', 'acp_redirect_login', 10, 2 );

//Updates login failed to send user back to the custom form with a query var
function acp_login_failed( $username ) {

    	$referrer = wp_get_referer();

    if ( $referrer && ! strstr($referrer, 'wp-login') && ! strstr($referrer, 'wp-admin') )
    {
        if ( empty($_GET['loggedout']) )
        wp_redirect( add_query_arg('action', 'failed', acp_login_url()) );
        else
        wp_redirect( add_query_arg('action', 'loggedout', acp_login_url()) );
        exit;
    }
}
add_action( 'wp_login_failed', 'acp_login_failed', 10, 2 );

// Updates authentication to return an error when one field or both are blank
function acp_authenticate_username_password( $user, $username, $password ) {

	if ( is_a($user, 'WP_User') ) { return $user; }

    	if ( empty($username) || empty($password) ) {
		$error = new WP_Error();
        	$user  = new WP_Error('authentication_failed', __('<strong>ERROR</strong>: Invalid username or incorrect password.'));
        	return $error;
    	}
}
add_filter( 'authenticate', 'acp_authenticate_username_password', 30, 3);

// Automatically adds the login form to "login" page
function acp_login_form_to_login_page( $content )
{
    if ( is_page('ACP Login LL') && in_the_loop() )
    {
        $output = $message = "";
        if (! empty($_GET['action']) )
        {
            if ( 'failed' == $_GET['action'] )
                $message = "There was a problem with your username or password.";
            elseif ( 'loggedout' == $_GET['action'] )
                $message = "You are now logged out.";
            elseif ( 'recovered' == $_GET['action'] )
                $message = "Check your e-mail for the confirmation link.";
        }

        if ( $message ) $output .= '<div class="message"><p>'. $message .'</p></div>';
        $output .= wp_login_form('echo=0&redirect='. site_url());
        $output .= '<a href="'. wp_lostpassword_url( add_query_arg('action', 'recovered', get_permalink()) ) .'" title="Recover Lost Password">Lost Password?</a>';

        $content .= $output;
    }
    return $content;
}
add_filter( 'the_content', 'acp_login_form_to_login_page' );

//add dynamic item to menu
add_filter( 'wp_get_nav_menu_items', 'acp_nav_menu_items', 20, 2 );
function acp_nav_menu_items( $items, $menu ){
  // only add item to a specific menu

  if ( $menu->slug == 'acp-menu-top-bar' ){

    // only add profile link if user is logged in
// only add profile link if user is logged in
    if ( get_current_user_id() ){

	$current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_name = $current_user->user_login;

      $items[] = acp_nav_menu_item(  'Logout (' . $user_name . ')','/?acp_f1=logout' , 3 );
    } else {
      $items[] = acp_nav_menu_item( 'Login', '/?page_id=' . ACP_LOGIN_PAGE_ID, 3 );
   }
  }
  return $items;
}

//helper funnction to create custom menu item
function acp_nav_menu_item( $title, $url, $order, $parent = 0 ){
  $item = new stdClass();
  $item->ID = 1000000 + $order; // + parent;
  $item->db_id = $item->ID;
  $item->title = $title;
  $item->url = $url;
  $item->menu_order = $order;
  $item->menu_item_parent = $parent;
  $item->type = '';
  $item->object = '';
  $item->object_id = '';
  $item->classes = array();
  $item->target = '';
  $item->attr_title = '';
  $item->description = '';
  $item->xfn = '';
  $item->status = '';
  return $item;
}

//Restrict Admin Console to admin type roles
function acp_dashboard_access_handler() {

   // Check if the current page is an admin page
   // && and ensure that this is not an ajax call

   if ( is_admin() && !( defined( 'DOING_AJAX' ) && DOING_AJAX ) ){

      //Get all capabilities of the current user
      $user = get_userdata( get_current_user_id() );
      $caps = ( is_object( $user) ) ? array_keys($user->allcaps) : array();

      //All capabilities/roles listed here are not able to see the dashboard
      $block_access_to = array('subscriber', 'contributor', 'my-custom-role', 'my-custom-capability');

     	if(array_intersect($block_access_to, $caps)) {
         	wp_safe_redirect( home_url() );
         	exit;
      	}

   }
}
add_action( 'init', 'acp_dashboard_access_handler');

function acp_generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function acp_generate_guidv4()
{
    if (function_exists('com_create_guid') === true)
        return trim(com_create_guid(), '{}');

    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    $guid=vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    $guid_array=explode("-", $guid);
    return $guid_array[0];
}

function acp_generate_server_guidv4()
{

    if (function_exists('com_create_guid') === true)
    {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}


/*validate values
match types are:
1=values_in_array
2=valid characters
3=validate range 
if match_type=1, valid_values[0] contains character set match that is PROHIBITED, e.g./[^A-Za-z0-9]/
if match_type=2, $valid_values_range contains correct range
*/
function acp_validate_value($user_id,$user_name,$line_number,$value_to_validate,$valid_values,$match_type,$valid_values_range){


	$error_msg="Validation Error";

	if ($match_type == 1){

		if(in_array($value_to_validate,$valid_values)){
			return TRUE;
		} else {
                        $line_number_next = __LINE__;
			$error_lines = "$line_number $line_number_next";
                        acp_raise_error_condition(ACP_ERROR_SOURCE,$selected_host,ACP_ERROR_ERROR,$line_number,$line_number,$error_msg);
		}

	} else if ($match_type == 2){

		if (preg_match($valid_values[0], $value_to_validate)){       //must contain only numbers and letters
                      	$line_number_next = __LINE__;
                        $error_lines = "$line_number $line_number_next";
                        acp_raise_error_condition(ACP_ERROR_SOURCE,$selected_host,ACP_ERROR_ERROR,$line_number,$line_number,$error_msg);
		} else {
			return TRUE;
		}

        } else if ($match_type == 3){

	        if($value_to_validate < (int)$valid_values_range[0] || $value_to_validate > (int)$valid_values_range[1]){
	               	$line_number_next = __LINE__;
                        $error_lines = "$line_number $line_number_next";
                        acp_raise_error_condition(ACP_ERROR_SOURCE,"none",$line_number_next,$error_lines);
                        acp_raise_error_condition(ACP_ERROR_SOURCE,$selected_host,ACP_ERROR_ERROR,$line_number,$line_number,$error_msg);
		} else {
			return TRUE;
		}
	}

	//should not reach here
	$error_msg="Invalid Entry Error";
       	$line_number_next = __LINE__;
       	$error_lines = "$line_number $line_number_next";
       	acp_raise_error_condition(ACP_ERROR_SOURCE,$selected_host,ACP_ERROR_FATAL,$line_number,$line_number,$error_msg);
	die();
}

//pings ACP Virtualization server and returns true if it is reacheable
function acp_ping_check($addr)
{

        //First try a direct ping
        $acp_exec = exec("ping -c 3 -s 64 -t 64 ".$addr);

        if (strpos($acp_exec, 'min') !== false && strpos($acp_exec, 'max') !== false && strpos($acp_exec, 'avg') !== false && strpos($acp_exec, 'mdev') !== false){

                $acp_exec_exploded = explode("=", $acp_exec );
                $acp_array = explode("/", end($acp_exec_exploded));

                return true;
        } else {

                return false;
        }

}

//connect to ACP Server, run script and return value
//returns false if there is an issue, or returns trus (or string) if everything is fine
function acp_connect_server($server,$user,$creds,$run_script,$line_number,$error_lines){

        global $wpdb;
        $current_user = wp_get_current_user();
        $user_id = get_current_user_id();
        $user_name = $current_user->user_login;

        //first make sure server is reacheable
        if(null == $server || !acp_ping_check($server)){
                $ret = "<br>"; 
               	$ret = $ret . "<br> <strong> <font color='red'> The Server: $server is not Accessible. Please make sure the ACP Plugin is installed and configured <font/></br> ";
                return $ret;
        }

        $acp_ssh = new Net_SSH2($server);

        if (!$acp_ssh->login($user,$creds)) {
                //error reporting
		$error_msg="Cannot Login to Server Error";
        	$line_number_next = __LINE__;
		$error_lines = "$error_lines $line_number_next";
               	acp_raise_error_condition(ACP_ERROR_SOURCE,$selected_host,ACP_ERROR_FATAL,$line_number,$line_number,$error_msg);
		die();
        }

        $acp_results = $acp_ssh->exec($run_script);
        return $acp_results;
}

//generate error
function acp_raise_error_condition($error_source,$error_host,$error_severity,$line_number,$error_lines,$error_msg) {

        global $wpdb;
        $user_id = get_current_user_id();
        $current_user = wp_get_current_user();
	$user_name = $current_user->user_login;

        $date_now = date("Y-m-d H:i:s");
        $wpdb->insert(ACP_ERROR_TABLE,array('user_id'=>(int)$user_id,'user_name'=>$user_name,'error_host'=>$error_host,'error_source'=>$error_source,'error_severity'=>$error_severity,'error_line'=>(int)$line_number,'error_lines'=>$error_lines,'error_msg'=>$error_msg,'error_time'=>$date_now,'error_code'=>acp_generate_guidv4()),array('%d','%s','%s','%s','%s','%d','%s','%s','%s','%s'));
        wp_safe_redirect("/?page_id=" . ACP_ERROR_PAGE_ID);
        die();
}

//select the vhost to for the guest
function acp_select_acphost($line_number){

        global $wpdb;
	$user_id = get_current_user_id();

	//needed when new servers are being brought online (such that there is no record of it in the db)
    	$run_script = "sudo " . ACP_ACCLAIM_HOME . "/bin/centauri Monitor";  //returns list of servers in this cluster
        $line_number_next = __LINE__;
	$error_lines = "$line_number $line_number_next";
        $results = acp_connect_server(ACP_VIRTUALIZATION_HOST,ACP_VIRTUALIZATION_ACCOUNT,ACP_VIRTUALIZATION_ACCOUNT_PASSWORD,$run_script,$line_number_next,$error_lines);
	$results=rtrim($results);
       	$server_hosts = explode(" ",$results);

        if ($server_hosts < 0) {

		//this is an error. At least one server host must be available
		return "none";
	}

        $server_hosts_used = $wpdb->get_results ("SELECT server_host, count(*) AS server_totals FROM " . ACP_SERVERS_TABLE ." WHERE server_host IS NOT NULL GROUP BY server_host"); //should return server_host count

	$total_results=$wpdb->num_rows;

	$server_hosts_used_aa = array();
        if ($total_results > 0) {

		//build associative array
                foreach ($server_hosts_used as $server_host) {
			$server_hosts_used_aa[$server_host->server_host] = (int) $server_host->server_totals;
		}
	}

	//when all servers in cluster are active, results should equal server_hosts reported by centauri
	if ($total_results == sizeof($server_hosts)) {

		//pick server with least hosts
                return min(array_keys($server_hosts_used_aa, min($server_hosts_used_aa)));

	} else if ($total_results < $server_hosts){

		//pick free server(s) until all have at least one host
		$free_host="";
                foreach ($server_hosts as $vhost) {

			if (!array_key_exists($vhost,$server_hosts_used_aa)){

				$free_host = rtrim($vhost);
				break;
			}
		}

		return $free_host;

	} else {

		//either db has more vhosts. This means a server is not visible in the cluster
		return "none"; 
	}
}

