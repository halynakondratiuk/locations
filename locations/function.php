<?php

function _enqueue_script() {

    wp_enqueue_script( 'bootstrap-js', plugin_dir_url( __FILE__ ) . '/bootstrap/js/bootstrap.min.js', array( 'jquery' ) );
    wp_enqueue_style( 'bootstrap-css', plugin_dir_url( __FILE__ ) . '/bootstrap/css/bootstrap.min.css'  );

}
add_action( 'admin_enqueue_scripts', '_enqueue_script' );


