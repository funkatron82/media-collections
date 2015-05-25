window.ced = window.ced || {};

( function($){
	var views = ced.mediaCollections.views, EditButton, GalleryPreview, PlaylistPreview, GalleryFeatured, Status, Gallery, Playlist, Collection;
	
	_.extend( views, { mediaCollection: {}, gallery: {}, playlist: {} } );
	
	Collection = views.Collection = wp.Backbone.View.extend( {
		template: wp.template( 'cedmc-collection' ),
		initialize: function( options ){
			this.render();
		},
		render: function() {
			var attrs = _.clone( this.model.attributes ),
				button = new EditButton( { model: this.model } ),
				status = new Status( { model: this.model } );
			this.$el.html( this.template( attrs ) );
			this.$el.find( '.cedmc-primary-bar' ).append( status.el );
			this.$el.find( '.cedmc-secondary-bar' ).append( button.el );
			return this;	
		}
	} );
	
	Gallery = views.Gallery = Collection.extend( {
		render: function() {
			Collection.prototype.render.call( this );
			var preview = new GalleryPreview( { model: this.model } );
			this.$el.find( '#cedmc-preview' ).append( preview.el );
			return this;	
		}
	} );
	
	Playlist = views.Playlist = Collection.extend( {
		render: function() {
			Collection.prototype.render.call( this );
			var preview = new PlaylistPreview( { model: this.model, el: this.$el.find( '#cedmc-preview' ) } ),
				selector = new PlaylistTypeSelect( { model: this.model } );
			this.$el.find( '.cedmc-secondary-bar' ).append( selector.el );
			return this;	
		}
	} );
	
	Status = views.Status = wp.Backbone.View.extend( {
		template: wp.template( 'cedmc-status' ),
		initialize: function( options ){
			this.listenTo( this.model, 'change:ids', function(){
				this.render();
			} );
			
			this.render();
		},
		
		render: function() {
			var attrs = _.clone( this.model.attributes );
			this.$el.html( this.template( attrs ) );
			return this;	
		}
	} );
	
	EditButton = views.EditButton = wp.Backbone.View.extend( {
		tagName: 'a',
		
		className: 'update button',
		
		template: wp.template( 'cedmc-edit-button' ),
		
		initialize: function( options ) {
			this.listenTo( this.model, 'change:ids', this.render );
			this.render();
		},
		
		events: { 
			'click' : 'update'	
		},
		
		update: function() {
			var frame = this.frame(),
				tag = this.model.get( 'type' );
			this.listenTo( frame, 'update', function( media ) {
				var shortcode = wp.media[tag].shortcode( media ),
					attrs = _.defaults( shortcode.attrs.named, this.model.defaults );
				attrs = _.omit( attrs, ['id', 'type'] );
				this.model.save( attrs );
			} );
		},
		
		frame: function() {
			var tag = this.model.get( 'type' ), 
				type = this.model.get('playlistType'),
				self = this, 
				state, 
				selection, 
				count = this.model.get( 'ids' ).length || 0;
			// Destroy the previous collection frame.
			if ( this._frame ) {
				this._frame.dispose();
			}
			
			if ( type && 'video' === type ) {
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
				tag = this.model.get( 'type' ),  
				attrs = this.model.toJSON(),
				media,
				selection;
			attrs.ids = attrs.ids.toString();
			media = wp.media[tag].attachments( new wp.shortcode({
				tag:    tag,
				attrs:  attrs,
				type:   'single'
			}) );
			
			
			selection = new wp.media.model.Selection( media.models, {
				props:    media.props.toJSON(),
				multiple: true
			});

			selection[ tag ] = media[ tag ];

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
	
	PlaylistTypeSelect = views.PlaylistTypeSelect = wp.Backbone.View.extend( {
		tagName: 'select',
		template: wp.template('cedmc-playlist-type'),
		initialize: function( options ){
			this.render();
		},
		events: {
			'change': 'change'	
		},
		
		render: function() {
			var attrs = _.clone( this.model.attributes );
			this.$el.html( this.template( attrs ) );
			this.$el.val( this.model.get( 'playlistType' ) )
			return this;	
		},
				
		change: function( event ) {
			this.model.save( { 'playlistType': this.$el.val() } );
		}
	} );

	
	GalleryPreview = views.gallery.Preview = wp.Backbone.View.extend( {
		template: wp.template( 'editor-gallery' ),
		initialize: function( options ) {
			this.listenTo( this.model, 'change', this.render );
			this.render();
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
			
			return this;			
		}
	} );
	
	PlaylistPreview = views.playlist.Preview = wp.Backbone.View.extend( {
		template: wp.template( 'cedmc-playlist' ),
		initialize: function( options ) {
			this.listenTo( this.model, 'change', this.render );
			this.render();
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
				type: data.playlistType,
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

					if ( 'video' === data.playlistType ) {
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
	
	GalleryFeatured = views.GalleryFeatured = wp.Backbone.View.extend( {
		template: wp.template( 'cedmc-featured' ),
		
		initialize: function( options ) {
			var self = this,
				featured = this.model.get( 'featuredId' );
							
			if( featured ) {
				this.image = wp.media.attachment( featured );
				this.image.fetch().done( function() {
					self.render();					
				} );	
			}
			
			this.listenTo( this.model, 'change:ids', function() {
				if(  this.model.get( 'ids' ).length == 0 ) {
					this.$el.html(' ');
				}
			} );
			
			this.listenTo( this.model, 'change:featuredId', function() {
				var self = this,
					featured = this.model.get( 'featuredId' );
				if( featured ) {
					this.image = wp.media.attachment( featured );
					this.image.fetch().done( function() {
						self.render();					
					} );
				}
			} );
		},
		
		render: function() {
			var featured = this.model.get( 'featuredId' ), 
				image = this.image, 
				options = {};
			
			if( featured ) {
				options.url = image.get('url');	
			} 
			
			this.$el.html( this.template( options ) );	
			
			return this;		
		},
		
		events: {
			'click .set' : function() {
				this.frame( ).open();
				return false;	
			}, 
			'click .remove': function() {
				var self = this;
				this.model.save( { 'featuredId': '' } ).done( function() {
					self.image = '';
					self.render();
				} );
				
				return false;
			}
		},
		
		frame: function() {
			var self = this, 
				ids = this.model.get( 'ids' );
			wp.media.view.settings.post.featuredImageId = this.model.get( 'featuredId' );

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
				self.model.save( {'featuredId': selection.get( 'id' ) } );
			} );
			return this._frame;
		},
	} );
} )( jQuery );