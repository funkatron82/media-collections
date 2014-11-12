<?php

class CED_Term_Namespace {
	public $taxonomy, $prefix;
	function __construct( $taxonomy, $prefix ) {
		$this->taxonomy = $taxonomy;
		$this->prefix = $prefix;
		
		//Hooks
		add_filter( 'request', array( $this, 'request' ) );
		add_filter( 'term_link', array( $this, 'term_link' ), 10, 3 );
		add_filter( 'get_' . $this->taxonomy, array( $this, 'get_term' ) );
		add_filter( 'get_terms', array( $this, 'get_terms' ), 10, 3 );
		add_filter( 'wp_get_object_terms', array( $this, 'get_object_terms' ) );
	}
	
	function request( $qvs ) {
		if ( ! isset( $qvs[ $this->taxonomy ] ) )
			return $qvs;
		$qvs[ $this->taxonomy ] = $this->prefix . $qvs[ $this->taxonomy ];
		$tax = get_taxonomy( $this->taxonomy );
		if ( ! is_admin() )
			$qvs['post_type'] = $tax->object_type;
		return $qvs;
	}
	
	function term_link( $link, $term, $taxonomy ) {
		global $wp_rewrite;
		if ( $this->taxonomy != $taxonomy )
			return $link;
		if ( $wp_rewrite->get_extra_permastruct( $taxonomy ) ) {
			return str_replace( "/{$term->slug}", '/' . str_replace( $this->prefix, '', $term->slug ), $link );
		} else {
			$link = remove_query_arg( $this->taxonomy, $link );
			return add_query_arg( $this->taxonomy, str_replace( $this->prefix, '', $term->slug ), $link );
		}
	}
	
	function get_term( $term ) {
		if ( isset( $term->slug ) ) {
			$term->slug = str_replace( $this->prefix, '', $term->slug );
		}
		return $term;
	}
	
	function get_terms( $terms, $taxonomies, $args ) {
		if ( in_array( $this->taxonomy, (array) $taxonomies ) ) {
			foreach ( (array) $terms as $order => $term ) {
				if ( isset( $term->taxonomy ) && $this->taxonomy == $term->taxonomy ) {
					$terms[$order]->slug = str_replace( $this->prefix, '', $term->slug );
				}
			}			
		}
		return $terms;
	}
	
	function get_object_terms( $terms ) {
		foreach ( (array) $terms as $order => $term ) {
			if ( isset( $term->taxonomy ) && $this->taxonomy == $term->taxonomy ) {
				$terms[$order]->slug = str_replace( $this->prefix, '', $term->slug );
			}
		}
		return $terms;
	}
}