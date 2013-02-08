<?php
/*
Plugin Name: Phosphor Wordpress Plugin
Description: All of the important functionality of your site belongs in this.
Version: 0.1
License: MIT
Author: Ikrima Elhassan
Author URI: mythly.com
*/

/*
Adapted from: http://mediaelementjs.com/ plugin
*/

$phosphorPlayerIndex = 1;

/* Runs when plugin is activated */
register_activation_hook(__FILE__,'pjs_install');

function pjs_install() {

	add_option('pp_default_video_height', '');
	add_option('pp_default_video_width', '');

	//TODO: ikrimae: Add options for adding interactive controls (play, pause, loop)
}

/* Runs on plugin deactivation*/
register_deactivation_hook( __FILE__, 'ppjs_remove' );
function ppjs_remove() {
	delete_option('pp_default_video_height');
	delete_option('pp_default_video_width');
}

// create custom plugin settings menu
add_action('admin_menu', 'pjs_create_menu');

function pjs_create_menu() {

	//create new top-level menu
	add_options_page('Phosphor.js', 'Phosphor.js', 'administrator', __FILE__, 'pjs_settings_page');

	//call register settings function
	add_action( 'admin_init', 'pjs_register_settings' );
}


function pjs_register_settings() {
	//register our settings
	register_setting( 'pp_settings', 'pp_default_video_height' );
	register_setting( 'pp_settings', 'pp_default_video_width' );
}


function pjs_settings_page() {
?>
<div class="wrap">
<h2>Phosphor.js HTML5 Player Options</h2>

<p>See <a href="https://github.com/mikewoodworth/phosphorframework">https://github.com/mikewoodworth/phosphorframework</a> for more details on how Phosphor works.</p>

<form method="post" action="options.php">
<?php wp_nonce_field('update-options'); ?>


	<h3 class="title"><span>Image Settings Settings</span></h3>

	<table  class="form-table">
		<tr valign="top">
			<th scope="row">
				<label for="pp_default_video_width">Default Width</label>
			</th>
			<td >
				<input name="pp_default_video_width" type="text" id="pp_default_video_width" value="<?php echo get_option('pp_default_video_width'); ?>" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="pp_default_video_height">Default Height</label>
			</th>
			<td >
				<input name="pp_default_video_height" type="text" id="pp_default_video_height" value="<?php echo get_option('pp_default_video_height'); ?>" />
			</td>
		</tr>
	</table>

	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="pp_default_video_width,pp_default_video_height" />

	<p>
		<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>

</div>



</form>
</div>
<?php
}


define('PHOSPHORJS_DIR', WP_PLUGIN_URL.'/phosphor-js-player/phosphorjs/');
// Javascript
function pjs_add_scripts(){
    if (!is_admin()){
        // the scripts
        wp_enqueue_script("phosphorjs-scripts", PHOSPHORJS_DIR ."phosphorframework.js", array('jquery'), "1.0.0", false);
    }
}
add_action('wp_print_scripts', 'pjs_add_scripts');

// css
//function pjs_add_styles(){
//    if (!is_admin()){
//        // the style
//        wp_enqueue_style("mediaelementjs-styles", MEDIAELEMENTJS_DIR ."mediaelementplayer.css");
//
//        if (get_option('mep_video_skin') != '') {
//			wp_enqueue_style("mediaelementjs-skins", MEDIAELEMENTJS_DIR ."mejs-skins.css");
//		}
//    }
//}

//add_action('wp_print_styles', 'mejs_add_styles');

function pjs_add_header(){
/*

	$dir = WP_PLUGIN_URL.'/phosphor-js-player/phosphorjs/';

	echo <<<_end_
<script src="{$dir}phosphorframework.js" type="text/javascript"></script>
_end_;
*/

}

// If this happens in the <head> tag it fails in iOS. Boo.
function pjs_add_footer(){

}

add_action('wp_head','pjs_add_header');
add_action('wp_footer','pjs_add_footer');

