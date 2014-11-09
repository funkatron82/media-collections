jQuery( document ).ready( function( $ ) {
	if( playlistData ) {
		var id = $( '#post_ID' ).val(), playlist = new ced.mediaCollections.models.Playlist( { id: id } ), playlistToolbar, playlistPreview;
		playlist.fetch().done( function() {
			playlistToolbar = new ced.mediaCollections.views.playlist.Toolbar({el: '#cedmc-toolbar', model:playlist}).render();
			playlistPreview = new ced.mediaCollections.views.playlist.Preview({el: '#cedmc-preview', model:playlist}).render();
		} );
	}
} );