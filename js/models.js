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
			console.log(method);
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
				console.log(options.data);
				return media.ajax( options );
				
			} else {
				/**
				 * Call `sync` directly on Backbone.Model
				 */
				return Backbone.Model.prototype.sync.apply( this, arguments );
			}
		},
		
		media: function() {
			var attrs = this.toJSON(), args, query, others, self = this;

			// Fill the default shortcode attributes.
			attrs = _.defaults( attrs, this.defaults );
			args  = _.pick( attrs, 'orderby', 'order' );

			args.type    = this.type;
			args.perPage = -1;

			// Mark the `orderby` override attribute.
			if ( undefined !== attrs.orderby ) {
				attrs._orderByField = attrs.orderby;
			}

			if ( 'rand' === attrs.orderby ) {
				attrs._orderbyRandom = true;
			}

			// Map the `orderby` attribute to the corresponding model property.
			if ( ! attrs.orderby || /^menu_order(?: ID)?$/i.test( attrs.orderby ) ) {
				args.orderby = 'menuOrder';
			}

			// Map the `ids` param to the correct query args.
			if ( attrs.ids && attrs.ids.length > 0 ) {
				args.post__in = attrs.ids;
				args.orderby  = 'post__in';
			} else if ( attrs.include && attrs.include.length > 0 ) {
				args.post__in = attrs.include;
			}

			if ( attrs.exclude && attrs.excluide.length > 0 ) {
				args.post__not_in = attrs.exclude;
			}

			if ( ! args.post__in ) {
				args.uploadedTo = attrs.id;
			}

			// Collect the attributes that were not included in `args`.
			others = _.omit( attrs, 'id', 'ids', 'include', 'exclude', 'orderby', 'order', 'nonces' );

			_.each( others, function( value, key ) {
				others[ key ] = wp.media.coerce( others, key );
			});

			query = wp.media.query( args );
			query[ this.tag ] = new Backbone.Model( others );
			return query;	
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