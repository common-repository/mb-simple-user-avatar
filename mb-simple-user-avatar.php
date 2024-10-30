<?php

   /*
   Plugin Name: MB Simple User Avatar
   Plugin URI:  https://babakfakhamzadeh.com/project/a-wordpress-plugin-to-upload-user-avatars/
   Description: Allows for custom user avatars in front end, via shortcode, and back end.
   Version: 0.0.4
   Author: Babak Fakhamzadeh, somewhat based on work by Ommune Team
   Author URI: https://babakfakhamzadeh.com
   Text Domain: mb-sua
   Domain Path: /languages/
   */

	/* Enqueue scripts */
	function mb_sua_additions_scripts() {
	
		$pluginLocation = plugin_dir_path( __FILE__ );

	    wp_enqueue_style( 'mb-sua-style', plugins_url( 'assets/css/custom.css', __FILE__ ), array(), filemtime($pluginLocation . 'assets/css/custom.css'));

		wp_register_script( 'mb-sua-js', plugins_url( 'assets/js/custom.js', __FILE__ ), array("jquery"), filemtime($pluginLocation . 'assets/js/custom.js'), true );
		$translation_array = array( 
			'Problem'			=> __("Problem", "mb-sua"),
			'templateUrl'		=> get_theme_file_uri(),
			'siteUrl'			=> site_url(),
	
		);
		wp_localize_script( 'mb-sua-js', 'localizations', $translation_array );
		wp_enqueue_script( 'mb-sua-js' );
	
	}
	add_action( 'wp_enqueue_scripts', 'mb_sua_additions_scripts' );

	/* Add fields to backend form */
	function mb_sua_avatar_field( $user ) { ?>
	    <?php  wp_enqueue_media(); ?>
	     
	    <script type="text/javascript">
	    jQuery(document).ready(function($){
	        var custom_uploader;
	        $('#upload_image_button').click(function(e) {
	 
	            e.preventDefault();
	 
	            //If the uploader object has already been created, reopen the dialog
	            if (custom_uploader) {
	                custom_uploader.open();
	                return;
	            }
	 
	            //Extend the wp.media object
	            custom_uploader = wp.media.frames.file_frame = wp.media({
	                title: '<?php _e("Choose image", "mb-sua"); ?>',
	                button: {
	                    text: '<?php _e("Choose image", "mb-sua"); ?>'
	                },
	                multiple: false
	            });
	 
	            //When a file is selected, grab the URL and set it as the text field's value
	            custom_uploader.on('select', function() {
	                attachment = custom_uploader.state().get('selection').first().toJSON();
	                $('#upload_image').val(attachment.id);
	                $('#mb_sua_image').attr('src', attachment.url);
	            });
	 
	            //Open the uploader dialog
	            custom_uploader.open();
	 
	        });
	 
	    });
	    </script>
	 
	    <h3><?php _e("MB Simple User Avatar", "mb-sua"); ?></h3>
	    <table>
	        <tr>
	            <td>
		            <label for='mb_sua_custom_avatar'><?php _e("Avatar image: ", "mb-sua"); ?></label>
		        </td>
	            <td>
		            <label for='upload_image'>
						<input id="upload_image" type="hidden" size="36" name="ad_image" value="<?php echo get_user_meta($user->ID,'mb_sua_custom_avatar',true)?>" />
						<input id="upload_image_button" type="button" value="<?php _e("Upload Image", "mb-sua"); ?>" />
					</label>
				</td>
	            <td><img src="<?php echo wp_get_attachment_url(get_user_meta($user->ID,'mb_sua_custom_avatar',true));?>" id="mb_sua_image"  style="height:100px; width: auto;"/></td>
	        </tr>
	        <?php
		        if (get_user_meta($user->ID,'mb_sua_custom_avatar',true) != "") {
			  		?>
			  		<tr>
			  			<td><label for='be_custom_avatar_remove'><?php _e("Remove avatar:", "mb-sua"); ?></label></td>
				  		<td colspan='2'><input type='checkbox' value='1' name='mb_sua_custom_avatar_remove' id='mb_sua_custom_avatar_remove'></td>
			  		</tr>
			  		<?php      
		        }
		    ?>
	    </table>
	    <?php
	}
	add_action( 'show_user_profile', 'mb_sua_avatar_field' );
	add_action( 'edit_user_profile', 'mb_sua_avatar_field' );
	 
	function mb_sua_save_avatar_field( $user_id ) {
	    if ( !current_user_can( 'edit_user', $user_id ) ) { return false; }
	    
	    update_user_meta( $user_id, 'mb_sua_custom_avatar', $_POST['ad_image'] );
	    
		if (isset($_POST['mb_sua_custom_avatar_remove'])) {
			if ($_POST["mb_sua_custom_avatar_remove"] == 1) {
				update_user_meta( $user_id, 'mb_sua_custom_avatar', "" );
			}
		}

	
	}
	add_action( 'personal_options_update', 'mb_sua_save_avatar_field' );
	add_action( 'edit_user_profile_update', 'mb_sua_save_avatar_field' );
	 
	function mb_sua_gravatar_filter($avatar, $id_or_email, $size, $default, $alt) {
	    $custom_avatar =  get_the_author_meta('mb_sua_custom_avatar',$id_or_email);
	    if ($custom_avatar)
	        $return = mb_sua_get_wp_user_avatar_image($id_or_email, $size, $default, $alt);
	    elseif ($avatar)
	        $return = $avatar;
	    else
	        $return = '<img src="'.$default.'" width="'.$size.'" height="'.$size.'" alt="'.$alt.'" class="avatar avatar-'.$size.'" />';
	    return $return;
	}
	 
	add_filter('get_avatar', 'mb_sua_gravatar_filter', 10, 5);	 
	 
	// Find avatar, show get_avatar if empty
	function mb_sua_get_wp_user_avatar_image($id_or_email="", $size='96', $align="", $alt="", $email='unknown@gravatar.com'){
	 
	    global $avatar_default, $blog_id, $post, $wpdb, $_wp_additional_image_sizes;
	    // Checks if comment
	    if(is_object($id_or_email)){
	        // Checks if comment author is registered user by user ID
	        if($id_or_email->user_id != 0){
	            $email = $id_or_email->user_id;
	            // Checks that comment author isn't anonymous
	        } elseif(!empty($id_or_email->comment_author_email)){
	            // Checks if comment author is registered user by e-mail address
	            $user = get_user_by('email', $id_or_email->comment_author_email);
	            // Get registered user info from profile, otherwise e-mail address should be value
	            $email = !empty($user) ? $user->ID : $id_or_email->comment_author_email;
	        }
	 
	        $alt = $id_or_email->comment_author;
	    } else {
	 
	        if(!empty($id_or_email)){
	            // Find user by ID or e-mail address
	            $user = is_numeric($id_or_email) ? get_user_by('id', $id_or_email) : get_user_by('email', $id_or_email);
	        } else {
	            // Find author's name if id_or_email is empty
	            $author_name = get_query_var('author_name');
	            if(is_author()){
	                // On author page, get user by page slug
	                $user = get_user_by('slug', $author_name);
	            } else {
	                // On post, get user by author meta
	                $user_id = get_the_author_meta('ID');
	                $user = get_user_by('id', $user_id);
	            }
	        }
	 
	        // Set user's ID and name
	        if(!empty($user)){
	            $email = $user->ID;
	            $alt = $user->display_name;
	        }
	    }
	 
	    // Checks if user has avatar
//	    $wpua_meta = get_the_author_meta($wpdb->get_blog_prefix($blog_id).'user_avatar', $email);
	    $wpua_meta = get_the_author_meta('mb_sua_custom_avatar',$email);
	 
	    // Add alignment class
	    $alignclass = !empty($align) && ($align == 'left' || $align == 'right' || $align == 'center') ? ' align'.$align : ' alignnone';
	 
	    // User has avatar, bypass get_avatar
	    if(!empty($wpua_meta)){
	        // Numeric size use size array
	        $get_size = is_numeric($size) ? array($size,$size) : $size;
	        // Get image src
//	        $wpua_image = wp_get_attachment_image_src($wpua_meta, $get_size);
	        $wpua_image = wp_get_attachment_image_src($wpua_meta, "thumbnail");
	        $dimensions = is_numeric($size) ? ' width="'.$size.'" height="'.$size.'"' : "";
	        // Construct the img tag
	 
	        $avatar = '<img src="'.$wpua_image[0].'"'.$dimensions.' alt="'.$alt.'" class="avatar avatar-'.$size.'" />';
	    } else {
	        // Get numeric sizes for non-numeric sizes based on media options
	        if(!function_exists('get_intermediate_image_sizes')){
	            require_once(ABSPATH.'wp-admin/includes/media.php');
	        }
	        // Check for custom image sizes
	        $all_sizes = array_merge(get_intermediate_image_sizes(), array('original'));
	        if(in_array($size, $all_sizes)){
	            if(in_array($size, array('original', 'large', 'medium', 'thumbnail'))){
	                $get_size = ($size == 'original') ? get_option('large_size_w') : get_option($size.'_size_w');
	            } else {
	                $get_size = $_wp_additional_image_sizes[$size]['width'];
	            }
	        } else {
	            // Numeric sizes leave as-is
	            $get_size = $size;
	        }
	         
	        // User with no avatar uses get_avatar
	        $avatar = get_avatar($email, $get_size, $default="", $alt="");
	        // Remove width and height for non-numeric sizes
	        if(in_array($size, array('original', 'large', 'medium', 'thumbnail'))){
	            $avatar = preg_replace('/(width|height)="d*"s/', "", $avatar);
	            $avatar = preg_replace("/(width|height)='d*'s/", "", $avatar);
	        }
	        $str_replacemes = array('wp-user-avatar ', 'wp-user-avatar-'.$get_size.' ', 'wp-user-avatar-'.$size.' ', 'avatar-'.$get_size, 'photo');
	        $str_replacements = array("", "", "", 'avatar-'.$size, 'wp-user-avatar wp-user-avatar-'.$size.$alignclass.' photo');
	 
	        $avatar = str_replace($str_replacemes, $str_replacements, $avatar);
	    }
	    return $avatar;
	}
	
	//Shortcode for frontend
	//A shortcode to display the profile form for authors
	function mb_sua_shortcode() {
		$toReturn = "";
		
		$userId = get_current_user_id();
		
	    if ($userId > 0) {
		    $custom_avatar =  get_the_author_meta('mb_sua_custom_avatar',$userId);
		    
		    if ($custom_avatar) {
			    $cai = wp_get_attachment_image_src( $custom_avatar, 'thumbnail' );
			    $showAvatar = true;
			}
			else {
				$cai[0] = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgDTD2qgAAAAASUVORK5CYII="; //Transparent png
				$showAvatar = false;
			}

			$toReturn .= "
				<div id='mb_sua_avatar_wrap' style='".(($showAvatar)? "": "display: none")."'>
					<a href='#_' class='btn btn-outline-danger mb_sua_remove'>".__("Remove", "mb-sua")."</a>
					<div class='mb_sua_avatar'><img src='".$cai[0]."'></div>
				</div>
			";
		
			$toReturn .= '
				<div class="mb_sua_upload" style="">
					<form type="post" action="" id="mb_sua_submitFileForm" class="" enctype="multipart/form-data">
						<div class="form-group">
							<label for="inputFile">'.__("Select", "mb-sua").'</label>
							<input type="file" id="inputFile" name="inputFile">
						</div>
					</form>
				
					<button type="button" class="btn btn-primary mb_sua_submitFile">'.__("Submit", "mb-sua").'</button>
				</div>
			';
		
		}
	
		return $toReturn;
	}
	add_shortcode('mb-simple-user-avatar', 'mb_sua_shortcode');

	//Remove avatar
	function mb_sua_remove(){
		$toReturn["success"] = false;
	
		if (get_current_user_id() > 0) {
	
			update_user_meta( get_current_user_id(), 'mb_sua_custom_avatar', "" );
			
			$toReturn["success"] = true;
		
		}
			
	    echo json_encode($toReturn);
	
		exit();
	
	}
	add_action('wp_ajax_mb_sua_remove', 'mb_sua_remove');
	add_action('wp_ajax_nopriv_mb_sua_remove', 'mb_sua_remove');

	//Submit a file
	add_action('wp_ajax_nopriv_mb_sua_submitFile', 'mb_sua_submitFile');
	add_action('wp_ajax_mb_sua_submitFile', 'mb_sua_submitFile');
	function mb_sua_submitFile(){
		$toReturn["userId"] = get_current_user_id();
		$toReturn["status"] = "";

		if ( $toReturn["userId"] > 0 ) {			
			
			$upload_dir = wp_upload_dir();
			$target_dir = $upload_dir["path"];
			$target_file = $target_dir . "/" . time() . "." . basename($_FILES["inputFile"]["name"]);
			$target_url = $upload_dir["url"] . "/" . time() . "." . basename($_FILES["inputFile"]["name"]);

			$toReturn["validUpload"] = true;
			
			$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
			
			// Check if image file is a actual image or fake image
			if(isset($_POST["action"])) {
			    $check = getimagesize($_FILES["inputFile"]["tmp_name"]);
			    if($check !== false) {
		//	        echo "File is an image - " . $check["mime"] . ".";
		//	        $uploadOk = 1;
			    } else {
		//	        echo "File is not an image.";
			        $toReturn["validUpload"] = false;
			    }
			}
		
			if ($toReturn["validUpload"]) {
			    if (move_uploaded_file($_FILES["inputFile"]["tmp_name"], $target_file)) {
					$image = $target_url;

					// magic sideload image returns an HTML image, not an ID
					$media = media_sideload_image($image, 0, null, 'id');

					// therefore we must find it so we can set it as featured ID
					if(!empty($media) && !is_wp_error($media)){
						update_user_meta( $toReturn["userId"], 'mb_sua_custom_avatar', $media );
						$custom_avatar =  get_the_author_meta('mb_sua_custom_avatar',$toReturn["userId"]);
						$cai = wp_get_attachment_image_src( $custom_avatar, 'thumbnail' );

						$toReturn["status"] = "success";
						$toReturn["imgUrl"] = $cai[0];

					}
					else {
						$toReturn["media"] = $media;
						$toReturn["wp_error"] = is_wp_error($media);
						$toReturn["status"] = "warning";
						$toReturn["msg"] = "WP error.";
						
					}
		
					unlink($target_file);
					
			    } 
			    else {
					$toReturn["status"] = "warning";
					$toReturn["msg"] = "We could not upload your image.";
			    }
			}
			else {
				$toReturn["status"] = "warning";
				$toReturn["msg"] = "File is not an image.";
			}
		}
	
	    echo json_encode($toReturn);
	    
	    exit(); // this is required to return a proper result
	
	}

	