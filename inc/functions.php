<?php
//Helper functions

//Gallery

/**
 * Is the post a gallery?
 * 
 * @since	1.0.0
 *
 * @param	(int|WP_Post)	$post	Post being tested.  Defaults to current post in a loop.
 *
 * @return	bool	Returns true if post is a gallery.
 */
function is_gallery( $post = NULL ) {
	return 'gallery' === get_post_type( $post );
}

/**
 * Gets media associated to gallery
 * 
 * @since 	1.0.0
 *
 * @param	(int|WP_Post)	$gallery	Gallery.
 *
 * @return	array	Returns media connected to gallery.
 */
function get_gallery_media( $gallery = NULL ) {
	$gallery = get_post( $gallery );
	
	if( ! is_gallery( $gallery ) ) {
		return false;	
	}
	
	if( $gallery->media ) {
		return $gallery->media;
	}
	
	$media = new WP_Query( array( 'media_in_gallery' => $gallery->ID ) );
	return 	$media->posts;
}


/**
 * Gets IDs of media associated with gallery
 * 
 * @since	1.0.0
 *
 * @param	(int|WP_Post)	$gallery	Gallery.
 *
 * @return	array	Returns IDs of media in gallery.
 */
function get_gallery_media_ids( $gallery = NULL ) {
	if( ! is_gallery( $gallery ) ) {
		return false;	
	}
	$media = get_gallery_media( $gallery );
	return wp_list_pluck( $media, 'ID' );	
}

/**
 * Gets meta data of gallery
 * 
 * @since	1.0.0
 *
 * @param	(int|WP_Post)	$gallery	Gallery.
 *
 * @return	array	Returns related meta data.
 */
function get_gallery_meta( $gallery = NULL ) {
	$gallery = get_post( $gallery );
	if( ! is_gallery( $gallery ) ) {
		return false;	
	}
	$meta = get_post_meta( $gallery->ID, '_gallery_metadata', true );
	return $meta ? (array) $meta : array();
}

/**
 * Is this a gallery archive?
 * 
 * @since 1.0.0
 *
 * @return	bool	Returns true if current archive is a gallery archive
 */
function is_gallery_archive() {
	return is_post_type_archive( 'gallery' ); 
}

//Playlists

/**
 * Checks to see if post is a playlist.
 * 
 * @since 1.0.0
 *
 * @param	(int|WP_Post)	$post	Post being tested.  Defaults to current post in a loop.
 *
 * @return	bool	Returns true if post is a playlist.
 */
function is_playlist( $post = NULL ) {
	return 'playlist' === get_post_type( $post );
}

/**
 * Checks to see if post is an audio playlist.
 * 
 * @since 1.0.0
 *
 * @param	(int|WP_Post)	$post	Post being tested.  Defaults to current post in a loop.
 *
 * @return	bool	Returns true if post is a audio playlist.
 */
function is_audio_playlist( $playlist = NULL ) {
	return is_playlist( $playlist ) && ( 'audio' == get_playlist_type( $playlist ) );
}

/**
 * Checks to see if post is a video playlist.
 * 
 * @since 1.0.0
 *
 * @param	(int|WP_Post)	$post	Post being tested.  Defaults to current post in a loop.
 *
 * @return	bool	Returns true if post is a video playlist.
 */
function is_video_playlist( $playlist = NULL ) {
	return is_playlist( $playlist ) && ( 'video' == get_playlist_type( $playlist ) );
}

/**
 * Gets media associated with playlist
 * 
 * @since 1.0.0
 *
 * @param	(int|WP_Post)	$playlist	Playlist.
 *
 * @return	array	Media in playlist.
 */
function get_playlist_media( $playlist = NULL ) {
	$playlist = get_post( $playlist );
	
	if( ! is_playlist( $playlist ) ) {
		return false;	
	}
	
	if( $playlist->media ) {
		return $playlist->media;	
	}

	$media = new WP_Query( array( 'media_in_playlist' => $playlist->ID ) );
	return $media->posts;	
}

/**
 * Gets IDs of media associated to playlist
 * 
 * @since 1.0.0
 *
 * @param	(int|WP_Post)	$playlist	Playlist.
 *
 * @return	array	Returns IDs of media in playlist.
 */
function get_playlist_media_ids( $playlist = NULL  ) {
	if( ! is_playlist( $playlist ) ) {
		return false;	
	}
	$media = get_playlist_media( $playlist, $args );
	return wp_list_pluck( $media, 'ID' );	
}

/**
 * Gets meta data of playlist
 * 
 * @since 1.0.0
 *
 * @param	(int|WP_Post)	$playlist	Playlist.
 *
 * @return	array	Returns related meta data.
 */
function get_playlist_meta( $playlist = NULL ) {
	$playlist = get_post( $playlist );
	if( ! is_playlist( $playlist ) ) {
		return false;	
	}
	$meta = get_post_meta( $playlist->ID, '_playlist_metadata', true );
	return $meta ? (array) $meta : array();
}

/**
 * Gets playlist type
 * 
 * @since 1.0.0
 *
 * @param	(int|WP_Post)	$playlist	Playlist.
 *
 * @return	string	Returns playlist type.
 */
function get_playlist_type( $playlist = NULL ) {	
	$playlist =  get_post( $playlist );
	
	if( ! is_playlist( $playlist ) ) {
		return false;	
	}
	
	$types = get_the_terms( $playlist->ID, 'playlist_type' );

	if ( empty( $types ) ) {              
		return 'audio';
	}
	
	$type = array_shift( $types );	
	
	return in_array( $type->slug, array( 'audio', 'video' ) ) ? $type->slug : 'audio';
}

/**
 * Is this a playlist archive?
 * 
 * @since 1.0.0
 *
 * @return	bool	Returns true if it is a playlist archive
 */
function is_playlist_archive() {
	return is_post_type_archive( 'playlist' ); 
}

/**
 * Is this an audio playlist archive?
 * 
 * @since 1.0.0
 *
 * @return	bool	Returns true if it is an audio playlist archive
 */
function is_audio_playlist_archive() {
	return is_playlist_archive() && is_tax( 'playlist_type', 'audio');
}

/**
 * Is this a video playlist archive?
 * 
 * @since 1.0.0
 *
 * @return	bool	Returns true if it is a video playlist archive
 */
function is_video_playlist_archive() {
	return is_playlist_archive() && is_tax( 'playlist_type', 'video');
}
