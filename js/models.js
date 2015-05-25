window.ced = window.ced || {};

(function($){
	var Gallery, Playlist, media = wp.media;
	
	mediaCollections = ced.mediaCollections = {};
	
	_.extend( mediaCollections, { models: {}, views: {}, controllers: {}, frames: {} } );
	
	Gallery = mediaCollections.models.Gallery = ced.models.Post.extend( {
		tag: 'gallery',
		type: 'image',
		defaults: wp.media.gallery.defaults
	} );
	
	Playlist = mediaCollections.models.Playlist = ced.models.Post.extend( {
		initialize: function( model, options ) {
			this.type = this.get( 'playlist_type' ) || 'audio';
			this.on( 'change:playlist_type', function() {
				this.type = this.get( 'playlist_type' ) || 'audio';
			}, this );
			this.trigger( 'change:type' );	
		},
		tag: 'playlist',
		defaults: wp.media.playlist.defaults
	} );
	
} )(jQuery);