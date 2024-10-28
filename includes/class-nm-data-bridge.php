<?php

class NM_Data_Bridge {
	public static function leagues() {
		$decoded = NM_Util::read_json( 'teams_leagues_categories.json' );

		if ( !$decoded )
			return false;


	}

	/**
	 * This will process the feed and insert posts
	 */
	static function process_feed( $feed ) {
		if ( empty($feed) || !is_array($feed) )
			return;

		$feed = apply_filters( '90min_feed_process_before', $feed );
		$feed_count_inserted = 0;

		foreach ( $feed as $feed_post ) {
			$post_insert_result = self::insert_post( $feed_post );

			// if post inserted raise counter
			if ( $post_insert_result )
				$feed_count_inserted++;
		}

		do_action( '90min_feed_process_after', $feed );

		return $feed_count_inserted;
	}

	static function insert_post( $feed_post ) {
		if ( !isset( $feed_post->article ) || !is_object($feed_post->article) )
			return false;

		$post = $feed_post->article;
		
		// first make sure this post doesn't exist yet.
		if ( self::post_exists( $post->id ) )
			return false;

		$selected_taxonomy = nm_get_option( 'tagging-tax' );
		$selected_taxonomy = taxonomy_exists( $selected_taxonomy ) ? $selected_taxonomy : 'post_tag';

		$found_tags = !empty($post->categories) ? $post->categories : array();
		/* to change data-show-author="1"*/
		$show_author_name = nm_get_option( 'show-author-name' );
		$embed_code = $post->embed_code;
		if($show_author_name)
		{
			$embed_code = str_replace("data-show-author=''","data-show-author='1'",$embed_code);
		}
		else
		{
			$embed_code =str_replace("data-show-author=''","",$embed_code);
		}
		$post->embed_code = $embed_code;
		$post_args = array(
			'post_title' => $post->title,
			'post_content' => self::post_content($post),
			
			'post_type' => MC_90min_Settings_Controls::get_option( 'post-type' ),
			'post_status' => MC_90min_Settings_Controls::get_option( 'post-status' ),
			'post_author' => MC_90min_Settings_Controls::get_option( 'post-author' ),
		);
		
		$embed_code = $post->embed_code;
		// find all related term IDs
		$_term_ids = array();

		if ( 'category' == $selected_taxonomy ) {
			foreach ($found_tags as $found_tag) {
				if ( !$term_info = term_exists($found_tag, $selected_taxonomy) ) {
		            // Skip if a non-existent term ID is passed.
		            if ( is_int($found_tag) )
		                continue;
		            $term_info = wp_insert_term($found_tag, $selected_taxonomy);
		        }
		        
		        // if was found
		        if ( is_array($term_info) && isset($term_info['term_id']) ) {
		        	$_term_ids[] = $term_info['term_id'];
		        }
			}
		}


		if ( 'category' == $selected_taxonomy ) {
			if ( !empty($_term_ids) ) {
				$post_args['post_category'] = $_term_ids;
			} else {
				$post_args['post_category'] = array( 1 ); // uncategorized
			}
		} else {
			$post_args['tax_input'][ $selected_taxonomy ] = $found_tags;
		}

		$new_post_id = wp_insert_post( apply_filters( '90min_pre_insert_post', $post_args, $post ) );

		
		
		// insert some metadata for debug later on
		add_post_meta( $new_post_id, '90min_post_original_ref', $post );
		$upload_dir = wp_upload_dir();
		define('DIRECTORY', $upload_dir['path']);
		$content = file_get_contents($post->image_url);
		$img_name = rand().'.jpg';
		$image = DIRECTORY.'/'.$img_name;
		file_put_contents($image, $content);
		//$image = 'http://23.235.208.54/~sources/90min/uploads/'.$imagename ;
		
		//Get the remote image and save to uploads directory
		$wp_filetype = wp_check_filetype( $img_name , null );
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => preg_replace( '/\.[^.]+$/', '', $img_name ),
			'post_content' => '',
			'post_status' => 'inherit'
		);

			//require for wp_generate_attachment_metadata which generates image related meta-data also creates thumbs
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$attach_id = wp_insert_attachment( $attachment, $upload_dir['path'].'/'.$img_name, $new_post_id );
			//Generate post thumbnail of different sizes.
			$attach_data = wp_generate_attachment_metadata( $attach_id , $upload_dir['path'].'/'.$img_name );
			wp_update_attachment_metadata( $attach_id,  $attach_data );
			//Set as featured image.
			delete_post_meta( $new_post_id, '_thumbnail_id' );
			add_post_meta( $new_post_id , '_thumbnail_id' , $attach_id, true );
		
	
		add_post_meta( $new_post_id, '90min_post_id', $post->id );
		$specific_tags = nm_get_option( 'specific-tags' );
		$tags = $post->tags;
		//$tagsstring = implode(",",$tags);
		if($specific_tags)
		{
			wp_set_post_tags( $new_post_id, $tags, true );
		}
		
		
		if($post->description != '')
		{
			$metadesc = $post->description;
		}
		else
		{
			$metadesc = $post->title;
		}
		//Add Meta Description for Yoast SEO
		add_post_meta( $new_post_id, '_yoast_wpseo_metadesc', $metadesc );
		//Add Meta Description for SEO Wizard
		$settings = array();
		$settings['is_meta_description'] =1;
		$settings['meta_description'] = $metadesc;
		update_post_meta($new_post_id, 'wsw-settings', $settings);
		
		//Add Meta Description for All in One Seo Pack
		add_post_meta( $new_post_id, '_aioseop_description', $metadesc );
		
		//Add Meta Description for WP Meta SEO
		add_post_meta( $new_post_id, '_metaseo_metadesc', $metadesc );
		
		
		
		return $new_post_id;
	}

	static function post_exists( $nm_post_id ) {
		$found_posts = get_posts( array(
			'post_type' => MC_90min_Settings_Controls::get_option( 'post-type' ),
			'meta_query' => array(
				array(
					'key' => '90min_post_id',
					'value' => $nm_post_id,
				)
			)
		) );

		return !empty($found_posts);
	}

	static function post_content( $feed_post ) {
		return '[90min-post-embed]';
	}

	static function get_90min_post_image( $post_id = false ) {
		$additionals = get_post_meta( $post_id, '90min_post_original_ref', true );

		// if nothing in this key, or no image URL, finish up
		if ( ! $additionals || empty( $additionals->image_url ) )
			return false;

		return $additionals->image_url;
	}

	static function is_90min_post( $post_id = false ) {
		$post_id = $post_id ? $post_id : get_the_ID();

		if ( !$post_id )
			return false;

		$val = get_post_meta( $post_id, '90min_post_id', true );

		return $val ? $val : false;
	}
	
}