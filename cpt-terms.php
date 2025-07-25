<?php

function pdf2p2_register_cpts() {
    $cpts = [
        'pdf2p2_import'    => ['singular' => 'pdf2p2 Import',    'plural' => 'pdf2p2 Imports'],
        'pdf2p2_gutenberg' => ['singular' => 'pdf2p2 GB Post',   'plural' => 'pdf2p2 GB Posts'],
    ];
    foreach ( $cpts as $slug => $labels ) {
        register_post_type( $slug, [
            'labels'       => [
                'name'          => $labels['plural'],
                'singular_name' => $labels['singular'],
            ],
            'public'       => false,
            'show_ui'      => true,
            'has_archive'  => false,
            'menu_position'=> 20,
            'supports'     => [ 'title', 'editor', 'custom-fields' ],
        ]);
    }
}
add_action( 'init', 'pdf2p2_register_cpts' );

// Register single‑select “Status” taxonomy and create terms
function pdf2p2_register_status_taxonomy() {
    $tax   = 'status';
    $cpts  = [ 'pdf2p2_import', 'pdf2p2_gutenberg' ];
    $labels = [
        'name'          => 'Statuses',
        'singular_name' => 'Status',
        'menu_name'     => 'Status',
        'all_items'     => 'All Statuses',
        'add_new_item'  => 'Add New Status',
        'edit_item'     => 'Edit Status',
    ];
    register_taxonomy( $tax, $cpts, [
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'show_admin_column' => true,
        'hierarchical'      => false,
        'meta_box_cb'       => 'pdf2p2_status_meta_box_cb',
        'rewrite'           => false,
    ]);
    $terms = [
        'unprocessed'    => 'Unprocessed',
        'ocr_processed'  => 'OCR Processed',
        'human_verified' => 'Human Verified',
        'staff_verified'       => 'Staff Verified',
    ];
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $tax ) ) {
            wp_insert_term( $name, $tax, [ 'slug' => $slug ] );
        }
    }
}
add_action( 'init', 'pdf2p2_register_status_taxonomy' );

//  Show “Status” custom term in the Meta‑box when editing a single post 
function pdf2p2_status_meta_box_cb( $post, $box ) {
    $tax     = 'status';
    $terms   = get_terms( [ 'taxonomy' => $tax, 'hide_empty' => false ] );
    $current = wp_get_object_terms( $post->ID, $tax, [ 'fields' => 'slugs' ] );
    $current = $current ? $current[0] : '';
    echo '<div>';
    foreach ( $terms as $term ) {
        printf(
            '<label style="display:block; margin-bottom:4px;">
               <input type="radio" name="%1$s[]" value="%2$s" %3$s> %4$s
             </label>',
            esc_attr( $tax ),
            esc_attr( $term->slug ),
            checked( $current, $term->slug, false ),
            esc_html( $term->name )
        );
    }
    echo '</div>';
}

// Show “Status” custom term in the CPT list tables
add_action( 'admin_init', function() {
    $post_types = [ 'pdf2p2_import', 'pdf2p2_gutenberg' ];
    foreach ( $post_types as $pt ) {
        // register the column header
        add_filter( "manage_{$pt}_posts_columns", function( $cols ) {
            $cols['status'] = __( 'Status', 'pdf2p2' );
            return $cols;
        } );
        //  fill in each row
        add_action( "manage_{$pt}_posts_custom_column", function( $column, $post_id ) {
            if ( 'status' !== $column ) {
                return;
            }
            $terms = get_the_terms( $post_id, 'status' );
            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                $names = wp_list_pluck( $terms, 'name' );
                echo esc_html( implode( ', ', $names ) );
            } else {
                echo '—';
            }
        }, 10, 2 );
    }
} );

// Save the “Status” custom term when a post is saved
add_action( 'save_post', 'pdf2p2_save_status_taxonomy', 10, 2 );
function pdf2p2_save_status_taxonomy( $post_id, $post ) {
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( ! in_array( $post->post_type, [ 'pdf2p2_import', 'pdf2p2_gutenberg' ], true ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( ! empty( $_POST['status'] ) && is_array( $_POST['status'] ) ) {
        $term = sanitize_text_field( wp_unslash( $_POST['status'][0] ) );
        wp_set_object_terms( $post_id, $term, 'status', false );
    } else {
        // Clear if nothing selected
        wp_set_object_terms( $post_id, [], 'status', false );
    }
}
