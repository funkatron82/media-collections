<?php
//Playlist Ajax

function cedmf_playlist_response( $response, $post ){
	$response['ids'] = (array) get_playlist_media_ids( $post );
	$response['playlistType'] = get_playlist_type( $post );
	return wp_parse_args( $response, (array) get_playlist_meta( $post ) );
}

add_filter( 'ced_process_playlist_response', 'cedmf_playlist_response', 10, 2 );

function cedmf_update_playlist( $changes, $id ) {	
	if( isset( $changes['ids'] ) ) {
		if( function_exists( 'p2p_distribute_connected' ) && p2p_type( 'playlist_to_media' )  ) { 
			$old_ids = get_playlist_media_ids( $id );
			foreach( $old_ids as $old_id ) {
				p2p_type( 'playlist_to_media' )->disconnect( $id, $old_id );
			}
			
			foreach( $changes['ids'] as $index => $media_id ) {
				p2p_type( 'playlist_to_media' )->connect( $id, $media_id, array( 'media_order' => $index ) );
			}	
		}	
	}
	
	if( isset( $changes['featured_id'] ) ) {
		delete_post_thumbnail( $id );
		set_post_thumbnail( $id, $changes['featured_id'] );
	}
	
	if( $changes['playlistType'] ) {
		wp_set_object_terms( $id, $changes['playlistType'], 'playlist_type' );
	}
	
	$defaults = array(
		'order'         => 'ASC',
		'orderby'       => 'post__in',
		'include'       => '',
		'exclude'   	=> '',
		'style'         => 'light',
		'tracklist' 	=> true,
		'tracknumbers' 	=> true,
		'images'        => true,
		'artists'       => true
	);
	$changes = array_intersect_key( $changes, $defaults );
	$meta = get_playlist_meta( $playlist );
	$meta = wp_parse_args( $meta, $defaults );
	$meta = wp_parse_args( $changes, $meta );
	update_post_meta( $id, '_playlist_metadata', $meta );
}

add_action( 'ced_ajax_update_playlist', 'cedmf_update_playlist', 10, 2 );