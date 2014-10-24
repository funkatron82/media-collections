jQuery( document ).ready( function( $ ) {
	if( playlistData ) {
		var playlist = new ced.mediaCollections.models.Playlist(playlistData);
		var playlistToolbar = new ced.mediaCollections.views.playlist.Toolbar({el: '#cedmc-toolbar', model:playlist}).render();
		var playlistPreview = new ced.mediaCollections.views.playlist.Preview({el: '#cedmc-preview', model:playlist}).render();
	}
} );