<?php
/**
 * Plugin Name:       Top Channel Posts
 * Description:       Shows top posts of a channel
 * Version:           1.0.0
 * Author:            Mango IT Solutions
 * Author URI:        https://www.mangoitsolutions.com/
 * Text Domain:       top-channel-posts
 * License: GPL v3
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * Original project created Mango IT Solutions.
 */
?>
<?php
/**
 * Registers the Top Channel Posts widget in WordPress
 * @since 1.0.0
 * @return null
 */
function wpb_load_widget() {
	register_widget( 'Top_Channel_Posts' );
}
add_action( 'widgets_init', 'wpb_load_widget' );

/* Creating the widget */
class Top_Channel_Posts extends WP_Widget {
	function __construct() {
		parent::__construct(
			'Top_Channel_Posts',
			__( 'Top Channel Posts', 'wpb_widget_domain' ),
			array( 'description' => __( 'Loads top posts of channel.' ) )
		);
	}

	function widget( $args, $instance ) {
		ob_start();
		extract( $args );
		$ids           = empty( $instance['ids'] ) ? '' : $instance['ids'];
		$title         = empty( $instance['title'] ) ? '' : $instance['title'];
		$title         = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		$number        = empty( $instance['number'] ) ? 3 : $instance['number'];
		$sort_by       = empty( $instance['sortby'] ) ? '' : $instance['sortby'];
		$img_display   = empty( $instance['img_display'] ) ? '' : $instance['img_display'];
		$show_metadata = empty( $instance['metadata'] ) ? '' : $instance['metadata'];
		if ( '' != $ids ) {
			$ids     = explode( ',', $ids );
			$id_list = array();
			foreach ( $ids as $id ) {
				array_push( $id_list, $id );
			}
			$args = array(
				'post_type'           => 'ct_channel',
				'posts_per_page'      => $number,
				'order'               => 'DESC',
				'post_status'         => 'publish',
				'post__in'            => $id_list,
				'ignore_sticky_posts' => 1,
			);
		} else {
			$args = array(
				'post_type'           => 'ct_channel',
				'posts_per_page'      => $number,
				'post_status'         => 'publish',
				'orderby'             => $sort_by,
				'ignore_sticky_posts' => 1,
			);
			if ( 'view' == $sort_by && '' == $ids ) {
					$ids = array();
				if ( function_exists( 'videopro_get_tptn_pop_posts' ) ) {
					$args = array(
						'daily'      => 0,
						'post_types' => 'ct_channel',
					);
					$ids  = videopro_get_tptn_pop_posts( $args );
				}
					$args = array(
						'post_type'           => 'ct_channel',
						'posts_per_page'      => $number,
						'post_status'         => 'publish',
						'ignore_sticky_posts' => 1,
					);
					$args = array_merge(
						$args,
						array(
							'post__in' => $ids,
							'orderby'  => 'post__in',
						)
					);
			}
		}
		$the_query = new WP_Query( $args );
		$html      = $before_widget;
		if ( $title ) {
			$html .= $before_title . $title . $after_title;
		}
		if ( $the_query->have_posts() ) :
			$html .= '<div class="widget_top_channel_content">
            <div class="post-metadata sp-style">';
			while ( $the_query->have_posts() ) :
				$the_query->the_post();

				$args         = array(
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'ignore_sticky_posts' => 1,
					'posts_per_page'      => 5,
					'orderby'             => 'latest',
					'meta_query'          => array(
						array(
							'key'     => 'channel_id',
							'value'   => get_the_ID(),
							'compare' => 'LIKE',
						),
					),
				);
				$video_query  = new WP_Query( $args );
				$n_video      = $video_query->found_posts;
				$channel_name = get_post_field( 'post_title', get_the_ID() );
				if ( $video_query->have_posts() ) :
					while ( $video_query->have_posts() ) :
						$video_query->the_post();
						$total_views = function_exists( 'get_tptn_post_count_only' ) ? get_tptn_post_count_only( get_the_ID(), 'total' ) : '';
						$get_format  = get_post_format();
						$id          = get_the_ID();
						$rent        = get_post_meta( $id, 'rent_price', true );
						$video_url   = get_the_permalink( $id );
						$video_url   = apply_filters( 'videopro_loop_item_url', $video_url, $id );
						$post_format = isset( $get_format ) ? 'post_format_' . $get_format . '' : '';

						if ( 'video' == $get_format ) {
							$popup_url   = get_post_meta( $id, 'tm_video_url', true );
							$popup_file  = get_post_meta( $id, 'tm_video_file', true );
							$trailer_url = get_post_meta( $id, 'trailer_url', true );

							if ( '' != $popup_url ) {
								$url = $popup_url;
							} elseif ( '' != $popup_file ) {
								$url = $popup_file;
							} elseif ( '' != $trailer_url ) {
								$url = $trailer_url;
							} else {
								$url = '';
							}
						}

						$html .= '
						<div class="channel-subscribe">';
						if ( has_post_thumbnail( get_the_ID() ) && 'cover' == $img_display ) {
							// show channel cover
							$html .= '
								<div class="channel ' . $post_format . '">
									<a href="' . get_permalink( get_the_ID() ) . '" title="' . the_title_attribute( 'echo=0' ) . '">';
							$html .= videopro_thumbnail( array( 140, 65 ) );
							if ( '' == $rent || ( isset( $trailer_url ) && '' != $trailer_url ) ) {
								if ( '' != $url ) {
									$player_sc = getPlayerShortcode( $id, $url, true );
									$html     .= '<div class="popup-video">' . do_shortcode( $player_sc ) . '</div>';
								}
							}
							$html .= '</a>
								</div>';
						} elseif ( 'thumb' == $img_display ) {
							// show channel thumbnail
							$thumbnail = get_post_meta( get_the_ID(), 'channel_thumb', true );
							if ( '' != $thumbnail ) {
								$thumbnail = wp_get_attachment_image( $thumbnail, array( 140, 65 ) );
								$html     .= '
									<div class="channel">
		                                <a href="' . get_permalink( get_the_ID() ) . '" title="' . the_title_attribute( 'echo=0' ) . '">' . $thumbnail . '</a>
		                            </div>';
							}
						} else {
							// show author avatar
							$img   = get_avatar( get_the_author_meta( 'email' ), 50 );
							$html .= '
								<div class="channel-picture ' . $post_format . '">
									<a href="' . get_permalink( get_the_ID() ) . '" title="' . the_title_attribute( 'echo=0' ) . '">
										' . $img . '
									</a>
								</div>';
						}
							$html .= '
							<div class="channel-content">
								<h4 class="channel-title h6">
									<a href="' . get_permalink( get_the_ID() ) . '" title="' . the_title_attribute( 'echo=0' ) . '">
										' . the_title_attribute( 'echo=0' ) . '
									</a>';
									ob_start();
									do_action( 'videopro_after_title', get_the_ID() );
									$html .= ob_get_contents();
									ob_end_clean();

									$html .= '
								</h4>';
								$html     .= '<span class="channel-name">' . $channel_name . '</span>';
								$html     .= '<span class="channel-tag">Channel</span>';
						if ( $show_metadata ) {

							$html .= '<div class="posted-on metadata-font">
		                            <span class="cactus-info font-size-1"><span> ' . sprintf( '%d videos' , $n_video ) . '</span></span>';
							if ( '' != $total_views ) {

								$html .= '<div class="cactus-info font-size-1"><i class="fas fa-eye"></i> ' . sprintf( '%d views' ), $total_views ) . '</div>';

							}

								$html .= '
		                        </div>';
						}

