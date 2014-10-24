<script type="text/html" id="tmpl-cedmc-playlist">
		<# if ( data.tracks ) { #>
				<div class="wp-playlist wp-{{ data.type }}-playlist wp-playlist-{{ data.style }}">
						<# if ( 'audio' === data.type ){ #>
						<div class="wp-playlist-current-item"></div>
						<# } #>
						<{{ data.type }} controls="controls" preload="none" <#
								if ( data.width ) { #> width="{{ data.width }}"<# }
								#><# if ( data.height ) { #> height="{{ data.height }}"<# } #>></{{ data.type }}>
						<div class="wp-playlist-next"></div>
						<div class="wp-playlist-prev"></div>
				</div>
				<div class="wpview-overlay"></div>
		<# } else { #>
				<div class="wpview-error">
						<div class="dashicons dashicons-video-alt3"></div><p><?php _e( 'No items found.' ); ?></p>
				</div>
		<# } #>
</script>

 /**
594	                 * Set the data that will be used to compile the Underscore template,
595	                 *  compile the template, and then return it.
596	                 *
597	                 * @returns {string}
598	                 */
599	                getHtml: function() {
                        var data = this.shortcode.attrs.named,
                            model = wp.media.playlist,
	                                options,
	                                attachments,
	                                tracks = [];
	
	                        // Don't render errors while still fetching attachments
	                        if ( this.dfd && 'pending' === this.dfd.state() && ! this.attachments.length ) {
	                                return;
	                        }
	
	                        _.each( model.defaults, function( value, key ) {
	                                data[ key ] = model.coerce( data, key );
	                        });
	
	                        options = {
	                                type: data.type,
	                                style: data.style,
	                                tracklist: data.tracklist,
	                                tracknumbers: data.tracknumbers,
	                                images: data.images,
	                                artists: data.artists
	                        };
	
	                        if ( ! this.attachments.length ) {
	                                return this.template( options );
	                        }
	
	                        attachments = this.attachments.toJSON();
	
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
	                                        if ( media.view.settings.contentWidth ) {
	                                                resize.width = media.view.settings.contentWidth - 22;
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
	                        this.data = options;
	
	                        return this.template( options );
	                },
675	
676	                unbind: function() {
677	                        this.unsetPlayers();
678	                }
679	        });


 getHtml: function() {
var attrs = this.shortcode.attrs.named,
        attachments = false,
        options;

// Don't render errors while still fetching attachments
if ( this.dfd && 'pending' === this.dfd.state() && ! this.attachments.length ) {
        return;
}

if ( this.attachments.length ) {
        attachments = this.attachments.toJSON();

        _.each( attachments, function( attachment ) {
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
        attachments: attachments,
        columns: attrs.columns ? parseInt( attrs.columns, 10 ) : 3
};

return this.template( options );
	
	                        }
	                }),
	