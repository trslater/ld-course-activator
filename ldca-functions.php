<?php 

/**
 * This file contains all the functions used in this plugin, except for action hooks and shortcodes
 */



/**
  * Checks to see if the form was filled out correctly
  * 
  * @param   array        $data   contains data about the form, course and product
  * @return  true|false
  */
function ldca_form_ok(&$data) {
  if (! is_array($data)) {
    
  }
  
  $form_errors = array();
  
  // Get post data
  $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : '';
  $licence_email = isset($_POST['licence_email']) ? $_POST['licence_email'] : '';
  $licence_key = isset($_POST['licence_key']) ? $_POST['licence_key'] : '';

  // Validate Product ID
  // If not left blank
  if (! empty($product_id)) {
    $product_id = sanitize_text_field($product_id);
  } else {
    // $form_errors->add('no_product_id', '&lsquo;Product ID&rsquo; was not filled in');
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
    //ldca_display_message($error_content, 'error');
    
    return false;
    
  // If no errors were found
  } else {
    return true;
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