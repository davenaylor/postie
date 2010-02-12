<?php
/*
Plugin Name: Postie
Plugin URI: http://blog.robfelty.com/plugins/postie
Description: Signifigantly upgrades the posting by mail features of Word Press (See <a href='options-general.php?page=postie/postie.php'>Settings and options</a>) to configure your e-mail settings. See the <a href='http://wordpress.org/extend/plugins/postie/other_notes'>Readme</a> for usage. Visit the <a href='http://forum.robfelty.com/forum/postie'>postie forum</a> for support.
Version: 1.4
Author: Robert Felty
Author URI: http://blog.robfelty.com/
*/

/*
$Id$
* -= Requests Pending =-
* German Umlats don't work
* Problems under PHP5 
* Problem with some mail server
* Multiple emails should tie to a single account
* Each user should be able to have a default category
* WP Switcher not compatible
* Setup poll
    - web server
    - mail clients
    - plain/html
    - phone/computer
    - os of server
    - os of client
    - number of users posting
* Test for calling from the command line
* Support userid/domain  as a valid username
* WP-switcher not compatiable http://www.alexking.org/index.php?content=software/wordpress/content.php#wp_120
* Test out a remote cron system
* Add support for http://unknowngenius.com/wp-plugins/faq.html#one-click
*    www.cdavies.org/code/3gp-thumb.php.txt
*    www.cdavies.org/permalink/watchingbrowserembeddedgpvideosinlinux.php
* Support private posts
* Make it possible to post without a script at all
*/

//Older Version History is in the HISTORY file
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

define("POSTIE_ROOT",dirname(__FILE__));
define("POSTIE_URL", WP_PLUGIN_URL . '/' . basename(dirname(__FILE__)));


function postie_loadjs_add_page() {
	$postiepage = add_options_page('Postie', 'Postie', 8, POSTIE_ROOT.'/postie.php', 'postie_loadjs_options_page');
	add_action( "admin_print_scripts-$postiepage", 'postie_loadjs_admin_head' );
}

function postie_loadjs_options_page() {
	require_once POSTIE_ROOT.'/config_form.php';
}

function postie_loadjs_admin_head() {
	$plugindir = get_settings('home').'/wp-content/plugins/'.dirname(plugin_basename(__FILE__));
	wp_enqueue_script('loadjs', $plugindir . '/js/simpleTabs.jquery.js');
	echo '<link type="text/css" rel="stylesheet" href="' .get_bloginfo('url') .'/wp-content/plugins/postie/css/style.css" />'."\n";
	echo '<link type="text/css" rel="stylesheet" href="' .get_bloginfo('url') .'/wp-content/plugins/postie/css/simpleTabs.css" />'."\n";
}


