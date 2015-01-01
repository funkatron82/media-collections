window.ced = window.ced || {};

( function($){
	var views = ced.mediaCollections.views, Toolbar, GalleryToolbar, PlaylistToolbar, GalleryPreview, PlaylistPreview, GalleryFeatured;
	_.extend( views, { mediaCollection: {}, gallery: {}, playlist: {} } );
	
	Toolbar = views.mediaCollection.Toolbar = wp.Backbone.View.extend( {
		initialize: function( options ) {
			this.listenTo( this.model, 'change', this.render );
		},
				 
		events: function() { 
			return {
				'click .update' : 'update'	
			}
		},
		
		update: function() {
			var frame = this.frame();
			this.listenTo( frame, 'update', function( media ) {
				var shortcode = wp.media[this.tag].shortcode( media ),
					attrs = _.defaults( shortcode.attrs.named, this.model.defaults );
				attrs = _.omit( attrs, 'id' );
				this.model.save( attrs );
			} );
		},
		frame: function() {
			var tag = this.tag, 
				self = this, 
				state, 
				selection, 
				count = this.model.get( 'ids' ).length || 0;
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
				attrs = this.model.toJSON(),
				media,
				selection;
			attrs.ids = attrs.ids.toString();
			media = wp.media[this.tag].attachments( new wp.shortcode({
				tag:    this.tag,
				attrs:  attrs,
				type:   'single'
			}) );
			
			
			selection = new wp.media.model.Selection( media.models, {
				props:    media.props.toJSON(),
				multiple: true
			});

			selection[ this.tag ] = media[ this.tag ];

			// Fetch the query's attachments, and then break ties from the
			// query to allow for sorting.
			selection.more().done( function() {
				// Break ties with the query.
				selection.props.set({ query: false });
				selection.unmirror();
				selection.props.unset('orderby');
			});
			return selection;
		},
		
		render: function() {
			var attrs = _.clone( this.model.attributes );
			this.$el.html( this.template( attrs ) );
			return this;	
		}
		
	} );
	
	GalleryToolbar = views.gallery.Toolbar = Toolbar.extend( {
		template: wp.template( 'cedmc-gallery-toolbar' ),
		tag: 'gallery',
		type: 'image'
	} );
	
	PlaylistToolbar = views.playlist.Toolbar = Toolbar.extend( {
		template: wp.template( 'cedmc-playlist-toolbar' ),
		tag: 'playlist',
		
		initialize: function( options ) {
			Toolbar.prototype.initialize.apply(this);
			this.type = this.model.type || 'audio';
			this.listenTo( this.model, 'change:type', function() {
				this.type = this.model.type;
			} );
		},
		
		events: function() {
			return _.extend( {}, Toolbar.prototype.events.apply(this),
				 {
					'change .type': function( event ) {
						this.model.save( { type: this.$el.find( '.type' ).val() } );
					}
				}
			);
		}
	} );
	
	GalleryPreview = views.gallery.Preview = wp.Backbone.View.extend( {
		template: wp.template( 'editor-gallery' ),
		initialize: function( options ) {
			this.listenTo( this.model, 'change', this.render );
		},
		render: function() {
			var attrs = _.clone( this.model.attributes ),
				attachments = false,
				options,
				self = this;
				
			if( attrs.ids.length == 0 ) {
				return self.$el.html( '' );
				return;
			}
				
			attrs.ids = attrs.ids.toString();
			
			this.attachments = wp.media.gallery.attachments( new wp.shortcode({
				tag:    'gallery',
				attrs:  attrs,
				type:   'single'
			}) );

			this.attachments.more().done( function() {				
				if ( self.attachments.length ) {
					attachments = self.attachments.toJSON();
		
					_.each( attachments, function( attachment ) {
						if ( attachment.sizes ) {
							if ( attachment.sizes['gallery_preview'] ) {
								attachment.thumbnail = attachment.sizes['gallery_preview'];
							} else if ( attachment.sizes.full ) {
								attachment.thumbnail = attachment.sizes.full;
							}
						}
					} );
				} 
				
				options = {
					attachments: attachments,
					columns: attrs.columns ? parseInt( attrs.columns, 10 ) : 3
				};
				
				return self.$el.html( self.template( options ) );
			} );			
		}
	} );
	
	PlaylistPreview = views.playlist.Preview = wp.Backbone.View.extend( {
		template: wp.template( 'cedmc-playlist' ),
		initialize: function( options ) {
			this.listenTo( this.model, 'change', this.render );
		},
		
		render: function() {
			var data = _.clone( this.model.attributes ),
				model = wp.media.playlist,
				options,
				attachments,
				tracks = [],
				self = this;

			_.each( model.defaults, function( value, key ) {
				data[ key ] = model.coerce( data, key );
			} );
			
			if( 0 === data.ids.length ) {
				self.$el.html( '' );
				return;
			}
			
			data.ids = data.ids.toString();

			options = {
				type: data.type,
				style: data.style,
				tracklist: data.tracklist,
				tracknumbers: data.tracknumbers,
				images: data.images,
				artists: data.artists
			};
			
			
			this.attachments = wp.media.playlist.attachments( new wp.shortcode({
				tag:    'playlist',
				attrs:  data,
				type:   'single'
			} ) );
			
			this.attachments.more().done( function() {
				if ( ! self.attachments.length ) {
					self.$el.html( self.template( options ) );
				}
	
				attachments = self.attachments.toJSON();
	
				_.each( attachments, function( attachment ) {
					var size = {}, resize = {}, track = {
						src : attachment.url,
						type : attachment.mime,
						title : attachment.title,
						caption : attachment.caption,
						description : attachment.description,
						meta : attachment.meta
					};

					if ( 'video' === data.type ) {
						size.width = attachment.width;
						size.height = attachment.height;
						if ( wp.media.view.settings.contentWidth ) {
							resize.width = wp.media.view.settings.contentWidth - 22;
							resize.height = Math.ceil( ( size.height * resize.width ) / size.width );
							if ( ! options.width ) {
								options.width = resize.width;
								options.height = resize.height;
							}
						} else {
							if ( ! options.width ) {
								options.width = attachment.width;
								options.height = attachment.height;
							}
						}
						track.dimensions = {
							original : size,
							resized : _.isEmpty( resize ) ? size : resize
						};
					} else {
							options.width = 400;
					}

					track.image = attachment.image;
					track.thumb = attachment.thumb;

					tracks.push( track );
				} );
	
				options.tracks = tracks;
				self.$el.html( self.template( options ) );
				new WPPlaylistView({ metadata: options, el:  self.$el.find( '.wp-playlist' ).get(0)});
			} );
		}
	} );
	
	GalleryFeatured = views.gallery.Featured = wp.Backbone.View.extend( {
		template: {
			empty: wp.template( 'cedmc-featured-empty' ),
			set: wp.template( 'cedmc-featured-set' )
		},
		
		initialize: function( options ) {
			this.render();
			this.listenTo( this.model, 'change:ids', function() {
				this.render();
			} )
		},
		
		render: function() {
			var featured = this.model.get( 'featured_id' ), 
				ids = this.model.get( 'ids' ), 
				image = this.image, 
				self = this,
				options = {};

			if(  ids.length == 0 ) {
				this.$el.html(' ');
				return;	
			}
			
			if( featured ) {
				if( ! image ) {
					image = wp.media.attachment( featured );
					image.fetch().done(function() {
						self.image = image;
						options.url = image.get('url');
						self.$el.html( self.template.set( options ) );
					} );		
				} else {
					options.url = image.get('url');
					this.$el.html( this.template.set( options ) );
				}
				
			} else {
				this.$el.html( this.template.empty( options ) );
			}
			
		},
		
		events: {
			'click .set' : function() {
				this.frame( ).open();
				return false;	
			}, 
			'click .remove': function() {
				var self = this;
				this.model.save( { 'featured_id': '' } ).done( function() {
					self.image = '';
					self.render();
				} );
				
				return false;
			}
		},
		frame: function() {
			var self = this, 
				ids = this.model.get( 'ids' );
			wp.media.view.settings.post.featuredImageId = this.model.get( 'featured_id' );

			if ( this._frame ) {
				this._frame.dispose();
			}

			this._frame = wp.media({
				state: 'featured-image',
				states: [ new wp.media.controller.FeaturedImage( { 
					library:  wp.media.query({ type: 'image', 'post__in': ids  })
				} ) , new wp.media.controller.EditImage() ]
			});

			this._frame.on( 'toolbar:create:featured-image', function( toolbar ) {
				/**
				 * @this wp.media.view.MediaFrame.Select
				 */
				this.createSelectToolbar( toolbar, {
					text: wp.media.view.l10n.setFeaturedImage
				});
			}, this._frame );

			this._frame.on( 'content:render:edit-image', function() {
				var selection = this.state('featured-image').get('selection'),
					view = new wp.media.view.EditImage( { model: selection.single(), controller: this } ).render();

				this.content.set( view );

				// after bringing in the frame, load the actual editor via an ajax call
				view.loadEditor();

			}, this._frame );

			this._frame.state('featured-image').on( 'select', function() {
				var selection = this.get('selection').single();
				self.model.save( {'featured_id': selection.get( 'id' ) } ).done( function() {
					self.image = selection;
					self.render();
				} );
			} );
			return this._frame;
		},
	} );
} )( jQuery );