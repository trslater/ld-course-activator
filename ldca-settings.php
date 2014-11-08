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
  
  // Attach general section to general page
  add_settings_section(
    'ldca-settings-section-all',
    '',
    'ldca_settings_section_all_cb',
    'ldca-settings-page-general'
  );
  
  // Add field to section
  add_settings_field(
    'ldca-settings-field-store-url',
    'Store URL',
    'ldca_settings_field_store_url_cb',
    'ldca-settings-page-general',
    'ldca-settings-section-all'
  );
}
add_action('admin_init','ldca_settings_init');



/**
 * Build actual content of settings page
 * 
 * @return null
 */
function ldca_settings_page_general_cb() {
  
  // HTML Output
  ?>
  
  <div class="wrap">
    <h2>LearnDash Course Activator Settings</h2>
    
    <form method="post" action="options.php">
      <?php
      
      // Output section, defined here: ldca_settings_section_all_cb
      settings_fields('ldca_settings_general');
      
      // Output fields, defined here: ldca_settings_field_store_url_cb
      do_settings_sections('ldca-settings-page-general');
      
      submit_button();
      
      ?>
    </form>
  </div>
  
  <?php
}



/**
 * Sets up the section to hold the fields
 * 
 * @return null
 */
function ldca_settings_section_all_cb() {
  // Empty...
}



/**
 * Handles logic and HTML output for store URL field
 * 
 * @return null
 */
function ldca_settings_field_store_url_cb() {
  
  $val = esc_attr(get_option('store_url'));
  
  ?>
  
  <input type="text" name="store_url" value="<?php echo $val; ?>" size="40">
  
  <?php
}

?>