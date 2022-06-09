<?php
/*
Plugin Name: Clone Posts
Description: Allow user to clone posts in the Admin Area.
Version: 1.0.0
Text Domain: clone-posts
Domain Path: /languages/
*/

namespace plugin\clone_posts;

if(!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

require plugin_dir_path( __FILE__ ) . 'includes/plugin-activation.php';
require plugin_dir_path( __FILE__ ) . 'includes/load-textdomain.php';
require plugin_dir_path( __FILE__ ) . 'includes/settings-page.php';
require plugin_dir_path( __FILE__ ) . 'includes/post-editing-screen.php';

// Admin hooks
add_action( 'admin_init', 'plugin\clone_posts\register_settings' );
add_action( 'admin_menu', 'plugin\clone_posts\admin_page' );
add_action( 'admin_footer-edit.php', 'plugin\clone_posts\admin_footer' );
add_action( 'load-edit.php', 'plugin\clone_posts\bulk_action' );
add_action( 'admin_notices', 'plugin\clone_posts\admin_notices' );
add_filter( 'post_row_actions', 'plugin\clone_posts\post_row_actions', 10, 2 );
add_filter( 'page_row_actions', 'plugin\clone_posts\post_row_actions', 10, 2 );
add_action( 'wp_loaded', 'plugin\clone_posts\wp_loaded' );

/**
 * Fires before admin_init, clears query args and redirects
 */
function wp_loaded() {
    global $post_type;

    if ( ! isset($_GET['action']) || $_GET['action'] !== "clone-single") {
        return;
    }

    $post_id = (int) $_GET['post'];

    if ( !current_user_can('edit_post', $post_id )) {
        wp_die( __('You are not allowed to clone this post.', 'clone-posts') );
    }

    if ( !clone_single( $post_id )) {
        wp_die( __('Error cloning post.', 'clone-posts') );
    }

    $sendback = remove_query_arg( array( 'cloned', 'untrashed', 'deleted', 'ids' ), $_GET['redirect'] );
    if ( ! $sendback ) {
        $sendback = admin_url( "edit.php?post_type=$post_type" );
    }

    $sendback = add_query_arg( array( 'cloned' => 1 ), $sendback );
    $sendback = remove_query_arg( array( 'action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status',  'post', 'bulk_edit', 'post_view'), $sendback );
    wp_redirect($sendback);
    exit();
}


/**
 * Clone the Post
 */
function clone_single( $id ) {
    $p = get_post( $id );
    if ($p == null) return false;

    $new_post = array(
        'post_name'				=> $p->post_name,
        'post_type'				=> $p->post_type,
        'post_parent'			=> $p->post_parent,
        'menu_order'			=> $p->menu_order,
        'post_password'			=> $p->post_password,
        'post_excerpt'			=> $p->post_excerpt,
        'comment_status'		=> $p->comment_status,
        'post_title'			=> $p->post_title . __('- clone', 'clone-posts'),
        'post_content'			=> $p->post_content,
        'post_author'			=> $p->post_author,
        'post_content_filtered' => $p->post_content_filtered,
        'post_category'			=> $p->post_category,
        'tags_input'			=> $p->tags_input,
        'tax_input'				=> $p->tax_input,
        'page_template'			=> $p->page_template,
    );

    $post_status = get_option('clone_posts_post_status');
    if ( $post_status !== 'draft' ) {
        $new_post['post_status'] = $post_status;
    }

    $date = get_option('clone_posts_post_date');
    if ( $date !== 'current' ) {
        $new_post['post_date'] = $p->post_date;
        $new_post['post_date_gmt'] = $p->post_date_gmt;
    }

    $new_id = wp_insert_post($new_post);
    $format = get_post_format($id);
    set_post_format($new_id, $format);

    $meta = get_post_meta($id);
    foreach($meta as $key=>$val) {
        update_post_meta( $new_id, $key, $val[0] );
    }

    return true;
}