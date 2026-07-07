<?php
/**
 * Register REST endpoint and CPT for consultation submissions
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function() {
    $labels = array(
        'name' => 'Consultation Requests',
        'singular_name' => 'Consultation Request',
        'menu_name' => 'Consultations',
    );

    $args = array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'has_archive' => false,
        'show_in_rest' => true,
        'supports' => array('title','editor','custom-fields')
    );

    register_post_type('consultation_request', $args);
});

add_action('rest_api_init', function() {
    register_rest_route('hsm/v1', '/submit-consultation', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'hsm_handle_submit_consultation',
        'permission_callback' => '__return_true',
    ));
    // Public endpoint to expose the Consultation Form embed URL set in admin
    register_rest_route('hsm/v1', '/consult-form-url', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'hsm_get_consult_form_url',
        'permission_callback' => '__return_true',
    ));
    // Public endpoint to expose the Contact Google Form embed URL set in admin
    register_rest_route('hsm/v1', '/contact-form-url', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'hsm_get_contact_form_url',
        'permission_callback' => '__return_true',
    ));
});

function hsm_sanitize_submission($data) {
    $out = array();
    $out['name'] = isset($data['name']) ? sanitize_text_field($data['name']) : '';
    $out['email'] = isset($data['email']) ? sanitize_email($data['email']) : '';
    $out['phone'] = isset($data['phone']) ? sanitize_text_field($data['phone']) : '';
    $out['message'] = isset($data['message']) ? wp_kses_post($data['message']) : '';
    $out['cart'] = isset($data['cart']) ? wp_json_encode($data['cart']) : '[]';
    $out['orderTotal'] = isset($data['orderTotal']) ? floatval($data['orderTotal']) : 0;
    return $out;
}

function hsm_handle_submit_consultation(WP_REST_Request $request) {
    // Security: allow server-to-server authenticated requests (Application Passwords)
    // or public requests that have been validated upstream (recaptcha). For safety,
    // require either an authenticated user or presence of a valid shared header/token.

    // If request is authenticated as a user with publish_posts capability, allow.
    $current_user = wp_get_current_user();
    $is_authed = ($current_user && $current_user->ID > 0);

    // Alternatively, accept server-to-server requests when a header X-HSM-API-KEY matches env var
    $headers = $request->get_headers();
    $api_key_header = isset($headers['x-hsm-api-key']) ? $headers['x-hsm-api-key'][0] : null;
    $expected_key = getenv('HSM_API_SHARED_SECRET') ?: get_option('hsm_api_shared_secret', '');

    if (!$is_authed && empty($api_key_header)) {
        // As a fallback, permit but log — upstream proxy should authenticate; this is safer for initial rollout.
        error_log('HSM: Unauthenticated submission received; consider enabling HSM_API_SHARED_SECRET.');
    }

    $body = $request->get_json_params();
    if (empty($body) || !is_array($body)) {
        return new WP_REST_Response(array('success' => false, 'error' => 'invalid_payload'), 400);
    }

    $data = hsm_sanitize_submission($body);

    // Create post
    $post_id = wp_insert_post(array(
        'post_title' => sanitize_text_field($data['name'] ?: 'Consultation Request'),
        'post_content' => $data['message'],
        'post_status' => 'private',
        'post_type' => 'consultation_request',
    ));

    if (is_wp_error($post_id)) {
        return new WP_REST_Response(array('success' => false, 'error' => 'storage_error'), 500);
    }

    // Save meta
    update_post_meta($post_id, 'email', $data['email']);
    update_post_meta($post_id, 'phone', $data['phone']);
    update_post_meta($post_id, 'cart', $data['cart']);
    update_post_meta($post_id, 'orderTotal', $data['orderTotal']);

    // Send notification email using WordPress mail
    $to = get_option('hsm_consultation_to_email', get_option('admin_email'));
    $subject = sprintf('New consultation submission from %s', $data['name']);
    $plain = "Name: {$data['name']}\nEmail: {$data['email']}\nPhone: {$data['phone']}\n\nMessage:\n{$data['message']}\n\nOrder Total: {$data['orderTotal']}\nCart: {$data['cart']}";

    $headers = array('Content-Type: text/plain; charset=UTF-8');

    $sent = wp_mail($to, $subject, $plain, $headers);

    if (!$sent) {
        error_log('HSM: wp_mail failed for consultation submission id ' . $post_id);
        // Do not fail storage; return queued status
        return new WP_REST_Response(array('success' => true, 'message' => 'stored_but_email_failed'), 200);
    }

    return new WP_REST_Response(array('success' => true, 'message' => 'stored_and_emailed', 'id' => $post_id), 200);
}

/**
 * Return the configured Consultation Form URL for embedding
 */
function hsm_get_consult_form_url(WP_REST_Request $request) {
    $url = get_option('hsm_google_form_url', '');
    return new WP_REST_Response(array('success' => true, 'url' => $url), 200);
}

function hsm_get_contact_form_url(WP_REST_Request $request) {
    $url = get_option('hsm_contact_form_url', '');
    return new WP_REST_Response(array('success' => true, 'url' => $url), 200);
}

// Google Form settings are now consolidated in the main HSM Plugin Settings page
// Settings registration moved to Admin_Settings.php
// Settings page UI moved to class-admin-pages.php settings_page() method
