<?php
/*
================================================================================
	remove some wp_header information
================================================================================
*/
remove_action('wp_head', 'feed_links');
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wp_shortlink_wp_head');

/*
================================================================================
	hide admin bar
================================================================================
*/
add_filter('show_admin_bar', '__return_false');

/*
================================================================================
	head title
================================================================================
*/
function get_head_title() {
	$title = '';
	if ( is_singular() )
		$title .= wp_title( '|', false, 'right' );
	$title .= get_bloginfo( 'name' );
	return $title;
}

function head_title() {
	echo esc_html( get_head_title() );
}

/*
================================================================================
	meta description
================================================================================
*/
function get_meta_description() {
	if ( is_singular() && have_posts() ):
		while(have_posts()): the_post();
			$description = mb_substr( get_the_excerpt(), 0, 100 );
		endwhile;
	else:
		$description = get_bloginfo( 'description' );
	endif;
	return $description;
}

function meta_description() {
	echo esc_attr( get_meta_description() );
}

/*
================================================================================
	ogp
================================================================================
*/
function get_og( $arg ) {

	if ( $arg === 'type' ):
		$og = is_front_page() ? 'website' : 'article';

	elseif ( $arg === 'url' ):
		$og = is_404() ? home_url() : set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

	elseif ( $arg === 'title' ):
		if ( is_singular() && have_posts() ):
			while( have_posts() ): the_post();
				$title = the_title( '', '', false );
			endwhile;
		else:
			$title = get_bloginfo( 'name' );
		endif;
		$og = $title;

	elseif ( $arg === 'description' ):
		$og = get_meta_description();

	elseif ( $arg === 'image' ):
		global $post;
		$post_content   = ( ! is_archive() && ! is_front_page() && ! is_home() ) ? $post->post_content : null;
		$search_pattern = '/<img.*?src=(["\'])(.+?)\1.*?>/i';
		if ( has_post_thumbnail() && ! is_archive() && ! is_front_page() && ! is_home() ):
			$image_id   = get_post_thumbnail_id();
			$image      = wp_get_attachment_image_src( $image_id, 'full' );
			$og = $image[0];
		elseif ( preg_match( $search_pattern, $post_content, $imgurl ) && ! is_archive() && ! is_front_page() && ! is_home() ):
			$og = $imgurl[2];
		else:
			$og = get_home_url() . '/assets/images/og-default.png';
		endif;
	else:
		$og = '';
	endif;

	return $og;

}

function og( $arg ) {
	echo esc_attr( get_og( $arg ) );
}

/*
================================================================================
	WordPress HTML Generator body class
================================================================================
*/
function htmlgen_body_class( $classes ) {

	global $wp_query, $wpdb;

	$classes = array();

	if ( is_front_page() || is_home() )
		$classes[] = 'home';
	if ( is_archive() )
		$classes[] = 'archive';
	if ( is_date() )
		$classes[] = 'date';
	if ( is_search() ) {
		$classes[] = 'search';
		$classes[] = $wp_query->posts ? 'search-results' : 'search-no-results';
	}
	if ( is_paged() )
		$classes[] = 'paged';
	if ( is_attachment() )
		$classes[] = 'attachment';
	if ( is_404() )
		$classes[] = 'error404';

	if ( is_single() ) {
		$post_id = $wp_query->get_queried_object_id();
		$post = $wp_query->get_queried_object();

		$classes[] = $post->post_name;
		if ( isset( $post->post_type ) ) {

			// Post Format
			if ( post_type_supports( $post->post_type, 'post-formats' ) ) {
				$post_format = get_post_format( $post->ID );

				if ( $post_format && !is_wp_error($post_format) )
					$classes[] = 'format-' . sanitize_html_class( $post_format );
			}
		}
	} elseif ( is_archive() ) {
		if ( is_post_type_archive() ) {
			$post_type = get_query_var( 'post_type' );
			if ( is_array( $post_type ) )
				$post_type = reset( $post_type );
			$classes[] = 'archive-' . sanitize_html_class( $post_type );
		} else if ( is_author() ) {
			$author = $wp_query->get_queried_object();
			$classes[] = 'author';
			if ( isset( $author->user_nicename ) ) {
				$classes[] = 'author-' . sanitize_html_class( $author->user_nicename, $author->ID );
				$classes[] = 'author-' . $author->ID;
			}
		} elseif ( is_category() ) {
			$cat = $wp_query->get_queried_object();
			$classes[] = 'category';
			if ( isset( $cat->term_id ) ) {
				$classes[] = 'category-' . sanitize_html_class( $cat->slug, $cat->term_id );
				$classes[] = 'category-' . $cat->term_id;
			}
		} elseif ( is_tag() ) {
			$tags = $wp_query->get_queried_object();
			$classes[] = 'tag';
			if ( isset( $tags->term_id ) ) {
				$classes[] = 'tag-' . sanitize_html_class( $tags->slug, $tags->term_id );
				$classes[] = 'tag-' . $tags->term_id;
			}
		} elseif ( is_tax() ) {
			$term = $wp_query->get_queried_object();
			if ( isset( $term->term_id ) ) {
				$classes[] = 'tax-' . sanitize_html_class( $term->taxonomy );
				$classes[] = 'term-' . sanitize_html_class( $term->slug, $term->term_id );
				$classes[] = 'term-' . $term->term_id;
			}
		}
	} elseif ( is_page() ) {

		$page_id = $wp_query->get_queried_object_id();

		$post = get_post($page_id);

		$classes[] = $post->post_name;
		$parent_id = $post->post_parent;
		while ( $parent_id ) {
			$parent = get_post($parent_id);
			$classes[] = 'parent-' . $parent->post_name;
			$parent_id = $parent->post_parent;
		}
		if ( is_page_template() ) {
			$classes[] = 'template-' . sanitize_html_class( str_replace( '.', '-', get_page_template_slug( $page_id ) ) );
		}
	}

	$page = $wp_query->get( 'page' );

	if ( !$page || $page < 2)
		$page = $wp_query->get( 'paged' );

	if ( $page && $page > 1 ) {
		$classes[] = 'paged-' . $page;

		if ( is_single() )
			$classes[] = 'paged-' . $page;
		elseif ( is_page() )
			$classes[] = 'paged-' . $page;
		elseif ( is_category() )
			$classes[] = 'category-paged-' . $page;
		elseif ( is_tag() )
			$classes[] = 'tag-paged-' . $page;
		elseif ( is_date() )
			$classes[] = 'date-paged-' . $page;
		elseif ( is_author() )
			$classes[] = 'author-paged-' . $page;
		elseif ( is_search() )
			$classes[] = 'search-paged-' . $page;
		elseif ( is_post_type_archive() )
			$classes[] = 'archive-paged-' . $page;
	}

	$classes = array_map( 'esc_attr', $classes );

	return $classes;
}
add_filter( 'body_class', 'htmlgen_body_class' );
