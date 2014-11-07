<?php

/**
 * Creates an activation form with all the needed fields for activating courses with the plugin.
 * 
 * @return output buffer      The html content of the form
 */
function ldca_activation_form_cb() {
  ob_start();
  
  ?>
  <form class="course-activation-form" method="post">
    <label>
      <span class="label-text"><span class="fa fa-key left"></span> Product ID</span>
      <input type="text" name="product_id" value="<?php //echo $data['product_id']; ?>">
    </label>
    
    <label>
      <span class="label-text"><span class="fa fa-envelope-o left"></span> Licence Email</span>
      <input type="email" name="licence_email" value="<?php //echo $data['licence_email']; ?>">
    </label>
    
    <label>
      <span class="label-text"><span class="fa fa-key left"></span> Licence Key</span>
      <input type="text" name="licence_key" value="<?php //echo $data['licence_key']; ?>">
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
add_shortcode('ldca_activation_form', 'ldca_activation_form_cb');

?>