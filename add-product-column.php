<?php
  // Add column, _kunaki_product_id to the products edit list page
  add_filter( 'manage_edit-product_columns', 'show_product_order', 10, 3 );
  function show_product_order($columns, $int1=0, $int2=0){
   //add column
   $columns['_kunaki_product_id'] = __( 'Kunaki ID');
   return $columns;
  }

  // Fill _kunaki_product_id column with content
  add_action( 'manage_product_posts_custom_column', 'woocommerce_kunaki_product_column_kunaki_product_id', 10, 2 );
  function woocommerce_kunaki_product_column_kunaki_product_id( $column, $postid ) {
    if ( $column == '_kunaki_product_id' ) {
        echo get_post_meta( $postid, '_kunaki_product_id', true );
    }
  }
?>
