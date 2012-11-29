<?php

define('ABSPATH', dirname(__FILE__) . '/');

class wpdb {

    public $t_get_var = "";
    public $terms = 'wp_terms';

    public function get_var($query, $column_offset = 0, $row_offset = 0) {
        return $this->t_get_var;
    }

}

class WP_Error {
    
}

$wpdb = new wpdb();

function get_option($option, $default = false) {
    return 'open';
}

function get_post_types() {
    return array("post", "page", "custom");
}

function current_time() {
    return '2005-08-05 10:41:13';
}

function get_post() {
    $r = new stdClass();
    $r->post_date = '';
    $r->post_parent = 0;
    $r->guid = '7b0d965d-b8b0-4654-ac9e-eeef1d8cf571';
    $r->post_title = '';
    return $r;
}

function __($t) {
    return $t;
}

function wp_check_filetype() {
    return array('ext' => 'xxx', 'type' => 'xxx/xxx');
}

function wp_upload_dir() {
    return array(
        'path' => '/tmp',
        'url' => 'http://example.com/upload/',
        'subdir' => '/tmp/',
        'basedir' => '/tmp;',
        'baseurl' => 'http://example.com/',
        'error' => false
    );
}

function wp_unique_filename() {
    return '/tmp/file.txt';
}

function wp_get_attachment_url() {
    return 'http://example.net/wp-content/uploads/filename';
}

function image_downsize() {
    return array('http://example.net/wp-content/uploads/filename.jpg', 10, 10, true);
}

function image_hwstring() {
    return 'width="10" height="10" ';
}

function get_attachment_link() {
    return 'http://example.net/wp-content/uploads/filename.jpg';
}

?>
