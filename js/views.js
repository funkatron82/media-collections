window.ced = window.ced || {};

( function($){
	var views = ced.mediaCollections.views, MediaCollectionPreview, GalleryBar, PlaylistBar, content, selector;

	selector = views.mediaCollectionType = wp.Backbone.View.extend( {
		initialize: function( options ) {
			var self = this, type = this.model.get( 'type' );
			this.$el.find('select').val( type );
		},
		template: wp.template( 'cedmc-selector' ),	
		
		events: {
			'change select' : 'update'	
		},
		
		update: function() {
			this.model.save( { type: this.$el.find('select').val() } );	
		},
		
		render: function() {
			var data = _.clone( this.model.attributes );
			if ( this.template ) {
				this.$el.html( this.template( data ) );
			}
			
			return this;	
		}
	} );
	
	MediaCollectionPreview = views.MediaCollectionPreview = wp.Backbone.View.extend( {
		initialize: function( options ) {
			var self = this;
			this.model.on( 'change', function(){
				self.render();
			} );	
		},
		
		coerce: wp.media.coerce,
		template: wp.template( 'cedmc-gallery-main' ),
		collectionTemplate: wp.template('editor-gallery'),
		 
		events: {
			'click .update' : 'update'	
		},
		
		update: function() {
			var frame = this.frame(), self = this, model = this.model;
			frame.on( 'update', function( media ) {
				self._media = media;
				var props = media.props.toJSON(),
					attrs = _.pick( props, 'orderby', 'order' ), clone;

				if ( media.type ) {
					attrs.type = model.type = media.type;
				}

				if ( media[model.tag] ) {
					_.extend( attrs, media[model.tag].toJSON() );
				}

				attrs.ids = media.pluck('id');

				// Check if the gallery is randomly ordered.
				delete attrs.orderby;

				if ( attrs._orderbyRandom ) {
					attrs.orderby = 'rand';
				} else if ( attrs._orderByField && attrs._orderByField != 'rand' ) {
					attrs.orderby = attrs._orderByField;
				}

				delete attrs._orderbyRandom;
				delete attrs._orderByField;

				// If the `ids` attribute is set and `orderby` attribute
				// is the default value, clear it for cleaner output.
				if ( attrs.ids && 'post__in' === attrs.orderby ) {
					delete attrs.orderby;
				}
				
				model.save( attrs );
			} );
		},
		frame: function() {
			var tag = this.model.tag, self = this, state, args = {}, selection, count = this.model.get( 'ids' ).length || 0;
			// Destroy the previous collection frame.
			if ( this._frame ) {
				this._frame.dispose();
			}
			
			if ( this.model.type && 'video' === this.model.type ) {
				state = 'video-' + tag;
			} else {
				state = tag;
			}
			if( count > 0 ) {
				state += '-edit';
				selection = this.selection();	
			} else {
				state +='-library';
			}
							
			this._frame = wp.media( {
				frame:     'post',
				state:     state,
				editing:   true,
				multiple:  true,
				selection: selection
			} ).open();
			
			return this._frame;						
		},
		
		selection: function() {
			var model = this.model, 
				media = this._media = this._media || model.media(), 
				selection;
			
			selection = new wp.media.model.Selection( media.models, {
				props:    media.props.toJSON(),
				multiple: true
			});

			selection[ model.tag ] = media[ model.tag ];

			// Fetch the query's attachments, and then break ties from the
			// query to allow for sorting.
			selection.more().done( function() {
				// Break ties with the query.
				selection.props.set({ query: false });
				selection.unmirror();
				selection.props.unset('orderby');
			});
						console.log(selection);

			return selection;
		},
		
		render: function() {
			var attrs = this.model.attributes,
				query = this._media || this.model.media(),
				options,
				self = this,
				media = [];
			
			/*query.more().done( function() {
				if ( query.length ) {
					media = query.toJSON();
			
					_.each( media, function( attachment ) {
							if ( attachment.sizes ) {
									if ( attachment.sizes.thumbnail ) {
											attachment.thumbnail = attachment.sizes.thumbnail;
									} else if ( attachment.sizes.full ) {
											attachment.thumbnail = attachment.sizes.full;
									}
							}
					} );
				}
				
				options = {
						attachments: media,
						columns: attrs.columns ? parseInt( attrs.columns, 10 ) : 3
				};
				if ( self.template ) {
				
			}
				self.$el.find('#cedmc-preview').html(self.collectionTemplate( options ));
			} );*/			
			
			
			self.$el.html( self.template( attrs ) );
			return this;	
		}
		
	} );
} )( jQuery );