window.ced = window.ced || {};

(function($){
	var models = ced.models = ced.models || {},
		Post, Query, QueryVars, Posts;
	Post = models.Post = Backbone.Model.extend({
		defaults: {
			type: 'post',
			status: 'publish',
			title: 'Post'
		},
		/**
		 * Triggered when post details change
		 * Overrides Backbone.Model.sync
		 *
		 * @param {string} method
		 * @param {ced.models.Post} model
		 * @param {Object} [options={}]
		 *
		 * @returns {Promise}
		 */
		sync: function( method, model, options ) {
			// If the post does not yet have an `id`, return an instantly
			// rejected promise. Otherwise, all of our requests will fail.
			if ( _.isUndefined( this.id ) ) {
				if ( 'create' === method ) {
					var attributes = _.clone(this.attributes)
					if ( ! cedPostNonces || ! cedPostNonces.create ) {
						return $.Deferred().rejectWith( this ).promise();
					}
	
					options = options || {};
					options.context = this;
	
					// Set the action and ID.
					options.data = _.extend( options.data || {}, {
						action:  		'ced-create-post',
						_ajax_nonce:	cedPostNonces.create,
						attributes:		attributes
					});
	
					return wp.ajax.send( options );
				} else {
					return $.Deferred().rejectWith( this ).promise();
				}
			}

			// Overload the `read` request so Post.fetch() functions correctly.
			if ( 'read' === method ) {
				options = options || {};
				options.context = this;
				options.data = _.extend( options.data || {}, {
					action: 'ced-read-post',
					id: this.id
				});
				return wp.ajax.send( options );

			// Overload the `update` request so properties can be saved.
			} else if ( 'update' === method ) {
				// If we do not have the necessary nonce, fail immeditately.
				if ( ! this.get('nonces') || ! this.get('nonces').update ) {
					return $.Deferred().rejectWith( this ).promise();
				}

				options = options || {};
				options.context = this;

				// Set the action and ID.
				options.data = _.extend( options.data || {}, {
					action:  		'ced-update-post',
					id:				this.id,
					_ajax_nonce:	this.get('nonces').update,
				});

				// Record the values of the changed attributes.
				if ( model.hasChanged() ) {
					options.data.changes = {};

					_.each( model.changed, function( value, key ) {
						options.data.changes[ key ] = this.get( key );
					}, this );
				}

				return wp.ajax.send( options );

			// Overload the `delete` request so posts can be removed.
			// This will permanently delete an post.
			} else if ( 'delete' === method ) {
				options = options || {};

				if ( ! options.wait ) {
					this.destroyed = true;
				}

				options.context = this;
				options.data = _.extend( options.data || {}, {
					action:   'ced-delete-post',
					id:       this.id,
					_ajax_nonce: this.get('nonces')['delete']
				});

				return wp.ajax.send( options ).done( function() {
					this.destroyed = true;
				}).fail( function() {
					this.destroyed = false;
				});

			// Otherwise, fall back to `Backbone.sync()`.
			} else {
				/**
				 * Call `sync` directly on Backbone.Model
				 */
				return Backbone.Model.prototype.sync.apply( this, arguments );
			}
		},
		/**
		 * Convert date strings into Date objects.
		 *
		 * @param {Object} resp The raw response object, typically returned by fetch()
		 * @returns {Object} The modified response object, which is the attributes hash
		 *    to be set on the model.
		 */
		parse: function( resp ) {
			if ( ! resp ) {
				return resp;
			}

			resp.date = new Date( resp.date );
			resp.modified = new Date( resp.modified );
			return resp;
		}
	},
	
	{
		/**
		 * Create a new model on the static 'all' posts collection and return it.
		 *
		 * @static
		 * @param {Object} attrs
		 * @returns {ced.models.Post}
		 */
		create: function( attrs ) {
			var post =  new Post( attrs );
			post.save();
			return Post.cache( post.id, post );
		},
		/**
		 * Create a new model on the static 'all' posts collection and return it.
		 *
		 * If this function has already been called for the id,
		 * it returns the specified post.
		 *
		 * @static
		 * @param {string} id A string used to identify a model.
		 * @param {Backbone.Model|undefined} post
		 * @returns {ced.models.Post}
		 */
		cache: _.memoize( function( id, post ) {			
			if( ! post ) {
				post = new Post( {id: id} );
				post.fetch();
			}			
			return post;
		}),
		
		get: function( id ) {
			Post.cache( id );
		}
	});

	
	Posts = models.Posts = Backbone.Collection.extend( {
		model: Post,
		
		fill: function( ids ) {
			var self = this;
			_.each( ids, function( id, index, ids ){
				self.push( self.model.get(id) );
			} );
		},  
		
		getIds: function() {
			return this.pluck( id );	
		}
	} );

} (jQuery));