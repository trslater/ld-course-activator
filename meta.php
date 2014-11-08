<?php 



/**
 * This file adds a meta box to courses for adding/editing the product ID from WooCommerce, and handles saving the meta data
 */



/**
 * Add meta box to course editor
 * 
 * @return null
 */
function ldca_meta_box_add() {
  add_meta_box('ldca-product-id', 'Product ID', 'ldca_meta_cb', 'sfwd-courses', 'normal', 'high');
}
add_action('add_meta_boxes', 'ldca_meta_box_add');

  /**
   * The callback for producing the meta box content
   * 
   * @param  WP_Post $post
   * @return null
   */
  function ldca_meta_cb($post) {
    // delete_post_meta($post->ID, 'ldca_product_id_text');
    $post_custom = get_post_custom($post->ID);
    $product_id = isset($post_custom['ldca_product_id']) ? sanitize_text_field($post_custom['ldca_product_id'][0]) : '';
    
    // Generate nonce for verification
    wp_nonce_field('ldca_product_id_nonce', 'ldca_product_id_nonce_val');
    
    // Text field HTML
    ?><input type="text" name="ldca_product_id" id="ldca-product-id-text" value="<?php echo $product_id; ?>"><?php
  }



/**
 * Saves metadata entered into meta box fields
 * 
 * @param  int    $post_id
 * @return null
 */
function ldca_meta_save($post_id) {
  // Bail if we're doing an auto save
  if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
   
  // If our nonce isn't there, or we can't verify it, bail
  if(!isset($_POST['ldca_product_id_nonce_val']) || !wp_verify_nonce($_POST['ldca_product_id_nonce_val'], 'ldca_product_id_nonce')) return;
   
  // If our current user can't edit this post, bail
  if(!current_user_can('edit_post')) return;
  
  if(isset($_POST['ldca_product_id'])) {
    $product_id = sanitize_text_field($_POST['ldca_product_id']);
    update_post_meta($post_id, 'ldca_product_id', $product_id);
  }
}
add_action('save_post', 'ldca_meta_save');



?>