<?php 

/**
 * This file contains all the functions used in this plugin, except for action hooks and shortcodes
 */



/**
  * Validator!
  * 
  * @param   array        $data   contains data about the form, course and product
  * @return  true|false
  */
function ldca_form_ok(&$data) {
  
  // Assume failure
  $success = false;
  
  // Make sure data is being passed in
  if (is_array($data)) {
    global $ldca_form_message;
    
    // Get post data
    $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : '';
    $licence_email = isset($_POST['licence_email']) ? $_POST['licence_email'] : '';
    $licence_key = isset($_POST['licence_key']) ? $_POST['licence_key'] : '';
    
    // Cache error object
    $form_errors = $data['form_errors'];

    // Validate Product ID
    
    // If not left blank
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
    
    // Validate Product ID
    
    // If left blank
    if (empty($licence_key)) {
      
      // Generate error
      $form_errors->add('no_product_id', '&lsquo;Licence Key&rsquo; was not filled in');
      
    } else {
      // Sanitize
      $licence_key = sanitize_text_field($licence_key);
    }
    
    // Check for errors
    
    // Cache messages
    $error_messages = $form_errors->get_error_messages();
    
    // If errors were generated
    if (count($error_messages) >= 1) {
      
      // Build message
      $message_content  = '<p>Please make sure you have filled out all fields correctly.</p>';
      $message_content .= '<p>Please correct the following errors:</p>';
      $message_content .= '<ul>';
      foreach ($error_messages as $message) {
        $message_content .= '<li>' . $message . '</li>';
      }
      $message_content .= '</ul>';
      
      // Add to global message variable
      $ldca_form_message = array(
        'type' => 'error',
        'content' => $message_content
      );
      
    // If no errors were found
    } else {
      $success = true;
    }
    
    // Add everything back to data
    $data['product_id'] = $product_id;
    $data['licence_email'] = $licence_email;
    $data['licence_key'] = $licence_key;
    $data['form_errors'] = $form_errors;
  }
  
  return $success;
}



/**
  * Looks for a course with the entered product ID
  * 
  * @param   array        $data   contains data about the form, course & product
  * @return  true|false
  */
function ldca_course_exists(&$data) {
  if (!is_array($data)) {
    //ldca_display_message('An unknown error has occurred', 'error');
    
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
      //ldca_display_message('Error #384. Please contact the vendor of the course.', 'error');
      return false;
    }
  
  // If none were found
  } else {
    //ldca_display_message('A course with that Product ID was not found.', 'error');
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
    //ldca_display_message('An unknown error has occurred', 'error');
    
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
    //ldca_display_message('You already have access to this course.', 'error');
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
    //ldca_display_message('An unknown error has occurred', 'error');
    
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
      //ldca_display_message('Activation failed.');
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
    //ldca_display_message('An unknown error has occurred', 'error');
    
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
    //ldca_display_message('Unable to give access.', 'error');
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

?>