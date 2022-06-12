<?php

namespace plugin\clone_posts;

/**
 * Display an admin notice on the Posts page after cloning
 */
function admin_notices() {
	global $pagenow;

	// Return if we are not in the edit page
	if ( 'edit.php' !== $pagenow ) {
		return;
	}

	// Return if nonce verification fails
	if ( ! isset( $_GET['post_cloned_nonce'] ) || ! wp_verify_nonce( $_GET['post_cloned_nonce'], 'post_cloned' ) ) {
		return;
	}

	// Display notice if cloned get param is set to 1
	if ( isset( $_GET['cloned'] ) && 1 === (int) $_GET['cloned'] ) {
		echo '<div class="updated"><p>' . esc_html__( 'Post cloned.', 'clone-posts' ) . '</p></div>';
	}

}

/**
 * Filters the array of row action links on the admin table.
 */
function post_row_actions( $actions, $post ) {
	global $post_type;

	$options = maybe_unserialize( get_option( 'clone_posts_post_type' ) );

	if ( ! is_array( $options ) ) {
		$options = array( 'post', 'page' );
	}

	if ( ! in_array( $post_type, $options ) ) {
		return $actions;
	}

	$url = wp_nonce_url( admin_url( 'edit.php?post_type=' . $post_type ), 'clone_post', 'clone_post_nonce' );
	$url = add_query_arg(
        array(
            'action'   => 'clone-post',
            'post'     => $post->ID,
            'redirect' => filter_input( INPUT_SERVER, 'REQUEST_URI' ),
        ),
        $url
    );

	$actions['clone'] = '<a href="' . $url . '">' . __( 'Clone', 'clone-posts' ) . '</a>';

	return $actions;
}

/**
 * Fires before admin_init, executes the cloning if necessary, clears query args and redirects
 */
function wp_loaded() {
	global $post_type;

	// Return if clone post action isn't set
	if ( ! isset( $_GET['action'] ) || 'clone-post' !== $_GET['action'] ) {
		return;
	}

	// Return if nonce verification fails
	if ( ! isset( $_GET['clone_post_nonce'] ) || ! wp_verify_nonce( $_GET['clone_post_nonce'], 'clone_post' ) ) {
		return;
	}

	// Grab the post we want to clone
	$post_id = (int) $_GET['post'];

	// Return if the current user can't edit posts
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You are not allowed to clone this post.', 'clone-posts' ) );
	}

	// Try to clone the post, display an error if something goes wrong
	if ( ! clone_post( $post_id ) ) {
		wp_die( esc_html__( 'Error cloning post.', 'clone-posts' ) );
	}

	// Prepare the URL where we have to redirect the user
	$sendback = remove_query_arg( array( 'cloned', 'untrashed', 'deleted', 'ids' ), $_GET['redirect'] );

	if ( ! $sendback ) {
		$sendback = admin_url( 'edit.php?post_type=' . $post_type );
	}

	$sendback = add_query_arg( array( 'cloned' => 1 ), $sendback );
	$sendback = remove_query_arg(
		array(
			'action',
			'action2',
			'tags_input',
			'post_author',
			'comment_status',
			'ping_status',
			'_status',
			'post',
			'bulk_edit',
			'post_view',
		),
		$sendback
	);
	$sendback = wp_nonce_url( $sendback, 'post_cloned', 'post_cloned_nonce' );
	wp_safe_redirect( $sendback );
	exit();
}

/**
 * Clone the Post
 * @param $id - ID of the post that should be cloned
 */
function clone_post( $id ) {

	$post_to_clone = get_post( $id );

	if ( null === $post_to_clone ) {
		return false;
	}

	// Create new post with original post data
	$new_post = array(
		'post_name'             => $post_to_clone->post_name,
		'post_type'             => $post_to_clone->post_type,
		'post_parent'           => $post_to_clone->post_parent,
		'menu_order'            => $post_to_clone->menu_order,
		'post_password'         => $post_to_clone->post_password,
		'post_excerpt'          => $post_to_clone->post_excerpt,
		'comment_status'        => $post_to_clone->comment_status,
		'post_title'            => $post_to_clone->post_title . ' ' . __( '[Cloned]', 'clone-posts' ),
		'post_content'          => $post_to_clone->post_content,
		'post_author'           => $post_to_clone->post_author,
		'post_content_filtered' => $post_to_clone->post_content_filtered,
		'post_category'         => $post_to_clone->post_category,
		'tags_input'            => $post_to_clone->tags_input,
		'tax_input'             => $post_to_clone->tax_input,
		'page_template'         => $post_to_clone->page_template,
	);

	$post_status = get_option( 'clone_posts_post_status' );
	if ( 'draft' !== $post_status ) {
		$new_post['post_status'] = $post_status;
	}

	$date = get_option( 'clone_posts_post_date' );
	if ( 'current' !== $date ) {
		$new_post['post_date']     = $post_to_clone->post_date;
		$new_post['post_date_gmt'] = $post_to_clone->post_date_gmt;
	}

	$new_id = wp_insert_post( $new_post );

	// Clone post format
	$format = get_post_format( $id );
	set_post_format( $new_id, $format );

	// Clone all metadata
	$meta = get_post_meta( $id );
	foreach ( $meta as $key => $val ) {
		update_post_meta( $new_id, $key, $val[0] );
	}

	return true;
}
