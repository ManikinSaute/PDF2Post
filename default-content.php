<?php


function pdf2p2_create_gb_post( $title, $content ) {
    $post_args = [
        'post_type'    => 'pdf2p2_gutenberg',
        'post_title'   => sanitize_text_field( $title ),
        'post_content' => wp_kses_post( $content ),
        'post_status'  => 'publish',
        'tax_input'    => [
            'status' => [ 'staff-verified' ],
        ],
    ];

    return wp_insert_post( $post_args );
}

register_activation_hook( __FILE__, 'pdf2p2_import_md_example_on_activate' );

function pdf2p2_import_md_example_on_activate() {
    $file_path = plugin_dir_path( __FILE__ ) . 'md-example.txt';

    if ( ! file_exists( $file_path ) ) {
        error_log( 'pdf2p2_import: md-example.txt not found.' );
        return;
    }

    $content = file_get_contents( $file_path );
    if ( false === $content ) {
        error_log( 'pdf2p2_gb_import: failed to read md-example.txt' );
        return;
    }

     $result = pdf2p2_create_gb_post( 'Markdown Cheat Sheet', $content );

    if ( is_wp_error( $result ) ) {
        error_log( 'pdf2p2_gb_import error: ' . $result->get_error_message() );
    } else {
        error_log( 'pdf2p2_gb_import: created post ID ' . $result );
    }
}
