<?php
function ttf_child_enqueue_scripts() {
    $asset_file = get_stylesheet_directory() . '/build/index.asset.php';
    $asset = file_exists( $asset_file )
        ? require( $asset_file )
        : [ 'dependencies' => [], 'version' => '1.0.0' ];

    wp_enqueue_script(
        'ttf-child-scripts',
        get_stylesheet_directory_uri() . '/build/index.js',
        $asset['dependencies'],
        $asset['version'],
        true
    );
}
add_action( 'wp_enqueue_scripts', 'ttf_child_enqueue_scripts' );
