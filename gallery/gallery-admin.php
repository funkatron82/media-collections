<?php

if( !class_exists( 'CED_Gallery_Type_Admin' )) :

class CED_Gallery_Type_Admin extends CED_Post_Type_Admin {
	public $post_type = 'gallery';
	
	function __construct() {
		parent::__construct();
		add_action( 'wp_ajax_cedmc_read_gallery', array( $this, 'read_gallery' ) );	
		add_action( 'wp_ajax_cedmc_update_gallery', array( $this, 'update_gallery' ) );	
		add_action( 'edit_form_after_title', array( $this, 'show_gallery')  );
		add_action( 'print_media_templates', array( $this, 'print_templates' ) );
		add_action( 'add_meta_boxes_' . $this->post_type, array( $this, 'add_featured') );
	}
	
	function add_featured( $gallery ) {
		add_meta_box( 
			'gallery-featured',
			__( 'Featured Image', 'cedmc' ),
			array( $this, 'render_featured' ),
			$this->post_type,
			'side',
			'default'
		);	
	}
	
	function render_featured( $post ) {
		?> <div id="cedmc-featured"></div>
        <?php
	}
	
	function add_columns( $columns ) {		
		return array_slice( $columns, 0, 2, true ) + array( 'media' => 'Media' ) + array_slice( $columns, 2, NULL, true );	
	}
	
	function manage_columns( $column, $id ) {
		global $post;
		if( 'media' === $column ) {
			$media = count( $post->media );
			if( ( $media > 0 ) ) {
				printf('<a href="%s" target="_new">%s %s</a>', admin_url( 'upload.php?media_in_gallery=' . $id ), $media,  _n( 'image', 'images', $media ) );
			} else {
				echo "—";	
			}
		}
		
	}
	
	function enqueue_scripts( $hook ) {
		if( $this->bail() || ( 'post.php' !== $hook && 'post-new.php' !== $hook ) ) {
			return;
		}
			
		wp_enqueue_script( 'cedmc-gallery', CEDMC_URL . 'js/gallery.js', array( 'backbone', 'cedmc-models', 'cedmc-views' ) );

		wp_enqueue_style( 'cedmc-gallery', CEDMC_URL . 'css/gallery.css', array( 'cedmc' ) );		
	}
	
	function show_gallery( $post ) {
		if( 'gallery' !== $post->post_type )	
			return;
		?>
        <div id="cedmc-main"></div>
        <label for="content"><strong>Description</strong>:</label>
		<?php
		wp_editor( $post->post_content, 'content', array( 'tinymce' => false, 'media_buttons' => false ) );
	}

	function print_templates() {
		?>
        <script type="text/html" id="tmpl-cedmc-featured"> 
			<# if( data.url ) { #>
				<a href="#" class="set"><img src="{{{ data.url }}}"></a>
				<p class="hide-if-no-js">
					<a href="#" class="remove"><?php _e( 'Remove featured image', 'cedmc' ); ?></a>
				</p>
			<# } else { #>
				<p class="hide-if-no-js">
					<a href="#" class="set"><?php _e( 'Set featured image', 'cedmc' ); ?></a>
				</p>
			<# } #>
		</script>
		<?php 
	}
	
	
}
endif;