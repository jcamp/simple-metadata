<?php

//Metadata for posts


defined ("ABSPATH") or die ("No script assholes!");

/**
 *	Function for creation of metabox to pick type of news post for proper Schema.org schema type
 */
function smd_add_post_type_meta () {

	add_meta_box (
		'smd_post_type', //Unique ID
		'Post Type', //Title
		'smd_render_article_type_meta', //Callback function
		'post', //for network settigns of AIOM
		'side', //Context
		'high' //priority
	);

}

/**
 *	Function for rendering post type metabox
 */
function smd_render_article_type_meta (){

	//creating nonce
	wp_nonce_field( basename( __FILE__ ), 'smd_render_post_type_meta' );

	//receiving type of post from option in metabox
	$post_type = esc_attr(get_post_meta (get_the_ID(), 'smd_post_type', true));
	$post_meta_types = array(
					'Article'					=> 'Article',
					'AdvertiserContentArticle'	=> 'Advertisement',
					'BlogPosting'				=> 'Blog Posting',
					'DiscussionForumPosting'	=> 'Discussion Forum Posting',
					'LiveBlogPosting'			=> 'Live Blog Posting',
					'Report'					=> 'Report',
					'SatiricalArticle' 			=> 'Satirical Article',
					'SocialMediaPosting'		=> 'Social Media Posting',
					'TechArticle'				=> 'Technology Article',
				  );
	?>
			<select  name="smd_post_type" id="smd_post_type">
				<?php
					foreach ($post_meta_types as $key => $value) {
						$selected = $post_type == $key ? 'selected' : '';
						if (!$post_type && 'Blog' == get_option('smd_website_blog_type') && 'BlogPosting' == $key){
							$selected = 'selected';
						}
						echo '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
					}
				?>
			</select>
	<?php
}

/**
 * Function for post saving/updating action
 */
function smd_save_post_type ($post_id, $post) {

	/* Verify the nonce before proceeding. */
	if ( !isset( $_POST['smd_render_post_type_meta'] ) || !wp_verify_nonce( $_POST['smd_render_post_type_meta'], basename( __FILE__ ) ) ){
		return $post_id;
	}


	//if user is not administrator, exit function
	if ( !current_user_can( 'administrator' ) ){
		return $post_id;
	}

	//fetching old and new meta values if they exist
	$new_meta_value = isset($_POST['smd_post_type']) ? sanitize_text_field ($_POST['smd_post_type']) : '';
	$old_meta_value = get_post_meta ($post_id, 'smd_post_type', true);

	if ( $new_meta_value && '' == $old_meta_value) {
		add_post_meta( $post_id, 'smd_post_type', $new_meta_value, true ); 
	} elseif ( $new_meta_value && $new_meta_value != $meta_value) {
		update_post_meta( $post_id, 'smd_post_type', $new_meta_value );
	} 
}


/**
 * Function for printing meta tags of Article in post pages
 */
