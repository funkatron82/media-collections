jQuery( document ).ready( function( $ ) {
	var id = $( '[name=post_ID]' ).val(),
		gallery = new ced.mediaCollections.models.Gallery( { id: id } );
	gallery.fetch().done( function(){
		new ced.mediaCollections.views.Gallery( { model: gallery, el: '#cedmc-main' } );
		new ced.mediaCollections.views.GalleryFeatured({el: '#cedmc-featured', model:gallery });
	} );
} );