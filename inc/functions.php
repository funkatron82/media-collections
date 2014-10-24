<?php
//Helper functions

function get_gallery_media( $gallery ) {
	$gallery = get_post( $gallery );
	
	return new WP_Query( array(
	  'connected_type' => 'gallery_to_media',
	  'connected_items' => $gallery,
	  'nopaging' => true,
	  'post_status' => 'inherit',
	  'connected_orderby' => 'media_order',
	  'connected_order' => 'asc',
	  'connected_order_num' => true,
	  'post_mime_type' => 'image'
	) );	
}

function get_gallery_media_ids( $gallery ) {
	$media = get_gallery_media( $gallery );
	return wp_list_pluck( $media->posts, 'ID' );	
}

function get_gallery_meta( $gallery ) {
	$gallery = get_post( $gallery );
	$keys = apply_filters( 'cedmc_gallery_keys', array( 'link', 'columns', 'orderby', 'order' ) ); 
	$meta = array();
	foreach( $keys as $key ) {
		$value = get_post_meta( $gallery->ID, $key, true );
		if( $value ) {
			$meta[$key] = $value;
		}
	}
	return $meta;
}

//Playlists
function get_playlist_media( $playlist ) {
	$playlist = get_post( $playlist );
	$type = get_playlist_type( $playlist->ID );
	
	return new WP_Query( array(
	  'connected_type' => 'playlist_to_media',
	  'connected_items' => $playlist,
	  'nopaging' => true,
	  'post_status' => 'inherit',
	  'connected_orderby' => 'media_order',
	  'connected_order' => 'asc',
	  'connected_order_num' => true,
	  'post_mime_type' => $type
	) );	
}

function get_playlist_media_ids( $playlist ) {
	$media = get_playlist_media( $playlist );
	return wp_list_pluck( $media->posts, 'ID' );	
}

function get_playlist_meta( $playlist ) {
	$playlist = get_post( $playlist );
	$keys = apply_filters( 'cedmc_playlist_keys', array( 'tracklist', 'images', 'artists', 'tracknumbers', 'style' ) ); 
	$meta = array( 'type' => get_playlist_type( $playlist->ID ));
	foreach( $keys as $key ) {
		$value = get_post_meta( $playlist->ID, $key, true );
		if( $value ) {
			$meta[$key] = $value;
		}
	}
	return $meta;
}

function get_playlist_type( $playlist ) {	
	$playlist =  get_post( $playlist );
	
	$types = get_the_terms( $playlist->ID, 'playlist_type' );

	if ( empty( $types ) )	               
		return 'audio';
	
	$type = array_shift( $types );	
	
	return $type->slug;
}
