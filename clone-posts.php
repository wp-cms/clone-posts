<?php
/*
Plugin Name:    Clone Posts
Description:    Allow user to clone posts in the Admin Area.
Version:        2.0.3
Author:         Álvaro Franz
Domain Path:    /languages/
Plugin URI:     https://github.com/wp-cms/clone-posts
*/

namespace plugin\clone_posts;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

// Include plugin files
require plugin_dir_path( __FILE__ ) . 'includes/main-functions.php';
require plugin_dir_path( __FILE__ ) . 'includes/plugin-activation.php';
require plugin_dir_path( __FILE__ ) . 'includes/load-textdomain.php';
require plugin_dir_path( __FILE__ ) . 'includes/settings-page.php';

// Setup admin hooks
add_action( 'admin_init', 'plugin\clone_posts\register_settings' );
add_action( 'admin_menu', 'plugin\clone_posts\admin_page' );
add_action( 'admin_notices', 'plugin\clone_posts\admin_notices' );
add_filter( 'post_row_actions', 'plugin\clone_posts\post_row_actions', 10, 2 );
add_filter( 'page_row_actions', 'plugin\clone_posts\post_row_actions', 10, 2 );
add_action( 'wp_loaded', 'plugin\clone_posts\wp_loaded' );
