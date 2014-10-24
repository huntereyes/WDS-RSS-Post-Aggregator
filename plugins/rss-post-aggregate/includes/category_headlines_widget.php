<?php
/*
 * Simple widget that displays the headline as a title
 */
class RSS_Post_Aggregation_Category_Headlines_Widget extends WP_Widget {

	/**
	 * Default widget options
	 *
	 * @var array
	 **/
	private $defaults = array(
		'title'	 	=> '',
		'category' 	=> '',
		'count'		=> 5,
		'excerpt' 	=> 0,
		'excerpt_length' => '15',
		'read_more_text' => '',
	);

	public function __construct() {
		parent::__construct(
			'rss_post_aggregation_category_headlines',
			__( 'RSS Category Headlines', 'wds-rss-post-aggregation' ),
			array( 'description' => __( 'Displays the title as the headline for imported RSS feed items.', 'wds-rss-post-aggregation' ) )
		);
	}

	public function form( $instance ) {

		$instance = wp_parse_args( $instance, $this->defaults );
		?>

		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php echo __( 'Title', 'rss_post_aggregation' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'category' )?>"><?php echo __( 'Category', 'rss_post_aggregation' ); ?></label>
			<?php echo $this->category_dropdown( $this->get_field_name( 'category' ), $instance['category'] ); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'count' )?>"><?php echo __( 'Number to show', 'rss_post_aggregation' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="text" value="<?php echo esc_attr( $instance['count'] ); ?>" />
		</p>
		<p>
        	<label for="<?php echo $this->get_field_id( 'excerpt' )?>"><?php echo __( 'Display Post Excerpt?', 'rss_post_aggregation' ); ?></label>
        	<input id="<?php echo $this->get_field_id('excerpt'); ?>" name="<?php echo $this->get_field_name('excerpt'); ?>" type="checkbox" value="1" <?php checked( '1', esc_attr( $instance['excerpt'] ) ); ?> />
        </p>
		<p>
			<label for="<?php echo $this->get_field_id( 'excerpt_length' )?>"><?php echo __( 'Excerpt Length', 'rss_post_aggregation' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'excerpt_length' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_length' ); ?>" type="text" value="<?php echo esc_attr($instance['excerpt_length']); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'read_more_text' )?>"><?php echo __( '"Read More" text', 'rss_post_aggregation' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'read_more_text' ); ?>" name="<?php echo $this->get_field_name( 'read_more_text' ); ?>" type="text" value="<?php echo esc_attr( $instance['read_more_text'] ); ?>" />
		</p>
		<?php

	}

	/**
	 * Update form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = array();
		$instance['excerpt'] 		= strip_tags($new_instance['excerpt']);
		$instance['excerpt_length'] = strip_tags($new_instance['excerpt_length']);


		// Sanitize options
		foreach ( $this->defaults as $key => $default_value ) {
			$instance[ $key ] = sanitize_text_field( $new_instance[ $key ] );
		}

		return $instance;
	}


	public function widget( $args, $instance ) {

		echo isset( $args['before_widget'] ) ? $args['before_widget'] : '';

		echo isset( $args['before_title'] ) ? $args['before_title'] : '';
		echo apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		echo isset( $args['after_title'] ) ? $args['after_title'] : '';

		$args = array(
			'post_type' => 'rss-posts',
			'showposts' => $instance['count'],
			'tax_query' => array(
				array(
					'taxonomy' => 'rss-category',
					'field'    => 'slug',
					'terms'    => $instance['category'],
				),
			),
		);

        $excerpt		= strip_tags($instance['excerpt']);
        $excerpt_length	= strip_tags($instance['excerpt_length']);


		if ( function_exists( 'msft_cache_get_posts' ) ) {
			$posts = msft_cache_get_posts( $args );
			$posts = is_array( $posts ) ? $posts : array();
		} else {
			$posts = get_posts( $args );
		}


		if( !empty( $posts ) ) {
			echo '<ul>';
			foreach( $posts as $p ) {
				//var_dump( get_post_meta( $p->ID ));
				echo '<li>';

				//display the linked post title
				echo '<a class="post-title" href="' . get_permalink( $p->ID ) . '"/>';
				echo $p->post_title;
				echo '</a>';

				// display the date
				echo '<p class="date">';
				echo date( 'M j, Y', strtotime( $p->post_date ) );
				echo '</p>';

				// display the excerpt or post content
				if( $excerpt ) {
					$content_excerpt = $p->post_excerpt;
					if( empty( $content_excerpt ) ) {
						$content_excerpt = $p->post_content;
					}
					$content_excerpt = strip_shortcodes( wp_strip_all_tags( $content_excerpt ) );
					if ( ! isset( $excerpt_length ) ) {
						$excerpt_length = 10;
					}
					$content_excerpt = preg_split( '/\b/', $content_excerpt, $excerpt_length * 2 + 1 );
					$body_excerpt_waste = array_pop( $content_excerpt );
					$content_excerpt = implode( $content_excerpt );
					echo wpautop( $content_excerpt );
				}

				// display the custom read more text if it exists and link to post
				if ( isset( $instance['read_more_text'] ) && trim( $instance['read_more_text'] ) ) {
				 	echo ' <a class="read-more" href="'. get_permalink( $p->ID ) .'">'. esc_html( $instance['read_more_text'] ) .'</a>';
				}
				echo '</li>';
			}
			echo '</ul>';

		} else {
			echo __( 'Nothing yet! Check again later', 'wds-rss-post-aggregation' );
		}

		echo isset( $args['after_widget'] ) ? $args['after_widget'] : '';
	}

	/**
	 * Creates a dropdown form element from the RSS Post Aggregation categories
	 *
	 * $param string $name Form element name
	 * @param string $term_slug Selected category
	 * @return string
	 **/
	function category_dropdown( $name, $term_slug = '') {
		$s = '<select name="' . esc_attr( $name ) . '" class="widefat">';

		$terms = get_terms( 'rss-category', array( 'hide_empty' => false ) );
		if ( !empty( $terms ) && !is_wp_error( $terms ) ) {
			foreach( $terms as $term ) {
				$s .= '<option name="' . esc_attr( $term->slug ) . '"';
				$s .= selected( $term_slug, $term->slug, false );
				$s .= '>';
				$s .= $term->name;
				$s .= '</option>';
			}
		}

		$s .= '</select>';

		return $s;
	}

} // end class
