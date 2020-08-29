<?php
/**
 * Imports data from uscreen in posts.
 **/
class importData {
	private static $_endpoint;
	private static $_key;
	private static $_uscreenApi;
	private static $_storeToken;

	/**
	 * Private Constructor
	 */
	public function __construct() {
		self::$_endpoint = uscreenAdminInterface::getEndpoint();
		self::$_key = uscreenAdminInterface::getKey();
		self::$_uscreenApi = new uscreenAPI();
		self::$_storeToken = self::$_uscreenApi->getSessionToken();
	}

	/**
	 * Stores data from uscreen in wp_posts table.
	 * @param $page_no, page no for pagination
	 * @since 1.0
	 */
	public function importPrograms($page_no=1) {
		global $wpdb;	
		$uscreen_post_type = uscreenAdminInterface::getUscreenPostType();
		$programs = self::$_uscreenApi->getPrograms($page_no);
		if (!empty($programs)) {
			foreach ($programs as $program) {
				if ($program->chapters_count <= 1) {
					echo "frst";
					$post_title = $program->title;
					$post_description = $program->description_html;
					$chapters = $program->chapters[0];
					$chapter_type = $chapters->type;
					if ($chapter_type == 'video') {
						$post_format = $chapter_type;
						if (isset($chapters->preview_image) && $chapters->preview_image!='') {
							$post_featured_image = $chapters->preview_image;
						} else if (isset($chapters->enroll_image) && $chapters->enroll_image!='') {
							$post_featured_image = $chapters->enroll_image;
						} else if (isset($program->horizontal_preview) && $program->horizontal_preview!='') {
							$post_featured_image = $program->horizontal_preview;
						} else {
							$post_featured_image = '';
						}

						$post_duration = $chapters->duration;

						if ($program->trailer != '') {
							if (isset($program->trailer->hd) && $program->trailer->hd != '')
								$post_trailer_url = $program->trailer->hd;
							else if (isset($program->trailer->hls) && $program->trailer->hls != '')
								$post_trailer_url = $program->trailer->hls;
							else if (isset($program->trailer->sd) && $program->trailer->sd != '')
								$post_trailer_url = $program->trailer->sd;
							else if (isset($program->trailer->md) && $program->trailer->md != '')
								$post_trailer_url = $program->trailer->md;
							else
								$post_trailer_url = '';
						}

						if ($chapters->has_access == 1 && $chapters->details_url!='') {
							$post_video_url = $chapters->details_url;
						} else {
							$post_video_url = '';
						}

						$post_type = $uscreen_post_type;
						$post_author = 1;
						$post_status = 'publish';

						$post_array = array(
							'post_author' => $post_author,
							'post_content' => $post_description,
							'post_title' => $post_title,
							'post_status' => $post_status,
							'post_type' => $post_type,
							'post_name' => $post_title,
						);

						$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta as pm INNER JOIN {$wpdb->prefix}posts as p ON p.ID = pm.post_id WHERE pm.meta_key = 'uscreen_program_id' and pm.meta_value = '".$program->ID."'");
						if (empty($results)) {
							$post_id = wp_insert_post($post_array, true);
							if (!is_wp_error($post_id)) {
								wp_set_post_terms($post_id, 'post-format-video', 'post_format');
								wp_set_post_terms($post_id, 'movie', 'category');
								update_post_meta($post_id, 'field-uscreen-post', 1);

								add_post_meta($post_id, 'uscreen_program_id', $program->id, true);
								add_post_meta($post_id, 'uscreen_chapter_id', $chapter->id, true);
								add_post_meta($post_id, 'uscreen_author', $program->author, true);

								if ($post_trailer_url!='') {
									update_post_meta($post_id, 'tm_video_url', $post_trailer_url);
									update_post_meta($post_id, 'field-trailer-url', $post_trailer_url);
								}

								if ($post_duration!='') {
									update_post_meta($post_id, 'video_duration', $post_duration);
								}

								$thumbnail_id = self::generate_featured_image($post_featured_image, $post_id);
								if ($thumbnail_id!='') {
									set_post_thumbnail($post_id, $thumbnail_id);
								}
							}
						} else {
							$post_id = wp_update_post($post_array, true);
							if (!is_wp_error($post_id)) {
								if ($post_trailer_url!='') {
									update_post_meta($post_id, 'tm_video_url', $post_trailer_url);
									update_post_meta($post_id, 'field-trailer-url', $post_trailer_url);
								}

								update_post_meta($post_id, 'field-uscreen-post', 1);
								wp_set_post_terms($post_id, 'movie', 'category');

								if ($post_duration!='') {
									update_post_meta($post_id, 'video_duration', $post_duration);
								}

								$thumbnail_id = self::generate_featured_image($post_featured_image, $post_id);
								if ($thumbnail_id!='') {
									set_post_thumbnail($post_id, $thumbnail_id);
								}
							}
						}
					}
				} else {
					if (isset($program->title) && $program->title != '') {
						$term_title = $program->title;
					}

					$args = array(
						'slug' => preg_replace('/\s+/', '-', $term_title),
						'description' => $program->description,
					);

					$is_term = get_term_by('slug', preg_replace('/\s+/', '-', $term_title), 'video-series');
					if (isset($is_term) && $is_term == '') {
						$terms = wp_insert_term($term_title, 'video-series', $args);
						if (!empty($terms)) {
							$chapters = $program->chapters;
							if (!empty($chapters)) {
								foreach ($chapters as $chapter) {
									$post_title = $chapter->title;
									$post_description = $program->description_html;
									$chapter_type = $chapter->type;
									if ($chapter_type == 'video') {
										$post_format = $chapter_type;
										if (isset($chapter->preview_image) && $chapter->preview_image!='') {
											$post_featured_image = $chapter->preview_image;
										} else if (isset($chapter->enroll_image) && $chapter->enroll_image!='') {
											$post_featured_image = $chapter->enroll_image;
										} else {
											$post_featured_image = '';
										} 

										$post_duration = $chapter->duration;

										if ($chapter->has_access == 1 && $chapter->details_url!='') {
											$post_video_url = $chapter->details_url;
										} else {
											$post_video_url = '';
										}

										$post_type = $uscreen_post_type;
										$post_author = 1;
										$post_status = 'publish';

										$post_array = array(
											'post_author' => $post_author,
											'post_content' => $post_description,
											'post_title' => $post_title,
											'post_status' => $post_status,
											'post_type' => $post_type,
											'post_name' => $post_title,
										);

										$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta as pm INNER JOIN {$wpdb->prefix}posts as p ON p.ID = pm.post_id WHERE pm.meta_key = 'uscreen_chapter_id' and pm.meta_value = '".$chapter->ID."'");

										if(empty($results)) {
											$post_id = wp_insert_post($post_array, true);
											if (!is_wp_error($post_id)) {
												wp_set_post_terms($post_id, 'post-format-video', 'post_format');
												wp_set_post_terms($post_id, array($terms['term_id']), 'video-series');
												update_post_meta($post_id, 'field-uscreen-post', 1);

												update_post_meta($post_id, 'uscreen_program_id', $program->id, true);
												update_post_meta($post_id, 'uscreen_chapter_id', $chapter->id, true);
												update_post_meta($post_id, 'uscreen_author', $program->author, true);

												if ($post_duration!='') {
													update_post_meta($post_id, 'video_duration', $post_duration);
												}

												$thumbnail_id = self::generate_featured_image($post_featured_image, $post_id);
												if ($thumbnail_id!='') {
													set_post_thumbnail($post_id, $thumbnail_id);
												}
											}
										} else {
											$post_id = wp_update_post($post_array, true);

											if (!is_wp_error($post_id)) {
												if ($post_duration!='') {
													update_post_meta($post_id, 'video_duration', $post_duration);
												}

												update_post_meta($post_id, 'field-uscreen-post', 1);
												wp_set_post_terms($post_id, array($terms['term_id']), 'video-series');

												$thumbnail_id = self::generate_featured_image($post_featured_image, $post_id);
												if ($thumbnail_id!='') {
													set_post_thumbnail($post_id, $thumbnail_id);
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Generates featured image for inserted post
	 * @param $image_url, url of image
	 * @param $post_id, post ID
	 * @since 1.0
	 */
	function generate_featured_image($image_url, $post_id){
		global $wpdb;
	    $upload_dir = wp_upload_dir();
	    $image_data = file_get_contents($image_url);
	    $filename = basename($image_url);
	    if (isset($filename) && $filename!='') {
	    	$filename = explode('?', $filename);
		    if(wp_mkdir_p($upload_dir['path']))
		    	$file = $upload_dir['path'] . '/' . $filename[0];
		    else
		    	$file = $upload_dir['basedir'] . '/' . $filename[0];
		    
			$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts where post_type = 'attachment' and post_title = '".$filename[0]."'");
			if(empty($results)) {
			    file_put_contents($file, $image_data);
			    $wp_filetype = wp_check_filetype($filename[0], null);
			    $attachment = array(
			        'post_mime_type' => $wp_filetype['type'],
			        'post_title' => sanitize_file_name($filename[0]),
			        'post_content' => '',
			        'post_status' => 'inherit'
			    );

			    $attach_id = wp_insert_attachment($attachment, $file, $post_id);
			    require_once(ABSPATH . 'wp-admin/includes/image.php');
			    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
			    $res1 = wp_update_attachment_metadata($attach_id, $attach_data);
			} else {
				$attach_id = $results[0]->ID;
			}
		    
		    return $attach_id;
		}
	}
}
?>