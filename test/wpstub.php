<?php

class wpdb {

    public $t_get_var = "";
    public $terms = 'wp_terms';

    public function get_var($query, $column_offset = 0, $row_offset = 0) {
        return $this->t_get_var;
    }

}

$wpdb = new wpdb();

function get_option($option, $default = false) {
    return 'open';
}

function get_post_types() {
    return array("post", "page", "custom");
}

?>
