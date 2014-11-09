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
 * Include needed files
 */
require_once 'functions.php';
require_once 'meta.php';
require_once 'settings.php';
require_once 'shortcode.php';



/**
 * Initiates the form validation, product activation and course access
 * 
 * @param   array        $data   contains data about the form, course & product
 * @return  true|false
 */
function ldca_init() {
  global $ldca_success, $ldca_form_message;
  
  // Form is considered incomplete by default
  $ldca_success = false;
  
  // Make sure form is posted to itself only
  
  // If no referer, no form, so quit
  if (!isset($_SERVER['HTTP_REFERER'])) return;
  
  // Grab referer and request URI's
  $referer = $_SERVER['HTTP_REFERER'];
  $request = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  
  // Compare and check post for submit
  if ($referer == $request && isset($_POST['ldca_submit'])) {
    
    // Check nonce
    if ( 
        ! isset($_POST['ldca_activation_wpnonce']) ||
        ! wp_verify_nonce($_POST['ldca_activation_wpnonce'], 'ldca_activation')
    ) {
       return;
    }
    
    // Init data object and create WP error object
    $data = array(
      'form_errors' => new WP_Error()
    );
    
    // Get all available form data
    if (ldca_form_ok($data)) {
      if (ldca_course_exists($data)) {
        if (ldca_not_user_has_access($data)) {
          if (ldca_activate($data)) {
            if (ldca_give_access($data)) {
              $ldca_success = true;
            }
          }
        }
      }
    }
    
    // After processing, produce message
    
    // Cache error messages
    $error_messages = $data['form_errors']->get_error_messages();
    
    // If there are any error messages
    if (count($error_messages) > 0) {
      
      $ldca_form_message = array(
        'type' => 'error'
      );
      
      // If there is only error message
      if (count($error_messages) == 1) {
        
        // Produce simple message
        $ldca_form_message['content'] = '<p>' . $error_messages[0] . '</p>';
        
      } else {
        
        // Build complex message
        $ldca_form_message['content'] = '<p>The following errors have occurred:</p><ul>';
        foreach ($error_messages as $error_message) {
          $ldca_form_message['content'] .= '<li>' . $error_message . '</li>';
        }
        $ldca_form_message['content'] .= '</ul>';
      }
      
    // If there are no errors, check for form success
    } else if ($ldca_success) {
      
      // Generate success message
      $ldca_form_message = array(
        'content' => '<p>Course activated successfully!</p>',
        'type' => 'success'
      );
    }      
  }
}
add_action('init', 'ldca_init');
  
  
 
/**
 * Checks for completed form and forwards back to itself to prevent form resubmission on refresh
 * 
 * @return null
 */
function ldca_end_form() {
  global $ldca_success;
  
  // If form complete
  if ($ldca_success) {
    
    // Clear post data
    $_POST = array();
    
    // Create success message
    $message_content = urlencode('<p>Course activtion successful!</p>');
    
    // Redirect to self to avoid repost data on refresh
    
    // Get URI of current page
    $request  = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
    // Check if URI already has a query
    
    // If it does
    if (strpos($request, '?')) {
      
      // Add an ampersand
      $request .= '&';
      
    // If it doesn't
    } else {
      
      // Add a question mark
      $request .= '?';
    }
    
    // Add query vars
    $request .= 'message_content=' . $message_content . '&message_type=success';
    
    wp_redirect($request);
    exit;
  }
}
add_action('send_headers', 'ldca_end_form');



/**
 * Registers all scripts and styles for the plugin
 * 
 * @return null
 */
function ldca_scripts() {
  wp_register_style("font-awesome", "//netdna.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.css", false, "4.2.0");
  wp_enqueue_style("font-awesome");
}
add_action("wp_enqueue_scripts", 'ldca_scripts');

?>