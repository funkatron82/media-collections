<?php
//Gallery Ajax

function cedmf_gallery_response( $response, $post ){
	$response['ids'] = (array) get_gallery_media_ids( $post );
	$response['featuredId'] = get_post_thumbnail_id( $post->ID );
	return wp_parse_args( $response, (array) get_gallery_meta( $post ) );
}

add_filter( 'ced_process_gallery_response', 'cedmf_gallery_response', 10, 2 );

function cedmf_update_gallery( $changes, $id ) {	
	if( isset( $changes['ids'] ) ) {
		if( function_exists( 'p2p_distribute_connected' ) && p2p_type( 'gallery_to_media' )  ) { 
			$old_ids = get_gallery_media_ids( $id );
			foreach( $old_ids as $old_id ) {
				p2p_type( 'gallery_to_media' )->disconnect( $id, $old_id );
			}
			
			foreach( $changes['ids'] as $index => $media_id ) {
				p2p_type( 'gallery_to_media' )->connect( $id, $media_id, array( 'media_order' => $index ) );
			}	
		}	
	}
	
	if( isset( $changes['featuredId'] ) ) {
		delete_post_thumbnail( $id );
		set_post_thumbnail( $id, $changes['featuredId'] );
	}
	
	$html5 = current_theme_supports( 'html5', 'gallery' );
	$defaults = array(
		'order'      => 'ASC',
		'orderby'    => 'post__in',
		'itemtag'    => $html5 ? 'figure'     : 'dl',
		'icontag'    => $html5 ? 'div'        : 'dt',
		'captiontag' => $html5 ? 'figcaption' : 'dd',
		'columns'    => 3,
		'size'       => 'gallery_preview',
		'include'    => '',
		'exclude'    => '',
		'link'       => ''
	);

	$changes = array_intersect_key( $changes, $defaults );
	$meta = get_gallery_meta( $id );
	$meta = wp_parse_args( $meta, $defaults );
	$meta = wp_parse_args( $changes, $meta );
	update_post_meta( $id, '_gallery_metadata', $meta );	
}

add_action( 'ced_ajax_update_gallery', 'cedmf_update_gallery', 10,2 );