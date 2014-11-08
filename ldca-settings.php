<?php

/**
 * Create the options page under 'Settings' in wp-admin
 * 
 * @return null
 */
function ldca_settings() {
  add_options_page('LearnDash Course Activator', 'LearnDash Course Activator', 'manage_options', 'ldca-settings-page-general', 'ldca_settings_page_general_cb');
}
add_action('admin_menu','ldca_settings');



/**
 * Setup for settings, sections and fields
 * 
 * @return null
 */
function ldca_settings_init() {
  register_setting(
    'ldca_settings_general',
    'store_url',
    'esc_url_raw'
  );
}
add_action('admin_init','ldca_settings_init');



/**
 * Build actual content of settings page
 * 
 * @return null
 */
function ldca_settings_page_general_cb() {
  
  $store_url_val = esc_attr(get_option('store_url'));
  
  // HTML Output
  ?>
  
  <div class="wrap">
    <h2>LearnDash Course Activator Settings</h2>
    
    <form method="post" action="options.php">
      <?php settings_fields('ldca_settings_general'); ?>
      
      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row">Store URL</th>
            
            <td>
              <input type="text" name="store_url" value="<?php echo $store_url_val; ?>" size="40">
            </td>
          </tr>
        </tbody>
      </table>
  
      <?php submit_button(); ?>
    </form>
  </div>
  
  <?php
}

?>