if (isset($_GET["postie_read_me"])) {
    include_once(ABSPATH . "wp-admin/admin.php");
    $title = __("Edit Plugins");
    $parent_file = 'plugins.php';
    include(ABSPATH . 'wp-admin/admin-header.php');
    postie_read_me();
    include(ABSPATH . 'wp-admin/admin-footer.php');
}
//Add Menu Configuration
if (is_admin()) {
  require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR ."postie-functions.php");
  //add_action("admin_menu","PostieMenu");
  add_action('admin_menu', 'postie_loadjs_add_page');
  if(function_exists('load_plugin_textdomain')){
    $plugin_dir = WP_PLUGIN_DIR . '/' . basename(dirname(__FILE__));
    function postie_load_domain() {
      load_plugin_textdomain( 'postie', $plugin_dir."/languages/",
      basename(dirname(__FILE__)). '/languages/');
    }
    add_action('init', 'postie_load_domain'); 
  }
  postie_warnings(); 
}
//register_activation_hook(__FILE__, 'UpdateArrayConfig');
function activate_postie() {
  register_setting('postie-settings');
	static $init = false;
	$options = get_option('postie-settings');
	
	if(!$init) {
		if(!$options) {
			$options = array();
		}	
    $default_options = array(
      'admin_username' => 'admin', 
      'prefer_text_type' => "plain",
      'default_title' => "Live From The Field",
      'input_protocol' => "pop3",
      'image_placeholder' => "#img%#",
      'images_append' => true,
      'allow_subject_in_mail' => true,
      'drop_signature' => true,
      'message_start' => ":start",
      'message_end' => ":end",
      'forward_rejected_mail' => true,
      'return_to_sender' => false,
      'confirmation_email' => false,
      'allow_html_in_subject' => true,
      'allow_html_in_body' => true,
      'start_image_count_at_zero' => false,
      'message_encoding' => "UTF-8",
      'message_dequote' => true, 
      'turn_authorization_off' => false,
      'custom_image_field' => false,
      'convertnewline' => false,
      'sig_pattern_list' => '--\n- --',
      'banned_files_list' => '',
      'supported_file_types' => "video\napplication",
      'authorized_addresses' => '',
      'mail_server' => NULL,
      'mail_server_port' =>  NULL,
      'mail_userid' =>  NULL,
      'mail_password' =>  NULL,
      'default_post_category' =>  NULL,
      'default_post_tags' =>  NULL,
      'time_offset' =>  get_option('gmt_offset'),
      'wrap_pre' =>  'no',
      'converturls' =>  true,
      'shortcode' =>  false,
      'add_meta' =>  'no',
      'icon_set' => 'silver',
      'icon_size' => 32,
      'audiotemplate' =>$simple_link,
      'selected_audiotemplate' => 'simple_link',
      'selected_video1template' => 'simple_link',
      'video1template' => $simple_link,
      'video1types' => 'mp4,mpeg4,3gp,3gpp,3gpp2,3gp2,mov,mpeg',
      'audiotypes' => 'm4a,mp3,ogg,wav,mpeg',
      'selected_video2template' => 'simple_link',
      'video2template' => $simple_link,
      'video2types' => 'x-flv',
      'post_status' => 'publish',
      'image_new_window' => false,
      'filternewlines' => true,
      'selected_imagetemplate' => 'wordpress_default',
      'imagetemplate' => $wordpress_default,
      'delete_mail_after_processing' => true,
      'interval' => 'twiceperhour',
      'smtp' => '',
    );
		
		$updated = false;
		$migration = false;
	  $oldConfig=getConfig();	
		foreach($default_options as $option => $value) {
			if(!isset($options[$option])) {
				// Migrate old options
        $oldOption = strtoupper($option);
				if ($oldConfig[$oldOption]) {
          // we handle some old array options individually
          $commas = array('audiotypes', 'video1types', 'video2types',
          'default_post_tags');
          $newlines = array('smtp', 'authorized_addresses', 'supported_file_types',
          'banned_files_list', 'sig_patterns_list');
          if (in_array($option, $commas)) {
            $options[$option] = implode(', ', $oldConfig[$oldOption]);
          } elseif (in_array($options, $newlines)) {
            $options[$option] = $implode("\n", $oldConfig[$oldOption]);
          } else {
            $options[$option] = $oldConfig[$oldOption];
          }
					$migration = true;
				} else {
					$options[$option] = $default_options[$option];
				}
				$updated = true;
			}
		}
		
		if($updated) {
			update_option('postie-settings', $options);
		}
		if ($migration) {
		}
		$init = true;
	}
	return $options;
}
register_activation_hook(__FILE__, 'activate_postie');
function postie_warnings() {
  if ($config=get_option('postie-settings'))
    extract($config);
  if ($mail_server=='' && !isset($_POST['submit'])) {
    function postie_enter_info() {
      echo "
      <div id='postie-info-warning' class='updated fade'><p><strong>".
      __('Postie is almost ready.', 'postie')."</strong> "
      .sprintf(__('You must <a href="%1$s">enter your email settings</a> for it to work.','postie'), "options-general.php?page=postie/postie.php")."</p></div> ";
    }
    add_action('admin_notices', 'postie_enter_info');
  }
  if (!function_exists('imap_mime_header_decode') && $_GET['activate']==true) {
    function postie_imap_warning() {
      echo "<div id='postie-imap-warning' class='error'><p><strong>";
      echo __('Warning: the IMAP php extension is not installed.', 'postie');
      echo __('Postie may not function correctly without this extension (especially for non-English messages).', 'postie');
      echo "</strong> ";
      //echo __('Warning: the IMAP php extension is not installed. Postie may not function correctly without this extension (especially for non-English messages) .', 'postie')."</strong> ".
      echo sprintf(__('Please see the <a href="%1$s">FAQ </a> for more information.'), "options-general.php?page=postie/postie.php", 'postie')."</p></div> ";
    }
    add_action('admin_notices', 'postie_imap_warning');
  }
}

function disable_kses_content() {
remove_filter('content_save_pre', 'wp_filter_post_kses');
}
add_action('init','disable_kses_content',20);
function postie_whitelist($options) {
	$added = array( 'postie-settings' => array( 'postie-settings' ) );
	$options = add_option_whitelist( $added, $options );
	return $options;
}
add_filter('whitelist_options', 'postie_whitelist');

function check_postie() {
    $host = get_option('siteurl');
    preg_match("/https?:\/\/(.[^\/]*)(.*)/",$host,$matches);
    $host = $matches[1];
    $url = "";
    if (isset($matches[2])) {
        $url .=  $matches[2];
    }
    $url .= "/wp-content/plugins/postie/get_mail.php";
    $port = 80;
	$fp=fsockopen($host,$port,$errno,$errstr);
    fputs($fp,"GET $url HTTP/1.0\r\n");
    fputs($fp,"User-Agent:  Cronless-Postie\r\n");
    fputs($fp,"Host: $host\r\n");
    fputs($fp,"\r\n");
    $page = '';
    while(!feof($fp)) {
        $page.=fgets($fp,128);
    }
    fclose($fp);
}

function postie_cron() {
  global $wpdb;
  $config=get_option('postie-settings');
  if (!$config['interval'] || $config['interval']=='')
    $config['interval']='hourly';
  if ($config['interval']=='manual') {
    wp_clear_scheduled_hook('check_postie_hook');
  } else {
    wp_schedule_event(time(),$config['interval'],'check_postie_hook');
  }
}
function postie_decron() {
  wp_clear_scheduled_hook('check_postie_hook');
}

/* here we add some more options for how often to check for e-mail */
function more_reccurences() {
  return array(
  'weekly' => array('interval' => 604800, 'display' => 'Once Weekly'),
  'twiceperhour' => array('interval' => 1800, 'display' => 'Twice per hour '),
  'tenminutes' =>array('interval' => 600, 'display' => 'Every 10 minutes')
  );
}
add_filter('cron_schedules', 'more_reccurences');
register_activation_hook(__FILE__,'postie_cron');
register_deactivation_hook(__FILE__,'postie_decron');
add_action('check_postie_hook', 'check_postie');
?>
