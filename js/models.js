window.ced = window.ced || {};

(function($){
	var MediaCollection, Gallery, Playlist, media = wp.media;
	
	mediaCollections = ced.mediaCollections = {};
	
	_.extend( mediaCollections, { models: {}, views: {}, controllers: {}, frames: {} } );

	MediaCollection = mediaCollections.models.MediaCollection = Backbone.Model.extend( {
		sync: function( method, model, options ) {
			
			if ( _.isUndefined( this.id ) ) {
				return $.Deferred().rejectWith( this ).promise();
			}

			//Overload sync
			if( 'read' == method ) {
				options = options || {};
				options.context = this;
				options.data = _.extend( options.data || {}, {
					action: 'cedmc_read_' + this.tag,
					id: this.id
				});
				return media.ajax( options );
			} else if ( 'update' === method ) {
				// If we do not have the necessary nonce, fail immeditately.
				if ( ! this.get('nonces').update ) {
					return $.Deferred().rejectWith( this ).promise();
				}
				
				options = options || {};
				options.context = this;
			
				// Set the action and ID.
				options.data = _.extend( options.data || {}, {
					action:  'cedmc_update_' + this.tag,
					id:      this.id,
					'_ajax_nonce':   this.get('nonces').update
				});
				
				
				
				// Record the values of the changed attributes.
				if ( model.hasChanged() ) {
					options.data.changes = {};

					_.each( model.changed, function( value, key ) {
						options.data.changes[ key ] = this.get( key );
					}, this );
				}
				return media.ajax( options );
				
			} else {
				/**
				 * Call `sync` directly on Backbone.Model
				 */
				return Backbone.Model.prototype.sync.apply( this, arguments );
			}
		}
	} );
	
	Gallery = mediaCollections.models.Gallery = MediaCollection.extend( {
		tag: 'gallery',
		type: 'image',
		defaults: wp.media.gallery.defaults
	} );
	
	Playlist = mediaCollections.models.Playlist = MediaCollection.extend( {
		initialize: function( model, options ) {
			this.on( 'change:type', function() {
				this.type = this.get( 'type' ) || 'audio';
			}, this );
			this.trigger( 'change:type' );	
		},
		tag: 'playlist',
		defaults: wp.media.playlist.defaults
	} );
	
} )(jQuery);