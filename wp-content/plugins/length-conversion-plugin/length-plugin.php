<?php
/*
Plugin Name: length conversion Plugin
Plugin URI: 
Description: A plugin that converts lenght measurements
Version: 1.0.0
Author: Kevin Hooper
Author URI: http://www.bakerpedia.com
License: GPL2
*/

class wp_length_plugin extends WP_Widget {

	// constructor
	function wp_length_plugin() {
		 parent::WP_Widget(false, $name = __('Length Conversion Widget', 'wp_widget_plugin') );
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
	   
	   // display length conversion page
	   wpb_length_conversion();
	   
	   //display related posts
	  // wpb_related_posts();
	   
	   echo $after_widget;
	}
}

// KJH 08/21/2015 get posts that are related by tags 
function wpb_length_conversion() { 

 ?>
 <div id="" class="">	
	 <?php 
		wphoops_scripts_include();  
		wphoops_styles_include();
	 ?>
			<div id="">       		
			 <div class="resp_code" align="left">	
				<div align="left">
					<b>Length Conversions</b> : Enter your quantity into the appropriate field and the calculation will be done automatically.
				</div>
				<div align="left">
				  <form name="conv" action="" class="frms noborders"> 
					<table width="100%" class="frms noborders"> 
						<tbody>                            
						<tr><td align="center"><input type="text" onkeyup="checnum(this);inchconv();" value="0" name="inch"></td><td>Inch</td></tr> 
						<tr><td align="center"><input type="text" onkeyup="checnum(this);cmconv();" value="0" name="cm"></td><td>Centimeter</td></tr>
						<tr><td align="center"><input type="text" onkeyup="checnum(this);feetconv();" value="0" name="feet"></td><td>Feet</td></tr>
						<tr><td align="center"><input type="text" onkeyup="checnum(this);kiloconv();" value="0" name="kilo"></td><td>Kilometer</td></tr> 
						<tr><td align="center"><input type="text" onkeyup="checnum(this);milesconv();" value="0" name="miles"></td><td>Mile</td></tr> 
						<tr><td align="center"><input type="text" onkeyUp="checnum(this);metersconv();" value="0" name="meters"></td><td>Meters</td></tr>						
						</tbody>
					</table>
					</form><br> 
				</div> 		
			</div>		
			<div class="blog-item" id="column1-wrap">	
				<div class="archive-text" style="padding-bottom:5px;" >
					
				</div>
			</div>
				<div id="column2" class="blog-item"></div>	
				<div class="post">
					<div class="post-content"></div>
				</div>
				<!-- END POST -->
			</div>
			<!-- END CONTENT -->	
	</div>			
 <?php
			
}
function wphoops_scripts_include()
{
    // Register the script like this for plugin:
    wp_register_script( 'custom-script', plugins_url( '/lengths.js', __FILE__ ) );
     
    // Then enqueue the script:
    wp_enqueue_script( 'custom-script' );
}
add_action( 'wp_enqueue_scripts', 'wphoops_scripts_include' );

function wphoops_styles_include()
{
    // Register the style like this for a plugin:
    wp_register_style( 'custom-style', plugins_url( '/conversion.css', __FILE__ ), array(), '20150908', 'all' );   
 
    // For either a plugin or a theme, you can then enqueue the style:
    wp_enqueue_style( 'custom-style' );
}
add_action( 'wp_enqueue_scripts', 'wphoops_styles_include' );

// add shortcode so you can display the widget in the post or page content edit area.
add_shortcode('lengthconversion', 'wpb_length_conversion');

// register widget
add_action('widgets_init', create_function('', 'return register_widget("wp_length_plugin");'));
?>