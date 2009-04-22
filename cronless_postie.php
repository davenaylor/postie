<?php
/*
Plugin Name: Cronless Postie
Plugin URI: http://www.economysizegeek.com/?page_id=395
Description: This plugin allows you to setup your rss feeds to trigger postie (See <a href="../wp-content/plugins/postie/cronless_postie.php?cronless_postie_read_me=1">Quick Readme</a>)
Author: Dirk Elmendorf
Version: 1.2
Author URI: http://www.economysizegeek.com/
*/ 

include_once (dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR. "wp-config.php");
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR ."postie-functions.php");
function check_postie() {
    $host = get_option('siteurl');
    preg_match("/http:\/\/(.[^\/]*)(.*)/",$host,$matches);
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
#var_dump($page);
    fclose($fp);
}
function cronless_read_me() {
    include_once("cronless_read_me.php");
}
if (isset($_GET["cronless_postie_read_me"])) {
    include_once(ABSPATH . "wp-admin/admin.php");
    $title = __("Edit Plugins");
    $parent_file = 'plugins.php';
    include(ABSPATH . 'wp-admin/admin-header.php');
    cronless_read_me();
    include(ABSPATH . 'wp-admin/admin-footer.php');
    exit();
}


//add_action('init','postie_cron');
register_activation_hook(__FILE__,'postie_cron');
add_action('check_postie_hook', 'check_postie');
function postie_cron() {
  $config=GetConfig();
  wp_schedule_event(time(),$config['CRONLESS'],'check_postie_hook');
}
register_deactivation_hook(__FILE__,'postie_decron');
function postie_decron() {
  wp_clear_scheduled_hook('check_postie_hook');
}

/* here we add some more options for how often to check for e-mail */
function more_reccurences() {
  return array(
  'weekly' => array('interval' => 604800, 'display' => 'Once Weekly'),
  'twiceperhour' => array('interval' => 1800, 'display' => 'Twice per hour
  '), 'tenminutes' =>array('interval' => 600, 'display' => 'Every 10 minutes')
  );
}
add_filter('cron_schedules', 'more_reccurences');
?>
