<?php

/*
Plugin Name: LearnDash Course Activator
Description: This plugin lists terms for a taxonomy for a post.
Version: 1.0.0
Author: Kanso Design
Author URI: http://www.kanso.ca
Description: This plugin is meant to be a link between the WooCommerce Software Addon and LearnDash LMS. LearnDash already has WooCommerce integration, but only when WooCommerce and LearnDash exist on the same WP install. This may not always be desirable or even possible.
*/

/**
 * Init plugin
 */
add_action( "wp_enqueue_scripts", 'ldca_scripts' );
/**
  * Initiates the form validation, product activation and course access
  * 
  * @param   array        $data   contains data about the form, course & product
  * @return  true|false
  */
function ldca_init(&$data) {
  if (!is_array($data)) $data = array();
  
  // Get all available form data
  if (ldca_form_ok($data)) {
    if (ldca_course_exists($data)) {
      if (ldca_not_user_has_access($data)) {
        if (ldca_activate($data)) {
          if (ldca_give_access($data)) {
            ldca_display_message('Course activated successfully!', 'success');
            
            wp_redirect($_SERVER['PHP_SELF']);
          }
        }
      }
    }
  }
}
add_action('init', 'ldca_init');



/**
  * Checks to see if the form was filled out correctly
  * 
  * @param   array        $data   contains data about the form, course and product
  * @return  true|false
  */
function ldca_form_ok(&$data) {
  if (!is_array($data)) $data = array();
  
  // If form submitted
  if (!empty($_POST)) {
    $form_errors = array();
    
    $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : '';
    $licence_email = isset($_POST['licence_email']) ? $_POST['licence_email'] : '';
    $licence_key = isset($_POST['licence_key']) ? $_POST['licence_key'] : '';

    // Validate Product ID
    if (! empty($product_id)) {
      $product_id = sanitize_text_field($product_id);
    } else {
      $form_errors[] = '&lsquo;Product ID&rsquo; was not filled in';
    }
    
    // Validate Licence Email
    if (! empty($licence_email)) {
      $licence_email = sanitize_email($licence_email);
      
      if (!is_email($licence_email)) {
        $form_errors[] = '&lsquo;Licence Email&rsquo; does not appear to be a valid email address';
      }
    } else {
      $form_errors[] = '&lsquo;Licence Email&rsquo; was not filled in';
    }
    
    // Validate Product ID
    if (! empty($licence_key)) {
      $licence_key = sanitize_text_field($licence_key);
    } else {
      $form_errors[] = '&lsquo;Licence Key&rsquo; was not filled in';
    }
    
    $data['form_errors'] = $form_errors;
    $data['product_id'] = $product_id;
    $data['licence_email'] = $licence_email;
    $data['licence_key'] = $licence_key;
    
    // If errors were generated
    if (! empty($data['form_errors'])) {
      
      // Build message
      $error_content  = '<p>Please make sure you have filled out all fields correctly.</p>';
      $error_content .= '<p>Please correct the following errors:</p>';
      $error_content .= '<ul>';
      foreach ($data['form_errors'] as $error) {
        $error_content .= '<li>' . $error . '</li>';
      }
      $error_content .= '</ul>';
      
      // Show errors
      ldca_display_message($error_content, 'error');
      
      return false;
      
    // If no errors were found
    } else {
      return true;
    }
    
  // If form wasn't submitted
  } else {
    // Do nothing
    return false;
  }
}



/**
  * Looks for a course with the entered product ID
  * 
  * @param   array        $data   contains data about the form, course & product
  * @return  true|false
  */
function ldca_course_exists(&$data) {
  if (!is_array($data)) {
    ldca_display_message('An unknown error has occurred', 'error');
    
    return false;
  }
  
  // Get courses with product ID
  $courses = get_posts(array(
    'post_type' => 'sfwd-courses',
    'meta_query' => array(
      array(
        'key' => 'ldca_product_id',
        'value' => $data['product_id'],
        'compare' => '='
      )
    )
  ));
  
  // Cache number of courses returned
  $courses_count = count($courses);
  
  // If found at least one course
  if ($courses_count >= 1) {
    
    // If there is exactly one course
    if ($courses_count == 1) {
      $data['course'] = $courses[0];
      return true;
      
    // If there is more than one...someone messed up :(
    } else {
      ldca_display_message('Error #384. Please contact the vendor of the course.', 'error');
      return false;
    }
  
  // If none were found
  } else {
    ldca_display_message('A course with that Product ID was not found.', 'error');
    return false;
  }
}



/**
  * Check to see if current user has access to the course
  * 
  * @param   array        $data   contains data about the form, course & product
  * @return  true|false
  */
function ldca_not_user_has_access(&$data) {
  if (!is_array($data)) {
    ldca_display_message('An unknown error has occurred', 'error');
    
    return false;
  }
  
  // Grab course data
  $course_data = get_post_meta($data['course']->ID, '_sfwd-courses', true);
  
  // Grab and arrayize access list
  $course_access_list = preg_split('/\s*,\s*/', $course_data['sfwd-courses_course_access_list']);

  // Grab current user id
  $current_user_id = get_current_user_id();
  
  // If user doesn't yet have access to course
  if (! in_array($current_user_id, $course_access_list)) {
    
    // Then continue...
    $data['current_user_id'] = $current_user_id;
    return true;
    
  // If they do already have access to course
  } else {
    ldca_display_message('You already have access to this course.', 'error');
    return false;
  }
}



/**
  * Attempt to activate plugin
  * 
  * @param   array        $data   contains data about the form, course & product
  * @return  true|false
  */
