<?php
/*
Plugin Name: volume conversion Plugin
Plugin URI: 
Description: A plugin that converts volume measurements
Version: 1.0.0
Author: Kevin Hooper
Author URI: http://www.bakerpedia.com
License: GPL2
*/

class wp_volume_plugin extends WP_Widget {

	// constructor
	function wp_volume_plugin() {
		 parent::WP_Widget(false, $name = __('Mass Conversion Widget', 'wp_widget_plugin') );
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
	   wpb_volume_conversion();
	   
	   //display related posts
	  // wpb_related_posts();
	   
	   echo $after_widget;
	}
}

function wpb_volume_conversion() { 

 ?>
 <div id="" class="">	
	 <?php  ?>
		<div id="">
           <h1 class=""></h1>
			
			 <div class="resp_code" align="left">							 
				<div align="left">				
				<p><b>Volume Conversions</b> : enter your quantity into the appropriate field and click Calculate.</p>				
				  <form id="volume" method="post"> 
					<table width="100%" class="frms noborders"> 
						<tbody>
							<tr style="height:1px;"><td><input type="hidden" value="Clear form" onclick="clearform(this.form)" ></td></tr>
							<tr style="height:25px;"><td align="center"><input type="TEXT" name="val1" size="6" value="0" onfocus="clearform(this.form)"></td><td align="left" style="padding-left: 5px;">Liters</td></tr>
							<tr style="height:25px;"><td align="center"><input type="TEXT" name="val2" size="6" value="0" onfocus="clearform(this.form)"></td><td align="left" style="padding-left: 5px;">Ounces</td></tr>
							<tr style="height:25px;"><td align="center"><input type="TEXT" name="val3" size="6" value="0" onfocus="clearform(this.form)"></td><td align="left" style="padding-left: 5px;">Quarts</td></tr>
							<tr style="height:25px;"><td align="center"><input type="TEXT" name="val4" size="6" value="0" onfocus="clearform(this.form)"></td><td align="left" style="padding-left: 5px;">Gallons</td></tr>									
							<tr style="height:25px;">
								<td style="align:left;"><input type="button" value="Calculate" onclick="convertform(this.form)"></td>								
							</tr>							
						</tbody>
					</table>
					</form><br> 
					<div></div>
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
	<script language="JavaScript">
		document.getElementById("volume").count = 4;
		document.getElementById("volume").rsize = 5;
		document.getElementById("volume").val1.factor = 1;
		document.getElementById("volume").val2.factor = 33.8239926;
		document.getElementById("volume").val3.factor = 1.056998;
		document.getElementById("volume").val4.factor = 0.2642499;
		
// Author    : Jonathan Weesner (jweesner@cyberstation.net)  21 Nov 95
// Copyright : You want it? Take it! ... but leave the Author line intact please!			
function convertform(form){

    var firstvalue = 0;
    for (var i = 1; i <= form.count; i++) {
       // Find first non-blank entry
       if (form.elements[i].value != null && form.elements[i].value.length != 0) {
			if (i == 1 && form.elements[2].value != "") return false;
			firstvalue = form.elements[i].value / form.elements[i].factor;
		break;
       }
    }

    if (firstvalue == 0) {
       clearform(form);
       return false;
    }

    for (var i = 1; i <= form.count; i++)
       form.elements[i].value = formatvalue((firstvalue * form.elements[i].factor), form.rsize);
    return true;
}

function formatvalue(input, rsize) {
   var invalid = "**************************";
   var nines = "999999999999999999999999";
   var strin = "" + input;
   var fltin = parseFloat(strin);

   if (strin.length <= rsize) return strin;

   if (strin.indexOf("e") != -1 ||
       fltin > parseFloat(nines.substring(0,rsize)+".4"))
      return invalid.substring(0, rsize);
	  var rounded = "" + (fltin + (fltin - parseFloat(strin.substring(0, rsize))));
      return rounded.substring(0, rsize);

}

function resetform(form) {
    clearform(form);	
   
    return true;
}

function clearform(form) {
    for (var i = 1; i <= form.count; i++) form.elements[i].value = "";
    return true;
}
	</script>			
 <?php
			
}

// add shortcode so you can display the widget in the post or page content edit area.
add_shortcode('volumeconversion', 'wpb_volume_conversion');

// register widget
add_action('widgets_init', create_function('', 'return register_widget("wp_volume_plugin");'));
?>