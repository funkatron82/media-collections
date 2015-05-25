jQuery( document ).ready( function( $ ) {
	var id = $( '[name=post_ID]' ).val(),
		playlist = new ced.mediaCollections.models.Playlist( { id: id } );
	playlist.fetch().done( function(){
		new ced.mediaCollections.views.Playlist( { model: playlist, el: '#cedmc-main' } )
	} );
} );