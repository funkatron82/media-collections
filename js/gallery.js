jQuery( document ).ready( function( $ ) {
	var gallery = new ced.mediaCollections.models.Gallery(galleryData);
	var galleryToolbar = new ced.mediaCollections.views.gallery.Toolbar({el: '#cedmc-toolbar', model:gallery}).render();
	var galleryPreview = new ced.mediaCollections.views.gallery.Preview({el: '#cedmc-preview', model:gallery}).render();
} );