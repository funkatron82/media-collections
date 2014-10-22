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
600	                        var data = this.shortcode.attrs.named,
601	                                model = wp.media.playlist,
602	                                options,
603	                                attachments,
604	                                tracks = [];
605	
606	                        // Don't render errors while still fetching attachments
607	                        if ( this.dfd && 'pending' === this.dfd.state() && ! this.attachments.length ) {
608	                                return;
609	                        }
610	
611	                        _.each( model.defaults, function( value, key ) {
612	                                data[ key ] = model.coerce( data, key );
613	                        });
614	
615	                        options = {
616	                                type: data.type,
617	                                style: data.style,
618	                                tracklist: data.tracklist,
619	                                tracknumbers: data.tracknumbers,
620	                                images: data.images,
621	                                artists: data.artists
622	                        };
623	
624	                        if ( ! this.attachments.length ) {
625	                                return this.template( options );
626	                        }
627	
628	                        attachments = this.attachments.toJSON();
629	
630	                        _.each( attachments, function( attachment ) {
631	                                var size = {}, resize = {}, track = {
632	                                        src : attachment.url,
633	                                        type : attachment.mime,
634	                                        title : attachment.title,
635	                                        caption : attachment.caption,
636	                                        description : attachment.description,
637	                                        meta : attachment.meta
638	                                };
639	
640	                                if ( 'video' === data.type ) {
641	                                        size.width = attachment.width;
642	                                        size.height = attachment.height;
643	                                        if ( media.view.settings.contentWidth ) {
644	                                                resize.width = media.view.settings.contentWidth - 22;
645	                                                resize.height = Math.ceil( ( size.height * resize.width ) / size.width );
646	                                                if ( ! options.width ) {
647	                                                        options.width = resize.width;
648	                                                        options.height = resize.height;
649	                                                }
650	                                        } else {
651	                                                if ( ! options.width ) {
652	                                                        options.width = attachment.width;
653	                                                        options.height = attachment.height;
654	                                                }
655	                                        }
656	                                        track.dimensions = {
657	                                                original : size,
658	                                                resized : _.isEmpty( resize ) ? size : resize
659	                                        };
660	                                } else {
661	                                        options.width = 400;
662	                                }
663	
664	                                track.image = attachment.image;
665	                                track.thumb = attachment.thumb;
666	
667	                                tracks.push( track );
668	                        } );
669	
670	                        options.tracks = tracks;
671	                        this.data = options;
672	
673	                        return this.template( options );
674	                },
675	
676	                unbind: function() {
677	                        this.unsetPlayers();
678	                }
679	        });


 getHtml: function() {
293	                                var attrs = this.shortcode.attrs.named,
294	                                        attachments = false,
295	                                        options;
296	
297	                                // Don't render errors while still fetching attachments
298	                                if ( this.dfd && 'pending' === this.dfd.state() && ! this.attachments.length ) {
299	                                        return;
300	                                }
301	
302	                                if ( this.attachments.length ) {
303	                                        attachments = this.attachments.toJSON();
304	
305	                                        _.each( attachments, function( attachment ) {
306	                                                if ( attachment.sizes ) {
307	                                                        if ( attachment.sizes.thumbnail ) {
308	                                                                attachment.thumbnail = attachment.sizes.thumbnail;
309	                                                        } else if ( attachment.sizes.full ) {
310	                                                                attachment.thumbnail = attachment.sizes.full;
311	                                                        }
312	                                                }
313	                                        } );
314	                                }
315	
316	                                options = {
317	                                        attachments: attachments,
318	                                        columns: attrs.columns ? parseInt( attrs.columns, 10 ) : 3
319	                                };
320	
321	                                return this.template( options );
322	
323	                        }
324	                }),
325	