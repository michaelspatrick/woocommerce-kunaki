<?php
  // Add Kunaki Product ID to the Admin Form
  add_action( 'woocommerce_product_options_shipping', 'woo_add_kunaki_field' );
  function woo_add_kunaki_field() {
    Global $post;
    echo "<hr>";
    woocommerce_wp_textarea_input(
				array(
					'id'          => '_kunaki_product_id',
					'label'       => __( 'Kunaki Product ID(s)', 'woocommerce' ),
					'placeholder' => 'PXXXXXXXXX,PXXXXXXXXX,...',
					'description' => __( 'For Kunaki products, enter the Kunaki Product ID(s) here.', 'woocommerce' ),
					//'value'       => get_post_meta( $post->ID, '_textarea', true ),
				)
			);
  }

  add_action('woocommerce_process_product_meta', 'woo_add_custom_kunaki_save');
  function woo_add_custom_kunaki_save($post_id){
    if(!empty( $_POST['_kunaki_product_id'])) update_post_meta( $post_id, '_kunaki_product_id', esc_attr( $_POST['_kunaki_product_id'] ) );
      else delete_post_meta( $post_id, '_kunaki_product_id' );
  }
?>
