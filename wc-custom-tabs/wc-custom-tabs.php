<?php
/*
Plugin Name: WC Custom Tabs
Description: To show custom data in woocommerce product tabs.
Version: 1.0
Author: Hats
Author URI:
*/
defined('ABSPATH') or die('Don\'t have permission to access the file');

class woocommerceCustomTabs {
      public function __construct() {
        add_action('plugins_loaded', array($this,'checkDependency'));
        add_action('wp_enqueue_scripts', array($this,'enqueue'));
        add_action( 'init', array($this, 'woo_tabs_post_type'));
        add_filter( 'woocommerce_product_tabs', array($this, 'woo_product_tabs'));

        // Add Javascript and CSS for front-end display
      }

      public function checkDependency()
      {
          if (!class_exists('woocommerce') || !is_plugin_active('advanced-custom-fields/acf.php')) {
              add_action('admin_notices',array($this, 'dependencyError'));
              return;
          }
      }
      public function dependencyError()
      {
          ?>
          <div class="error">
              <p>
                  <?php _e('Woo Tabs requires Woocommerce and ACF plugins to work. Please install and activate them.'); ?>
              </p>
          </div>
          <?php
      }
    
      public function enqueue() {
          wp_enqueue_script('deliveryTime', plugins_url('scripts/scripts.js', __FILE__), array('jquery'), '1.0', true);
          wp_localize_script( 'deliveryTime', 'delivery_time', array('ajaxurl' => admin_url('admin-ajax.php')));
      }
      public function woo_tabs_post_type(){
        $labels = array(
                   'name'                => _x( 'Woo Tabs', 'Post Type General Name', 'Woo_tab' ),
                   'singular_name'       => _x( 'Woo Tab', 'Post Type Singular Name', 'Woo_tab' ),
                   'menu_name'           => __( 'Woo Tabs', 'Woo_tab' ),
                   'parent_item_colon'   => __( '', 'Woo_tab' ),
                   'all_items'           => __( 'Woo Tabs', 'Woo_tab' ),
                   'view_item'           => __( '', 'Woo_tab' ),
                   'add_new_item'        => __( 'Add Woo Tab', 'Woo_tab' ),
                   'add_new'             => __( 'Add New', 'Woo_tab' ),
                   'edit_item'           => __( 'Edit Woo Tab', 'Woo_tab' ),
                   'update_item'         => __( 'Update Woo Tab', 'Woo_tab' ),
                   'search_items'        => __( 'Search Woo Tab', 'Woo_tab' ),
                   'not_found'           => __( 'Not found', 'Woo_tab' ),
                   'not_found_in_trash'  => __( 'Not found in Trash', 'Woo_tab' ),
               );
               $args = array(
                   'label'               => __( 'Woo Tabs', 'Woo_tab' ),
                   'description'         => __( 'Custom WooCommerce Tabs', 'Woo_tab' ),
                   'labels'              => $labels,
                   'supports'            => array( 'title', 'editor', 'custom-fields' ),
                   'hierarchical'        => false,
                   'public'              => true,
                   'show_ui'             => true,
                   'show_in_menu'        => true,
                   'show_in_nav_menus'   => false,
                   'show_in_admin_bar'   => true,
                   'menu_position'       => 25,
                   'menu_icon'           => 'dashicons-networking',
                   'can_export'          => true,
                   'has_archive'         => false,
                   'exclude_from_search' => true,
                   'publicly_queryable'  => false,
                   'capability_type'     => 'post',
               );
               register_post_type( 'Woo-tab', $args );	
       }
      public function checkTabCondition(){
        $id = get_the_ID();
        $woo_tab_ids = get_posts(array(
            'fields'          => 'ids',
            'posts_per_page'  => -1,
            'post_type' => 'Woo-tab'
        ));
        
        $tabFieldsArr = array();
        $flag = false; 
        $currentProductWooTabIds = array();
        $currentProductTags = wp_get_post_terms( $id, 'product_tag' );
        $currentProductCats = wp_get_post_terms( $id, 'product_cat' );
        $currentProductTagIds = array();
        $currentProductCatIds = array();

        if( count($currentProductTags) > 0 ){
          foreach($currentProductTags as $currentProductTag){
              $currentProductTagIds[] = $currentProductTag->term_id; // Product tag slug
          }
        }
        if( count($currentProductCats) > 0 ){
          foreach($currentProductCats as $currentProductCat){
              $currentProductCatIds[] = $currentProductCat->term_id; // Product tag slug
          }
        }

        
        foreach ($woo_tab_ids as $woo_tab_id) {
          $tagIds = get_field('tags', $woo_tab_id, true);
          $productIds = get_field('products', $woo_tab_id, true);
          $productCategoriesIds = get_field('product_categories', $woo_tab_id, true);
          
          $tabFieldsArr[$woo_tab_id]['tags'][] = $tagIds;
          $tabFieldsArr[$woo_tab_id]['product_id'][] = $productIds;
          $tabFieldsArr[$woo_tab_id]['product_category'][] = $productCategoriesIds;
        }
        
        foreach ($currentProductTagIds as $currentProductTagId) { 

          foreach ($tabFieldsArr as $tabFieldArrKey => $tabFieldArrValue) { 
            if(in_array($currentProductTagId, $tabFieldArrValue['tags'][0])){
                if(in_array($tabFieldArrKey, $currentProductWooTabIds))
                    continue;
                    
                  $currentProductWooTabIds[] = $tabFieldArrKey;
                  break;
                }
          }
        }

        foreach ($currentProductCatIds as $currentProductCatId) {  

          foreach ($tabFieldsArr as $tabFieldArrKey => $tabFieldArrValue) { 

            if(in_array($currentProductCatId, $tabFieldArrValue['product_category'][0])){
              if(in_array($tabFieldArrKey, $currentProductWooTabIds))
                 continue;
                 
                 $currentProductWooTabIds[] = $tabFieldArrKey;
                 break;
                }
          }
        }

        foreach ($tabFieldsArr as $tabFieldArrKey => $tabFieldArrValue) { 
          if(in_array($id, $tabFieldArrValue['product_id'][0])){
            if(in_array($tabFieldArrKey, $currentProductWooTabIds))
                continue;
                
                $currentProductWooTabIds[] = $tabFieldArrKey;
                break;
              }
        }
        return $currentProductWooTabIds;
      }
      
      public function woo_product_tabs( $tabs ) {
        // Adds the new tab
        $wooCustomTabIds = $this->checkTabCondition();
        if(!empty($wooCustomTabIds)){
          foreach($wooCustomTabIds as $wooCustomTabId){
            $tabData = get_post($wooCustomTabId);
            $title  = $tabData->post_title;
            $content  = $tabData->post_content;
            $tabs[str_replace(' ', '_', $title)] = array(
              'title'     => __( $title, 'woocommerce' ),
              'priority'  => 50,
              'callback'  => array($this, 'woo_new_product_tabs_content'),
              'callback_parameters' => array($this, $content)
            );
          }
        }
          return $tabs;
      }
      public function woo_new_product_tabs_content($name,$tab_arr) {
        echo $tab_arr['callback_parameters'][1];
      }
}
  $woocommerceCustomTabs = new woocommerceCustomTabs();