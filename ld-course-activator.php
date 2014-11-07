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
require_once './ldca-functions.php';
require_once './ldca-meta.php';
require_once './ldca-settings.php';
require_once './ldca-shortcode.php';



/**
 * Initiates the form validation, product activation and course access
 * 
 * @param   array        $data   contains data about the form, course & product
 * @return  true|false
 */
function ldca_init() {
  
  // Before doing anything, check for form submission
  if (isset($_POST)) {
    global $ldca_complete;
    
    $ldca_complete = false;  
    $data = array(
      'form_errors' => new WP_Error()
    );
    
    // Get all available form data
    if (ldca_form_ok($data)) {
      if (ldca_course_exists($data)) {
        if (ldca_not_user_has_access($data)) {
          if (ldca_activate($data)) {
            if (ldca_give_access($data)) {
              $ldca_complete = true;
              // ldca_display_message('Course activated successfully!', 'success');
            }
          }
        }
      }
    }
  }
}
add_action('init', 'ldca_init');
  
  
 
/**
 * Checks for completed form
 * 
 * @return null
 */
function ldca_end_form() {
  global $ldca_complete;
  
  // If form complete
  if ($ldca_complete) {
    
    // Redirect to self to avoid repost data on refresh
    header('Location: ' . $_SERVER['PHP_SELF']);
  }
}
add_action('send_headers', 'ldca_end_form');



/**
 * Registers all scripts and styles for the plugin
 * @return null
 */
function ldca_scripts() {
  wp_register_style("font-awesome", "//netdna.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.css", false, "4.2.0");
  wp_enqueue_style("font-awesome");
}
add_action("wp_enqueue_scripts", 'ldca_scripts');

  

?>