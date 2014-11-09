jQuery( document ).ready( function( $ ) {
	var id = $( '#post_ID' ).val(), gallery = new ced.mediaCollections.models.Gallery( { id: id } ), galleryToolbar, galleryPreview;
	gallery.fetch().done( function( data ) {
		galleryToolbar = new ced.mediaCollections.views.gallery.Toolbar({el: '#cedmc-toolbar', model:gallery}).render();
		galleryPreview = new ced.mediaCollections.views.gallery.Preview({el: '#cedmc-preview', model:gallery}).render();
	} );
} );