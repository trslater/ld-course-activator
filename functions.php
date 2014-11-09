<?php 

/**
 * Functions
 * 
 * This file contains all the functions used in this plugin, except for action hooks and shortcodes
 */



/**
  * Validator!
  * 
  * @param   array        $data   contains data about the form, course and product
  * @return  true|false
  */
function ldca_form_ok(&$data) {
  global $ldca_form_message, $ldca_field_data;
  
  // Assume failure
  $success = false;
    
  // Get post data
  $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : '';
  $licence_email = isset($_POST['licence_email']) ? $_POST['licence_email'] : '';
  $licence_key = isset($_POST['licence_key']) ? $_POST['licence_key'] : '';
  
  // Cache data
  $form_errors = $data['form_errors'];
  
  // Validate Product ID
  
  // If left blank
  if (empty($product_id)) {
    
    // Generate error
    $form_errors->add('no_product_id', '&lsquo;Product ID&rsquo; was not filled in');
    
  } else {
    
    // Sanitize
    $product_id = sanitize_text_field($product_id);
  }
  
  // Validate Licence Email
  
  // If left blank
  if (empty($licence_email)) {
    
    // Generate error
    $form_errors->add('no_licence_email', '&lsquo;Licence Email&rsquo; was not filled in');
    
  } else {
    
    // Sanitize
    $licence_email = sanitize_email($licence_email);
    
    // If not a proper email address
    if (! is_email($licence_email)) {
      $form_errors->add('invalid_licence_email', '&lsquo;Licence Email&rsquo; does not appear to be a valid email address');
    }
  }
  
  // Validate Licence Key
  
  // If left blank
  if (empty($licence_key)) {
    
    // Generate error
    $form_errors->add('no_licence_key', '&lsquo;Licence Key&rsquo; was not filled in');
    
  } else {
    // Sanitize
    $licence_key = sanitize_text_field($licence_key);
  }
  
  // If no errors
  if (count($form_errors->get_error_messages()) < 1) {
    
    // We did it!
    $success = true;
    
  }
  
  // Add cached vars back to data
  $data['form_errors'] = $form_errors;
  
  // Store form data for user later
  $data['product_id'] = $product_id;
  $data['licence_email'] = $licence_email;
  $data['licence_key'] = $licence_key;
  
  return $success;
}



/**
  * Looks for a course with the entered product ID
  * 
  * @param   array        $data   contains data about the form, course & product
  * @return  true|false
  */
function ldca_course_exists(&$data) {
  global $ldca_form_message;
  
  // Assume failure
  $success = false;
  
  // Cache data
  $form_errors = $data['form_errors'];
  $product_id = $data['product_id'];
  
  // Get courses with product ID
  $courses = get_posts(array(
    'post_type' => 'sfwd-courses',
    'meta_query' => array(
      array(
        'key' => 'ldca_product_id',
        'value' => $product_id,
        'compare' => '='
      )
    )
  ));
  
  // Cache number of courses returned
  $courses_count = count($courses);
  
  // If no courses are found
  if ($courses_count < 1) {
    
    // Generate error
    $form_errors->add('course_not_found', 'A course with that Product ID was not found.');
    
  } else {
    
    // If there is more than one course
    if ($courses_count > 1) {
      
      // Generate error
      $form_errors->add('duplicate_product_ids', 'There was an internal error. Please contact the store administrator.');
      
    } else {
      
      // Add course to data for later use
      $data['course'] = $courses[0];
      
      // We did it!
      $success = true;
    }
  }
  
  // Add chaced vars back to data
  $data['form_errors'] = $form_errors;
  
  return $success;
}



/**
  * Check to see if current user DOESN'T has access to the course
  * 
  * @param   array        $data   contains data about the form, course & product
  * @return  true|false
  */
function ldca_not_user_has_access(&$data) {
  global $ldca_form_message;
  
  // Assume failure
  $success = false;
  
  // Cache data
  $form_errors = $data['form_errors'];
  $course = $data['course'];
  
  // Grab course data
  $course_data = get_post_meta($course->ID, '_sfwd-courses', true);
  
  // Grab and arrayize access list
  $course_access_list = preg_split('/\s*,\s*/', $course_data['sfwd-courses_course_access_list']);

  // Grab current user id
  $current_user_id = get_current_user_id();
  
  // If user already has access to the course
  if (in_array($current_user_id, $course_access_list)) {

    // Generate error
    $form_errors->add('user_already_has_access', 'You already have access to this course.');
    
  } else {
    
    // We did it!
    $success = true;
  }
  
  // Add chaced vars back to data
  $data['form_errors'] = $form_errors;
  
  // Store user ID for later
  $data['current_user_id'] = $current_user_id;
  
  return $success;
}



/**
  * Attempt to activate plugin
  * 
  * @param   array        $data   contains data about the form, course & product
  * @return  true|false
  */
function ldca_activate(&$data) {
  global $ldca_form_message;
  
  // Assume failure
  $success = false;
  
  // Cache data
  $form_errors = $data['form_errors'];
  $product_id = $data['product_id'];
  $licence_email = $data['licence_email'];
  $licence_key = $data['licence_key'];
  
  // Get store URL from settings
  $store_url = get_option('store_url');
  
  if (! $store_url) {
    
    // Generate error
    
  } else {
    
    // Make request to API
    $response = ldca_request($store_url, 'activation', $data['product_id'], $data['licence_email'], $data['licence_key']);
    
    // If response came back fine
    if (isset($response['response']['code']) && $response['response']['code'] == '200') {
      
      // Get activated status
      $activation_successful = json_decode($response['body'], true)['activated'];
      
      if ($activation_successful) {
        return true;
      } else {
        //ldca_display_message('Activation failed.');
        return false;
      }
    }
  }

  return $success;
}



/**
 * Attempt to give current user access to course
 * @param  array        $data   contains data about the form, course & product
 * @return true|false
 */
function ldca_give_access(&$data) {
  global $ldca_form_message;
  
  // Assume failure
  $success = false;
  
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
    //ldca_display_message('Unable to give access.', 'error');
    return false;
  }
  
  return $success;
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

?>