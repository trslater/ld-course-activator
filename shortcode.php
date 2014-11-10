<?php

/**
 * Creates an activation form with all the needed fields for activating courses with the plugin.
 * 
 * @return output buffer      The html content of the form
 */
function ldca_activation_form_cb() {
  global $ldca;
  
  // Start buffering
  ob_start();
  
  // Check for message 
  
  // Check for global message var first
    
  // If no message is found
  if (! isset($ldca['form_message']['content'])) {
    
    // Check query vars
    
    // If message found in query var
    if (! empty(get_query_var('message_content'))) {
      
      // Create global message var and add content
      $ldca['form_message'] = array(
        'content' => get_query_var('message_content')
      );
      
      // If optional type var is found
      if (! empty(get_query_var('message_type'))) {
        
        // Add it to global var as well
        $ldca['form_message']['type'] = get_query_var('message_type');
      } 
    }
  }
  
  // Check again
  
  // If found
  if (isset($ldca['form_message']['content'])) {
    
    // Prep optional type property
    $message_type = isset($ldca['form_message']['type']) ? ' ' . $ldca['form_message']['type'] : '';
      
    // Add message HTML to buffer
    ?><div class="ldca-message<?php echo $message_type; ?>">
      <?php echo $ldca['form_message']['content']; ?>
    </div><?php
  }
  
  // If error object was generated
  if (isset($ldca['form_errors'])) {
    
    // Chache
    $form_errors = $ldca['form_errors'];
    
  // If not (form not submitted)
  } else {
    
    // Make error object just so error classes prep properly
    $form_errors = new WP_Error();
  }
  
  
  // Prep field error classes
  $product_id_error_class = $form_errors->get_error_message('no_product_id') !== '' ? ' class="error"' : '';
  $licence_email_error_class = $form_errors->get_error_message('no_licence_email') !== '' || $form_errors->get_error_message('invalid_licence_email') !== '' ? ' class="error"' : '';
  $licence_key_error_class = $form_errors->get_error_message('no_licence_key') !== '' ? ' class="error"' : '';
  
  // Prep field values
  $product_id_value = isset($_POST['product_id']) ? ' value="' . esc_attr($_POST['product_id']) . '"' : '';
  $licence_email_value = isset($_POST['licence_email']) ? ' value="' . esc_attr($_POST['licence_email']) . '"' : '';
  $licence_key_value = isset($_POST['licence_key']) ? ' value="' . esc_attr($_POST['licence_key']) . '"' : '';
  
  // Add form HTML to buffer
  ?>
  
  <form class="course-activation-form" method="post">
    <label<?php echo $product_id_error_class; ?>>
      <span class="label-text"><span class="fa fa-key left"></span> Product ID <span class="required">*</span></span>
      <input type="text" name="product_id"<?php echo $product_id_value; ?>>
    </label>
    
    <label<?php echo $licence_email_error_class; ?>>
      <span class="label-text"><span class="fa fa-envelope-o left"></span> Licence Email <span class="required">*</span></span>
      <input type="email" name="licence_email"<?php echo $licence_email_value; ?>>
    </label>
    
    <label<?php echo $licence_key_error_class; ?>>
      <span class="label-text"><span class="fa fa-key left"></span> Licence Key <span class="required">*</span></span>
      <input type="text" name="licence_key"<?php echo $licence_key_value; ?>>
    </label>
    
    <p>
      <button type="submit" name="ldca_submit">
        <span class="fa fa-bolt left"></span> Activate
      </button>
    </p>
    
    <?php wp_nonce_field('ldca_activation', 'ldca_activation_wpnonce'); ?>
  </form>
  <?php
  
  // Return buffer
  return ob_get_clean();
}
add_shortcode('ldca_activation_form', 'ldca_activation_form_cb');



/**
 * Adds query vars for use later
 * 
 * @param  array    $vars   array of query vars
 * @return null
 */
function ldca_query_vars($vars) {
  $vars[] = 'message_content';
  $vars[] = 'message_type';
  
  return $vars;
}
add_filter('query_vars', 'ldca_query_vars')

?>