function smd_print_post_meta_fields () {

	if ('post' == get_post_type(get_the_ID()) && !is_front_page()) {

		//if education plugin is active, we choose schema type depending on website type option automatically
		if (!is_plugin_active('simple-metadata-education/simple-metadata-education.php')){

			$post_meta_type = get_post_meta(get_the_ID(), 'smd_post_type', true) ?: 'no_type';

			if ('no_type' == $post_meta_type){
				$post_meta_type = 'Article';
			}
		} else {

			switch (get_option('smd_website_blog_type');) {
				case 'Blog':
				case 'Course':
					$post_meta_type = 'Article';
					break;
				case 'Book':
					$post_meta_type = 'Chapter';
				case 'WebSite':
					$post_meta_type = 'WebPage';		
				default:
					$post_meta_type = 'Article';
					break;
			}
		}

		$post_id = get_the_ID();

		// getting information for metafields
		$post_content = get_post( $post_id )->post_content;
		$word_count = str_word_count($post_content);
		$categories = get_the_category( $post_id);
		$categories_arr = [];
		foreach ($categories as $category) {
			$categories_arr[] = $category->name;
		}
		$categories_string = implode(', ', $categories_arr);
		$key_words = wp_get_post_tags($post_id, ['fields' => 'names']);
		$key_words_string = implode(', ', $key_words);

		$author_id = get_post_field('post_author', $post_id);
		$author = get_the_author_meta('first_name', $author_id) && get_the_author_meta('last_name', $author_id) ? get_the_author_meta('first_name', $author_id).' '.get_the_author_meta('last_name', $author_id) : get_the_author_meta('display_name', $author_id);
		$creation_date = get_the_date();
		$last_modification_date = get_the_modified_date();
		$title = get_the_title();
		$last_modifier = get_the_modified_author();
		$thumbnail_url = get_the_post_thumbnail_url();

		//getting logo image 
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		$logo = isset(wp_get_attachment_image_src( $custom_logo_id , 'full' )[0]) ? wp_get_attachment_image_src( $custom_logo_id , 'full' )[0] : (get_avatar_url($author_id) ?: '');

		$publisher = get_bloginfo();
		$type = 'Organization';
		$publication_date = get_the_time(get_option( 'date_format' ));

		//include to check if YOAST or SEO Framework are installed
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		
		//if YOAST plugin is active, get company or person name to set for publisher property
		if (is_plugin_active('wordpress-seo/wp-seo.php')){

			$all_vals = get_option('wpseo_titles');

			if (!empty($all_vals)){

				$publisher = 'company' == $all_vals['company_or_person'] ? $all_vals['company_name'] : ('person' == $all_vals['company_or_person'] ? $all_vals['person_name'] : get_bloginfo()) ;

				if ($all_vals['company_logo'] && 'company' == $all_vals['company_or_person']){
					$logo = $all_vals['company_logo'];
				}
			}
		}

		//if SEO Framework plugin is active, get company or person name to set for publisher property
		if (is_plugin_active('autodescription/autodescription.php')){

			$all_vals = get_option('autodescription-site-settings');

			if (!empty($all_vals)){

				$publisher = $all_vals['knowledge_name'] ?: get_bloginfo();

				if ($all_vals['knowledge_logo_url'] && 'organization' == $all_vals['knowledge_type']){
					$logo = $all_vals['knowledge_logo_url'];
				}
			}
		}
		?>

		<div itemscope itemtype="http://schema.org/<?=$post_meta_type;?>">
			<meta itemprop="articleBody" content="<?=$post_content;?>">
			<meta itemprop="author" content="<?=$author;?>">
			<meta itemprop="dateCreated" content="<?=$creation_date;?>">
			<meta itemprop="headline" content="<?=$title;?>">
			<meta itemprop="editor" content="<?=$last_modifier;?>">
			<meta itemprop="thumbnailUrl" content="<?=$thumbnail_url;?>">
			<meta itemprop="image" content="<?php if(!$thumbnail_url){echo $logo;} else {echo $thumbnail_url;} ?>">
			<meta itemprop="dateModified" content="<?=$last_modification_date;?>">
			<meta itemprop="datePublished" content="<?=$publication_date?>">
			<meta itemprop="keywords" content="<?=$key_words_string;?>">
			<meta itemprop="articleSection" content="<?=$categories_string;?>">
			<meta itemprop="wordCount" content="<?=$word_count;?>">
			<div itemprop="publisher" itemscope itemtype="http://schema.org/<?=$type?>">
				<meta itemprop="name" content="<?=$publisher;?>">
				<meta itemprop="logo" content="<?=$logo;?>">
			</div>
			<div itemprop="mainEntityOfPage" itemscope itemtype="http://schema.org/WebPage"></div>
		</div>

		<?php
	}

}

add_action ('add_meta_boxes', 'smd_add_post_type_meta');
add_action ('save_post', 'smd_save_post_type', 10, 2);
add_action ('wp_head', 'smd_print_post_meta_fields');