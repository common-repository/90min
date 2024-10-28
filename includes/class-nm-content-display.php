<?php
/* In case there's a need to upload images instead:
	<http://theme.fm/2011/10/how-to-upload-media-via-url-programmatically-in-wordpress-2657/>
	<http://codex.wordpress.org/Function_Reference/media_sideload_image>
*/

class NM_Content_Display {
	static $_current_post_id;

	static function setup() {
		$c = __CLASS__;
		
		add_filter( 'get_post_metadata', array( $c, 'has_post_thumbnail' ), 10, 4 );
		add_filter( 'post_thumbnail_html', array( $c, 'filter_post_thumbnail_html' ), 10, 5 );
		//add_filter( 'admin_post_thumbnail_html', array( $c, 'filter_admin_post_thumbnail_html' ), 10, 2 );
		/* To Change Duplicate Author Name*/
		add_filter( 'the_author', array( $c, 'guest_author_name' ) );
		add_filter( 'get_the_author_display_name', array( $c, 'guest_author_name' ) );
		// add_filter( 'post_class', array( $c, 'filter_post_class' ), 10, 3 );
		
		add_action('wp_head', array( $c, 'noindex_post_page' ));
	}

	static function filter_post_class( $classes, $class, $post_id ) {
		if ( ! NM_Data_Bridge::is_90min_post( $post_id ) )
			return $classes;

		// enqueue the main JS file
		wp_enqueue_script( '90min-main-embed', 'http://static.90min.com/assets/production/embed/v1.js', false, false, true );

		return $classes;
	}

	static function filter_admin_post_thumbnail_html( $content, $post_id ) {
		if ( ! NM_Data_Bridge::is_90min_post( $post_id ) )
			return $content;

		global $content_width, $_wp_additional_image_sizes;

		$post_image_url = NM_Data_Bridge::get_90min_post_image( $post_id );
		$inner_img_format = '<img src="%s">';

		$style = 'border: 1px dotted #AAA; padding: 5px;';

		$content .= "<p class=\"nm-featured-image-wrap\" style=\"$style\">" 
			. 'Original 90min featured image: <br>'
			. sprintf($inner_img_format, $post_image_url) . '</p>';

		return $content;
	}

	static function filter_post_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
		// check if this is a 90min post
		if ( ! NM_Data_Bridge::is_90min_post( $post_id ) || $post_thumbnail_id !== true || is_singular() )
			return $html;

		$post_image_url = NM_Data_Bridge::get_90min_post_image( $post_id );
		$desired_thumbnail_size = self::get_image_size( $size );

		// if size not found or no image URL, stop this
		if ( ! $desired_thumbnail_size || empty( $desired_thumbnail_size['width'] ) || ! $post_image_url )
			return $html;

		$hwstring = image_hwstring($desired_thumbnail_size['width'], $desired_thumbnail_size['height']);

		$src = add_query_arg( array( 
			'width' => $desired_thumbnail_size['width'],
			'height' => 0,
		), $post_image_url );
		$size_class = $size;
        if ( is_array( $size_class ) ) {
        	$size_class = join( 'x', $size_class );
        }

		$default_attr = array(
			'src' => $src,
			'class' => "attachment-$size_class",
		);

		$attr = wp_parse_args($attr, $default_attr);

		$attr = apply_filters( '90min_attachment_image_attributes', $attr, $size, $post_id );

		$attr = array_map( 'esc_attr', $attr );
		$html = rtrim("<img $hwstring");
		foreach ( $attr as $name => $value ) {
			$html .= " $name=" . '"' . $value . '"';
		}
		$html .= ' />';

		return $html;
	}

	static function has_post_thumbnail( $empty, $object_id, $meta_key, $single ) {
		if ( is_admin() )
			return $empty;

		if ( '_thumbnail_id' != $meta_key )
			return $empty;
		
		if ( ! NM_Data_Bridge::is_90min_post( $object_id ) || is_singular() )
			return $empty;
		
		// now check if by settings we should show the featured image
		if ( '1' !== nm_get_option( 'update-featured-image' ) )
			return $empty;


		// unwire
		remove_filter( 'get_post_metadata', array( __CLASS__, 'has_post_thumbnail' ), 10, 4 );

		// check for real if this post has a featured image
		$featured_image = get_post_thumbnail_id( $object_id );

		// bring back the wire
		add_filter( 'get_post_metadata', array( __CLASS__, 'has_post_thumbnail' ), 10, 4 );


		if ( is_numeric( $featured_image ) && $featured_image > 0 ) {
			return $featured_image;
		} else {
			// this post has a 90min featured image
			return true;
		}
	}

	static function get_image_size( $size ) {
		global $_wp_additional_image_sizes;

		return has_image_size($size) ? $_wp_additional_image_sizes[ $size ] : false;
	}
	
	/* To Change Duplicate Author Name*/
	static function guest_author_name( $name ) {
		global $post;
		$additionals = get_post_meta( $post->ID, '90min_post_original_ref', true );
		
		$author = $additionals->author_name; //get_post_meta( $post->ID, 'guest-author', true );
		
		if ( $author )
		$name = $author;
		
		return $name;
	}
	
	static function noindex_post_page()
	{
		global $post;
		if (!is_home() && NM_Data_Bridge::is_90min_post( $post->ID ) )	
		{
			$index_nm_option=nm_get_option( 'index-nm-post' );
			if($index_nm_option)
				echo '<meta name="robots" content="noindex" />', "\n";
		}
	}
	
}