							$html .= '</div>
						</div>
						';
						break;
					endwhile;
				endif;
			endwhile;
			$html .= '</div>';
			$html .= '</div>';
		endif;
		$html .= $after_widget;
		echo $html;
		wp_reset_postdata();
	}

	function update( $new_instance, $old_instance ) {
		$instance                = $old_instance;
		$instance['title']       = strip_tags( $new_instance['title'] );
		$instance['ids']         = strip_tags( $new_instance['ids'] );
		$instance['sortby']      = esc_attr( $new_instance['sortby'] );
		$instance['img_display'] = esc_attr( $new_instance['img_display'] );
		$instance['number']      = absint( $new_instance['number'] );
		$instance['metadata']    = esc_attr( $new_instance['metadata'] );
		return $instance;
	}

	function form( $instance ) {
		$title         = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$ids           = isset( $instance['ids'] ) ? esc_attr( $instance['ids'] ) : '';
		$number        = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$show_metadata = isset( $instance['metadata'] ) ? $instance['metadata'] : 1;
		?>

		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'videopro' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>
		<p>
		<label for="<?php echo $this->get_field_id( 'ids' ); ?>"><?php esc_html_e( 'IDs (List of Channels IDs or Slugs, separated by a comma):', 'videopro' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'ids' ); ?>" name="<?php echo $this->get_field_name( 'ids' ); ?>" type="text" value="<?php echo $ids; ?>" />
		</p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php esc_html_e( 'Number of Items:', 'videopro' ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
		<p>
		<label for="<?php echo $this->get_field_id( 'sortby' ); ?>">
		<?php esc_html_e( 'Order by', 'videopro' ); ?>:
		<select id="<?php echo $this->get_field_id( 'sortby' ); ?>" name="<?php echo $this->get_field_name( 'sortby' ); ?>">
		<option value="view"<?php selected( isset( $instance['sortby'] ) ? $instance['sortby'] : '', 'view' ); ?>><?php esc_html_e( 'Most Viewed ', 'videopro' ); ?></option>
		<option value="rand"<?php selected( isset( $instance['sortby'] ) ? $instance['sortby'] : '', 'rand' ); ?>><?php esc_html_e( 'Random', 'videopro' ); ?></option>
		</select>
		</label>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'img_display' ); ?>">
		<?php esc_html_e( 'Images display', 'videopro' ); ?>:
		<select id="<?php echo $this->get_field_id( 'img_display' ); ?>" name="<?php echo $this->get_field_name( 'img_display' ); ?>">
		<option value="avatar"<?php selected( isset( $instance['img_display'] ) ? $instance['img_display'] : '', 'avatar' ); ?>><?php esc_html_e( 'Show avatar of author', 'videopro' ); ?></option>
		<option value="cover"<?php selected( isset( $instance['img_display'] ) ? $instance['img_display'] : '', 'cover' ); ?>><?php esc_html_e( 'Show cover photo', 'videopro' ); ?></option>
		<option value="thumb"<?php selected( isset( $instance['img_display'] ) ? $instance['img_display'] : '', 'thumb' ); ?>><?php esc_html_e( 'Show thumbnail image', 'videopro' ); ?></option>
		</select>
		</label>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'metadata' ); ?>">
		<?php esc_html_e( 'Show Channel Information', 'videopro' ); ?>:
		<select id="<?php echo $this->get_field_id( 'metadata' ); ?>" name="<?php echo $this->get_field_name( 'metadata' ); ?>">
		<option value="1"<?php selected( isset( $instance['metadata'] ) ? $instance['metadata'] : '', 1 ); ?>><?php esc_html_e( 'Yes', 'videopro' ); ?></option>
		<option value="0"<?php selected( isset( $instance['metadata'] ) ? $instance['metadata'] : '', 0 ); ?>><?php esc_html_e( 'No', 'videopro' ); ?></option>
		</select>
		</label>
		</p>
		<?php
	}
}

