<?php

/**
 * Creates an activation form with all the needed fields for activating courses with the plugin.
 * 
 * @return output buffer      The html content of the form
 */
function ldca_activation_form_cb() {
  global $ldca_form_message, $ldca_error_fields;
  
  // Start buffering
  ob_start();
  
  // Check for message 
  
  // Check for global message var first
  if (isset($ldca_form_message['content'])) {
    
    // Prep optional type property
    $message_type = isset($ldca_form_message['type']) ? ' ' . $ldca_form_message['type'] : '';
    
    // Message output
    ?><div class="ldca-message<?php echo $message_type; ?>">
      <?php echo $ldca_form_message['content']; ?>
    </div><?php
    
  // If not message is found in global var
  } else {
    
    // Check query vars
    if (! empty(get_query_var('message_content'))) {
      $ldca_form_message = array(
        'content' => get_query_var('message_content'),
        'type' => get_query_var('message_type')
      );
    }
  }
  
  
  // Form output
  ?>
  
  <form class="course-activation-form" method="post">
    <label>
      <span class="label-text"><span class="fa fa-key left"></span> Product ID</span>
      <input type="text" name="product_id" value="<?php echo isset($_POST['product_id']) ? esc_attr($_POST['product_id']) : ''; ?>">
    </label>
    
    <label>
      <span class="label-text"><span class="fa fa-envelope-o left"></span> Licence Email</span>
      <input type="email" name="licence_email" value="<?php echo isset($_POST['licence_email']) ? esc_attr($_POST['licence_email']) : ''; ?>">
    </label>
    
    <label>
      <span class="label-text"><span class="fa fa-key left"></span> Licence Key</span>
      <input type="text" name="licence_key" value="<?php echo isset($_POST['licence_key']) ? esc_attr($_POST['licence_key']) : ''; ?>">
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