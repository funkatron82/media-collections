<?php
//Post AJAX API


add_action( 'admin_init', 'ced_post_ajax_js' );

function ced_post_ajax_js() {
	wp_enqueue_script( 'ced-post-ajax', plugins_url( 'post-ajax.js', __FILE__ ), array( 'backbone', 'media-editor', 'media-models', 'media-audiovideo' ) );
	wp_localize_script( 'ced-post-ajax', 'cedPostNonces', array( 'create' => wp_create_nonce( 'create-post' ) ) );
}


function ced_process_post_response( $post ) {
	if ( ! $post = get_post( $post ) )
		return;	
	
	$response = array(
		'id'          => $post->ID,
		'title'       => $post->post_title,
		'permalink'        => get_permalink( $post->ID ),
		'author'      => $post->post_author,
		'content'	  => $post->post_content,
		'excerpt'     => $post->post_excerpt,
		'name'        => $post->post_name,
		'status'      => $post->post_status,
		'parent'	  => $post->post_parent,
		'date'        => strtotime( $post->post_date_gmt ) * 1000,
		'modified'    => strtotime( $post->post_modified_gmt ) * 1000,
		'menuOrder'   => $post->menu_order,
		'type'        => $post->post_type,
		'nonces'      => array(
			'update' => false,
			'delete' => false,
		),
		'editLink'   => false,
	);
	
	$author = new WP_User( $post->post_author );
	$response['authorName'] = $author->display_name;
	
	if ( current_user_can( 'edit_post', $post->ID ) ) {
		$response['nonces']['update'] = wp_create_nonce( 'update-post_' . $post->ID );
		$response['editLink'] = get_edit_post_link( $post->ID, 'raw' );
	}
	
	if ( current_user_can( 'delete_post', $post->ID ) )
		$response['nonces']['delete'] = wp_create_nonce( 'delete-post_' . $post->ID );
	$response = apply_filters( "ced_process_{$post->post_type}_response", $response, $post );
	return apply_filters( 'ced_process_post_response', $response, $post );
}

function ced_ajax_create_post() {
	check_ajax_referer( 'create-post' );
	
	if ( ! current_user_can( 'publish_posts' ) )
		wp_send_json_error( 'Cannot publish');
		
	$attributes = $_REQUEST['attributes'];	
		
	$post = array();
	if ( isset( $attributes['title'] ) )
		$post['post_title'] = $attributes['title'];

	if ( isset( $attributes['excerpt'] ) )
		$post['post_excerpt'] = $attributes['excerpt'];

	if ( isset( $attributes['content'] ) )
		$post['post_content'] = $attributes['content'];

	if ( isset( $attributes['status'] ) )
		$post['post_status'] = $attributes['status'];
	
	if ( isset( $attributes['parent'] ) )
		$post['post_parent'] = $attributes['parent'];
		
	if ( isset( $attributes['menuOrder'] ) )
		$post['menu_order'] = $attributes['menuOrder'];
		
	if ( isset( $attributes['author'] ) )
		$post['post_author'] = $attributes['author'];
		
	if ( isset( $attributes['type'] ) )
		$post['post_type'] = $attributes['type'];
		
	if ( isset( $attributes['date'] ) )
		$post['post_date_gmt'] = date( 'Y-m-d H:i:s', $attributes['date'] );
				
	$id = wp_insert_post( $post );
	if( is_wp_error( $id ) ) 
		wp_send_json_error();
		
	$post = get_post( $id );
		
	do_action( 'ced_ajax_create_post', $attributes, $id );
	do_action( 'ced_ajax_create_' . $post->post_type , $attributes, $id );
	
	wp_send_json_success( ced_process_post_response( $id ) );
}

add_action( 'wp_ajax_ced-create-post', 'ced_ajax_create_post' );

function ced_ajax_read_post() {
	if ( ! isset( $_REQUEST['id'] ) )
		wp_send_json_error();

	if ( ! $id = absint( $_REQUEST['id'] ) )
		wp_send_json_error();

	wp_send_json_success( ced_process_post_response( $id ) );
}

add_action( 'wp_ajax_ced-read-post', 'ced_ajax_read_post' );

function ced_ajax_update_post() {
	if ( ! isset( $_REQUEST['id'] ) )
		wp_send_json_error();

	if ( ! $id = absint( $_REQUEST['id'] ) )
		wp_send_json_error();
	
	check_ajax_referer( 'update-post_' . $id );
	
	if ( ! current_user_can( 'edit_post', $id ) )
		wp_send_json_error();

	if ( ! $post = get_post( $id ) )
		wp_send_json_error();
		
	$changes = $_REQUEST['changes'];	
	$post    = get_post( $id, ARRAY_A );
	
	if ( isset( $changes['title'] ) )
		$post['post_title'] = $changes['title'];

	if ( isset( $changes['excerpt'] ) )
		$post['post_excerpt'] = $changes['excerpt'];

	if ( isset( $changes['content'] ) )
		$post['post_content'] = $changes['content'];

	if ( isset( $changes['status'] ) )
		$post['post_status'] = $changes['status'];
	
	if ( isset( $changes['parent'] ) )
		$post['post_parent'] = $changes['parent'];
		
	if ( isset( $changes['menuOrder'] ) )
		$post['menu_order'] = $changes['menuOrder'];
		
	if ( isset( $changes['type'] ) )
		$post['post_type'] = $changes['type'];	
		
	if ( isset( $changes['status'] ) && 'trash' === $changes['status'] ) {
		wp_delete_post( $id );
	} else {
		wp_update_post( $post );
	}
	

	do_action( 'ced_ajax_update_post', $changes, $id );
	do_action( 'ced_ajax_update_' . $post['post_type'], $changes, $id );

	wp_send_json_success( ced_process_post_response( $id ) );
}

add_action( 'wp_ajax_ced-update-post', 'ced_ajax_update_post' );


function ced_ajax_delete_post() {
	if ( ! isset( $_REQUEST['id'] ) )
		wp_send_json_error();

	if ( ! $id = absint( $_REQUEST['id'] ) )
		wp_send_json_error();
		
	check_ajax_referer( 'delete-post_' . $id );
	
	if ( ! current_user_can( 'delete_post', $id ) )
		wp_send_json_error();

	if ( ! $post = get_post( $id ) )
		wp_send_json_error();
		
	if ( ! wp_delete_post( $id ) )
		wp_send_json_error();
		
	do_action( 'ced_ajax_delete_post', $id );
	
	wp_send_json_success();
}

add_action( 'wp_ajax_ced-delete-post', 'ced_ajax_delete_post' );