function pjs_media_shortcode($tagName, $atts){

	global $phosphorPlayerIndex;
	$dir = WP_PLUGIN_URL.'/phosphor-js-player/phosphorjs/';

	$defaultVideoWidth = get_option('pp_default_video_width');
    $defaultVideoHeight = get_option('pp_default_video_height');

	extract(shortcode_atts(array(
		'src' => '',
		'atlascount' => '',
		'width' => get_option('pp_default_'.$tagName.'_width'),
		'height' => get_option('pp_default_'.$tagName.'_height'),
		'autoplay' => '',
		'isinteractive' => '',
		'loop' => ''
	), $atts));

	if ($src) {
		$src_attribute = 'src="'.htmlspecialchars($src).'"';
		$flash_src = htmlspecialchars($src);
	}

	if ($width) {
		$width_attribute = 'width="'.$width.'"';
	}

	if ($height) {
		$height_attribute = 'height="'.$height.'"';
	}


    $srcParts = pathinfo($src);
    $srcPathName = $srcParts['dirname'];
    $srcBaseName = $srcParts['filename'];
    $srcExtName = $srcParts['extension'];
    $atlasSourceList = '';
    for ($i=0; $i<$atlascount; $i++)
    {
        $atlasSourceList .= '"'.$srcPathName.'/'.$srcBaseName.'_atlas'.sprintf('%03d', $i).'.'.$srcExtName.'"';
        if($i != ($atlastcount - 1))
        {
            $atlasSourceList .= ',';
        }
    }

	$mediahtml .= <<<_end_
	<img id="wp_pp_target_{$srcBaseName}" {$src_attribute} {$width_attribute} {$height_attribute} />
<script type="text/javascript">
/**
   * After the page has loaded, we register a callback which will be triggered by the jsonp file.
   * Once the callback is registered, we inject the jsonp script file into the page's HEAD block.
   * An alternative method is to use AJAX (getJSON, etc) to load the corresponding json file.  After loading the
   * data, instantiate the player in the same way.
   */
  jQuery(document).ready(function(){
    var wp_pp_{$srcBaseName} = null;
    var wp_pp_{$srcBaseName}_framecount = 0;

    wp_pp_{$srcBaseName} = new PhosphorPlayer('wp_pp_target_{$srcBaseName}');
    phosphorCallback_{$srcBaseName} = function(data) {

     /**
      * Instantiate the player.  The player supports a variate of callbacks for deeper integration into your site.
      */

      wp_pp_{$srcBaseName}_framecount = data.frames.length;
      wp_pp_{$srcBaseName}.load_animation({
       imageArray:[{$atlasSourceList}],
       animationData: data,
       loop: {$loop},
       onLoad: function() {
         wp_pp_{$srcBaseName}.play();

         /**
          * If your Phosphor composition was created with the "interactive" mode set, the code below enables that
          * interation.  Handlers are registered for both mouse drag and touch events.
          */

          var trappedMouse = false;
          var trappedXPos;

          var enableInteractivity = $isinteractive;

          if(enableInteractivity) {
           jQuery("#wp_pp_target_{$srcBaseName}").mousedown(function(e){
             e.preventDefault();
             wp_pp_{$srcBaseName}.stop();
             trappedMouse = true;
             trappedXPos = e.pageX;
             jQuery(document).bind('mousemove',function(event) {
               if(trappedMouse){
                 var pos =  (event.pageX - trappedXPos) / 5;
                 var seekTime = (wp_pp_{$srcBaseName}_framecount + wp_pp_{$srcBaseName}.currentFrameNumber() + parseInt(pos)) % wp_pp_{$srcBaseName}_framecount;
                 wp_pp_{$srcBaseName}.setCurrentFrameNumber(seekTime);
                 trappedXPos = event.pageX;
               }

             });

           });

           jQuery(document).mouseup(function(e){
             trappedMouse = false;
             jQuery(document).unbind('mousemove');
           });



           jQuery("#wp_pp_target_{$srcBaseName}").bind("touchstart",function(event){
            var e = event.originalEvent;
            e.preventDefault();
             wp_pp_{$srcBaseName}.stop();
            trappedMouse = true;
            trappedXPos = e.pageX;
            jQuery(document).bind('touchmove', function(e) {
             if(trappedMouse){
               var e = e.originalEvent;
               e.preventDefault();
               var pos =  (e.pageX - trappedXPos) / 5;
               var seekTime = (wp_pp_{$srcBaseName}_framecount + wp_pp_{$srcBaseName}.currentFrameNumber() + parseInt(pos)) % wp_pp_{$srcBaseName}_framecount;
               wp_pp_{$srcBaseName}.setCurrentFrameNumber(seekTime);
               trappedXPos = e.pageX;
             }
            });
          });

           jQuery("#wp_pp_target_{$srcBaseName}").bind("touchend",function(event){
            var e = event.originalEvent;
            e.preventDefault();
            trappedMouse = false;
            wp_pp_{$srcBaseName}.play(true);
            jQuery(document).unbind('touchmove');
          });

         }

       }
     });
    }
    var jsonpScript = document.createElement("script");
    jsonpScript.type = "text/javascript";
    jsonpScript.id = "jsonPinclude_{$srcBaseName}";
    jsonpScript.src = "{$srcPathName}/{$srcBaseName}_animationData.json" + "p";
    document.getElementsByTagName("head")[0].appendChild(jsonpScript);


});
</script>

_end_;

	$phosphorPlayerIndex++;

  return $mediahtml;
}


function pjs_phosphor_shortcode($atts){
	return pjs_media_shortcode('video',$atts);
}

add_shortcode('phosphor', 'pjs_phosphor_shortcode');

function pjs_init() {

	wp_enqueue_script( 'jquery' );

}

add_action('init', 'pjs_init');

?>