function ldca_activate(&$data) {
  if (!is_array($data)) {
    ldca_display_message('An unknown error has occurred', 'error');
    
    return false;
  }
  
  $response = ldca_request($data['store_url'], 'activation', $data['product_id'], $data['licence_email'], $data['licence_key']);
  
  // If response came back fine
  if (! empty($response['body'])) {
    
    // Get activated status
    $activation_successful = json_decode($response['body'], true)['activated'];
    
    if ($activation_successful) {
      return true;
    } else {
      ldca_display_message('Activation failed.');
      return false;
    }
  }
}



/**
 * Attempt to give current user access to course
 * @param  array        $data   contains data about the form, course & product
 * @return true|false
 */
function ldca_give_access(&$data) {
  if (!is_array($data)) {
    ldca_display_message('An unknown error has occurred', 'error');
    
    return false;
  }
  
  // Grab course custom fields
  $course_data = get_post_meta($data['course']->ID, '_sfwd-courses', true);
  
  // Grab and arrayize access list
  $course_access_list = preg_split('/\s*,\s*/', $course_data['sfwd-courses_course_access_list']);
  
  // Add current user
  $course_access_list[] = $data['current_user_id'];
  
  // Add access list back to course data
  $course_data['sfwd-courses_course_access_list'] = implode(', ', $course_access_list);
  
  // Try to write new data back to course
  if (update_post_meta($data['course']->ID, '_sfwd-courses', $course_data)) {
    
    // If successful, continue...
    return true;
    
  } else {
    ldca_display_message('Unable to give access.', 'error');
    return false;
  }
}



/**
 * Displays a message to the user
 * @param  string   $content    the body of the message
 * @param  string   $type       a type can be set and will be assigned as a class to the message wrapper for styling
 * @return null
 */
function ldca_display_message($content, $type = '') {
  ?><div class="ldca-messages<?php echo ' ' . $type; ?>"><?php echo $content; ?></div><?php
}



/**
 * Basic WooCommerce Software Addon request function
 * 
 * @param  string $request_key
 * @param  string $product_id
 * @param  string $licence_email
 * @param  string $licence_key
 * @return null
 */
function ldca_request($store_url, $request_key, $product_id, $licence_email, $licence_key) {
  $url = "$store_url?wc-api=software-api&request=$request_key&email=$licence_email&licence_key=$licence_key&product_id=$product_id";
  
  return wp_remote_get($url);
}



// Scripts
  
function ldca_scripts() {
  wp_register_style( "font-awesome", "//netdna.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.css", false, "4.2.0" );
  wp_enqueue_style( "font-awesome" );
}


/**
 * Add a meta box to courses for adding/editing the product ID from WooCommerce
 */

// Add meta box
add_action('add_meta_boxes', 'ldca_meta_add');

function ldca_meta_add() {
  add_meta_box('ldca-product-id', 'Product ID', 'ldca_meta_cb', 'sfwd-courses', 'normal', 'high');
}

// Meta box innards
function ldca_meta_cb($post) {
  // delete_post_meta($post->ID, 'ldca_product_id_text');
  $post_custom = get_post_custom($post->ID);
  $product_id = isset($post_custom['ldca_product_id']) ? sanitize_text_field($post_custom['ldca_product_id'][0]) : '';
  
  // Generate nonce for verification
  wp_nonce_field('ldca_product_id_nonce', 'ldca_product_id_nonce_val');
  
  // Text field HTML
  ?><input type="text" name="ldca_product_id" id="ldca-product-id-text" value="<?php echo $product_id; ?>"><?php
}

// Save meta data
add_action('save_post', 'ldca_meta_save');

function ldca_meta_save($post_id) {
  // Bail if we're doing an auto save
  if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
   
  // If our nonce isn't there, or we can't verify it, bail
  if(!isset($_POST['ldca_product_id_nonce_val']) || !wp_verify_nonce($_POST['ldca_product_id_nonce_val'], 'ldca_product_id_nonce')) return;
   
  // If our current user can't edit this post, bail
  if(!current_user_can('edit_post')) return;
  
  if(isset($_POST['ldca_product_id'])) {
    $product_id = sanitize_text_field($_POST['ldca_product_id']);
    update_post_meta($post_id, 'ldca_product_id', $product_id);
  }
}



// Creates a simple shortcode for inserting the activation form. All form validation and activation is set up through this shortcode as well.

add_shortcode('ldca_activation_form', 'ldca_activation_form_cb');

function ldca_activation_form_cb($atts) {
  $a = shortcode_atts( array(
    'store_url' => ''
  ), $atts );
  
  if (empty($a['store_url'])) return;
  
  $data = array(
    'store_url' => $a['store_url'],
    'product_id' => '',
    'licence_email' => '',
    'licence_key' => ''
  );
  
  ldca_init($data);
  
  ldca_display_message($data['message']);
  
  ob_start();
  
  ?>
  <form class="course-activation-form" method="post">
    <label>
      <span class="label-text"><span class="fa fa-key left"></span> Product ID</span>
      <input type="text" name="product_id" value="<?php echo $data['product_id']; ?>">
    </label>
    
    <label>
      <span class="label-text"><span class="fa fa-envelope-o left"></span> Licence Email</span>
      <input type="email" name="licence_email" value="<?php echo $data['licence_email']; ?>">
    </label>
    
    <label>
      <span class="label-text"><span class="fa fa-key left"></span> Licence Key</span>
      <input type="text" name="licence_key" value="<?php echo $data['licence_key']; ?>">
    </label>
    
    <p>
      <button type="submit">
        <span class="fa fa-bolt left"></span> Activate
      </button>
    </p>
  </form>
  <?php
    
  return ob_get_clean();
}







  

?>