<?php
/*
Plugin Name: Tags Plugin
Plugin URI: 
Description: A plugin that adds a widget to a page or post based on associated tags.
Version: 1.0.0
Author: Kevin Hooper
Author URI: http://www.bakerpedia.com
License: GPL2
*/

class wp_tags_plugin extends WP_Widget {

	// constructor
	function wp_tags_plugin() {
		 parent::WP_Widget(false, $name = __('Related Links Widget', 'wp_widget_plugin') );
	}

	// widget form creation
	function form($instance) {	
		// Check values
		if( $instance) {
			 $title = esc_attr($instance['title']);
			 $text = esc_attr($instance['text']);
			 $textarea = esc_textarea($instance['textarea']);
		} else {
			 $title = '';
			 $text = '';
			 $textarea = '';
		}
		?>

		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', 'wp_widget_plugin'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<p>
		<label for="<?php echo $this->get_field_id('text'); ?>"><?php _e('Text:', 'wp_widget_plugin'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>" type="text" value="<?php echo $text; ?>" />
		</p>

		<p>
		<label for="<?php echo $this->get_field_id('textarea'); ?>"><?php _e('Textarea:', 'wp_widget_plugin'); ?></label>
		<textarea class="widefat" id="<?php echo $this->get_field_id('textarea'); ?>" name="<?php echo $this->get_field_name('textarea'); ?>"><?php echo $textarea; ?></textarea>
		</p>
				
		<?php
	}

	// widget update
	function update($new_instance, $old_instance) {
		  $instance = $old_instance;
      // Fields
      $instance['title'] = strip_tags($new_instance['title']);
      $instance['text'] = strip_tags($new_instance['text']);
      $instance['textarea'] = strip_tags($new_instance['textarea']);
     return $instance;
	}

	// widget display
	function widget($args, $instance) {
		extract( $args );
	   // these are the widget options
	   $title = apply_filters('widget_title', $instance['title']);
	   $text = $instance['text'];
	   $textarea = $instance['textarea'];
	   echo $before_widget;
	   // Display the widget
	   echo '<div>';

	   // Check if title is set
	   if ( $title ) {
		  echo '<div class="related-links-widget-title">'.$title.'</div>';;
	   }

	   // Check if text is set
	   if( $text ) {
		  echo '<p>'.$text.'</p>';
	   }
	   // Check if textarea is set
	   if( $textarea ) {
		 echo '<p>'.$textarea.'</p>';
	   }
	   echo '</div>';
	   
	   // display related pages
	   wpb_related_pages();
	   
	   //display related posts
	   wpb_related_posts();
	   
	   echo $after_widget;
	}
}
// KJH 08/20/2015 - get pages that are related by tags 
function wpb_related_pages() { 
	$orig_post = $post;
	global $post;
	$tags = wp_get_post_tags($post->ID);
	
	if ($tags) {		
		$tag_ids = array();
		foreach($tags as $individual_tag)
			$tag_ids[] = $individual_tag->term_id;
			$args=array(
			'post_type' => 'page',
			'tag__in' => $tag_ids,
			'post__not_in' => array($post->ID),
			'posts_per_page'=>5
			);
			
			$my_query = new WP_Query( $args );
			
				if( $my_query->have_posts() ) {				
					echo '<div style="margin-bottom: -15px;" class="related-links-widget-title">Related Topics</div>';
					//  echo $post->ID;
						while( $my_query->have_posts() ) {
							$my_query->the_post(); ?>		
		
					<p style="margin-bottom: -20px;">			
						<i class="fa fa-link"></i>
						<a style="padding-left:10px;" href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a>
					</p>
					 
					 <?php
					 }
					
	echo '</ul></div>';
	} else { 
	echo "No Related Pages Found:";
	}
}
	$post = $orig_post;
	wp_reset_query(); 
}

// KJH 08/21/2015 get posts that are related by tags 
function wpb_related_posts() { 
	$orig_post = $post;
	global $post;
	$tags = wp_get_post_tags($post->ID);
	
	if ($tags) {
		$tag_ids = array();
		foreach($tags as $individual_tag)
			$tag_ids[] = $individual_tag->term_id;
			$args=array(
			'post_type' => 'post',
			'tag__in' => $tag_ids,
			'post__not_in' => array($post->ID),
			'posts_per_page'=>5
			);
			
			$my_query = new WP_Query( $args );
			
				if( $my_query->have_posts() ) {				
					echo '<div style="margin-bottom: -15px;" class="related-links-widget-title">Related Posts</div>';
					// echo $post->ID;
						while( $my_query->have_posts() ) {
							$my_query->the_post(); ?>		
		
					<p style="margin-bottom: -20px;">			
						<i class="fa fa-link"></i>
						<a style="padding-left:10px;" href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a>
					</p>					 
				 <?php
				 }				
			} 
	}
	
	$post = $orig_post;
	wp_reset_query(); 
}
// register widget
add_action('widgets_init', create_function('', 'return register_widget("wp_tags_plugin");'));
?>