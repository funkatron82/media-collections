<?php
//Helper functions

function get_gallery_media( $gallery, $args = array() ) {
	$gallery = get_post( $gallery );
	$args = wp_parse_args( array( 'media_in_gallery' => $gallery->ID ), $args );
	return new WP_Query( $args );	
}

function get_gallery_media_ids( $gallery, $args = array() ) {
	$media = get_gallery_media( $gallery, $args );
	return wp_list_pluck( $media->posts, 'ID' );	
}

function get_gallery_meta( $gallery ) {
	$gallery = get_post( $gallery );
	$meta = get_post_meta( $gallery->ID, '_gallery_metadata', true );
	return $meta ? (array) $meta : array();
}

//Playlists
function get_playlist_media( $playlist, $args = array() ) {
	$playlist = get_post( $playlist );
	$args = wp_parse_args( array( 'media_in_playlist' => $playlist->ID ), $args );
	return new WP_Query( $args );	
}

function get_playlist_media_ids( $playlist, $args = array() ) {
	$media = get_playlist_media( $playlist, $args );
	return wp_list_pluck( $media->posts, 'ID' );	
}

function get_playlist_meta( $playlist ) {
	$playlist = get_post( $playlist );
	$meta = get_post_meta( $playlist->ID, '_playlist_metadata', true );
	$meta = $meta ? (array) $meta : array();
	$meta['type'] = get_playlist_type( $playlist->ID );
	return $meta;
}

function get_playlist_type( $playlist ) {	
	$playlist =  get_post( $playlist );
	
	$types = get_the_terms( $playlist->ID, 'playlist_type' );

	if ( empty( $types ) ) {              
		wp_set_object_terms( $playlist->ID, 'audio', 'playlist_type' );
		return 'audio';
	}
	
	$type = array_shift( $types );	
	
	return $type->slug;
}
