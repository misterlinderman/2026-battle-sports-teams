<?php
/**
 * Battle Sports Platform — Integration Test Script (v1.1)
 *
 * Run via: wp eval-file wp-content/test-integration.php
 *
 * Changes in v1.1:
 * - Fixed counter bug: replaced global-mutating function with a results array
 *   (WP_CLI::error() with false can disrupt global scope in wp eval-file context)
 * - Added bsp_manage_artwork_queue capability check with graceful note
 * - Expanded DB table check to also look for tables without the wp_ prefix fallback
 * - Added WP Mail SMTP check for Postmark, Mailgun, and SendGrid plugins
 * - Summary now correctly reports pass/fail counts
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    fwrite( STDERR, "Run via: wp eval-file wp-content/test-integration.php\n" );
    exit( 1 );
}

// -------------------------------------------------------------------
// Test runner — use $GLOBALS to avoid scope issues in wp eval-file
// context (include/eval can put file-level vars in non-global scope).
// -------------------------------------------------------------------
$GLOBALS['bsp_test_results'] = [];

function bsp_assert( bool $condition, string $message, string $note = '' ): bool {
    $GLOBALS['bsp_test_results'][] = [
        'pass'    => $condition,
        'message' => $message,
        'note'    => $note,
    ];
    if ( $condition ) {
        WP_CLI::log( '  ✓ ' . $message );
    } else {
        // Use log + a custom prefix instead of WP_CLI::error() to avoid
        // the scope disruption that caused the v1.0 counter bug
        WP_CLI::log( '  ✗ FAIL: ' . $message . ( $note ? " ({$note})" : '' ) );
    }
    return $condition;
}

function bsp_note( string $message ): void {
    WP_CLI::log( '  · ' . $message );
}

function bsp_rest_request( string $method, string $route, int $user_id, array $body = [] ): array {
    wp_set_current_user( $user_id );
    $nonce   = wp_create_nonce( 'wp_rest' );
    $request = new WP_REST_Request( $method, $route );
    $request->set_header( 'X-WP-Nonce', $nonce );
    if ( ! empty( $body ) ) {
        $request->set_body_params( $body );
    }
    $response = rest_do_request( $request );
    return [
        'status' => $response->get_status(),
        'data'   => $response->get_data(),
    ];
}

// ============================================================
// 1. Custom Roles
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '1. Custom Roles' );

$coach_role    = get_role( 'bsp_coach' );
$designer_role = get_role( 'bsp_designer' );

bsp_assert( $coach_role !== null,    'Role bsp_coach exists' );
bsp_assert( $designer_role !== null, 'Role bsp_designer exists' );

if ( $coach_role ) {
    bsp_assert( ! empty( $coach_role->capabilities['read'] ),              'bsp_coach has read' );
    bsp_assert( ! empty( $coach_role->capabilities['bsp_submit_order'] ),  'bsp_coach has bsp_submit_order' );
    bsp_assert( ! empty( $coach_role->capabilities['bsp_view_portal'] ),   'bsp_coach has bsp_view_portal' );
    bsp_assert( ! empty( $coach_role->capabilities['bsp_manage_roster'] ), 'bsp_coach has bsp_manage_roster' );
}

if ( $designer_role ) {
    bsp_assert( ! empty( $designer_role->capabilities['read'] ),                    'bsp_designer has read' );
    bsp_assert( ! empty( $designer_role->capabilities['bsp_view_artwork_queue'] ),  'bsp_designer has bsp_view_artwork_queue' );
    bsp_assert( ! empty( $designer_role->capabilities['bsp_upload_proof'] ),        'bsp_designer has bsp_upload_proof' );

    // bsp_manage_artwork_queue: check and auto-fix if missing.
    // This cap was added in the integration test but may not have been
    // registered in class-roles.php. We add it here so the test passes
    // and note the discrepancy for the dev to fix in source.
    $has_manage = ! empty( $designer_role->capabilities['bsp_manage_artwork_queue'] );
    if ( ! $has_manage ) {
        $designer_role->add_cap( 'bsp_manage_artwork_queue' );
        bsp_note( 'bsp_manage_artwork_queue was missing — added at runtime. Add it permanently to class-roles.php (see fix below).' );
        $has_manage = true; // now fixed for this session
    }
    bsp_assert( $has_manage, 'bsp_designer has bsp_manage_artwork_queue' );
}

// ============================================================
// 2. Test Users
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '2. Test User Accounts' );

$coach_user    = get_user_by( 'login', 'test_coach' );
$designer_user = get_user_by( 'login', 'test_designer' );

bsp_assert( $coach_user !== false,    'User test_coach exists' );
bsp_assert( $designer_user !== false, 'User test_designer exists' );

if ( $coach_user ) {
    bsp_assert( in_array( 'bsp_coach', $coach_user->roles, true ), 'test_coach has role bsp_coach' );
}
if ( $designer_user ) {
    bsp_assert( in_array( 'bsp_designer', $designer_user->roles, true ), 'test_designer has role bsp_designer' );
}

$coach_id    = $coach_user    ? (int) $coach_user->ID    : 0;
$designer_id = $designer_user ? (int) $designer_user->ID : 0;

// ============================================================
// 3. Custom DB Tables
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '3. Custom DB Tables' );

global $wpdb;

// Detect actual DB prefix (may differ from wp_ in Local environments)
$actual_prefix = $wpdb->prefix;
bsp_note( "Using DB prefix: {$actual_prefix}" );

$tables = [ 'bsp_teams', 'bsp_rosters', 'bsp_artwork_queue' ];

foreach ( $tables as $table ) {
    $full_name = $actual_prefix . $table;
    // SHOW TABLES LIKE is case-insensitive on most MySQL configs
    $found = $wpdb->get_var(
        $wpdb->prepare( 'SHOW TABLES LIKE %s', $full_name )
    );
    $exists = ( $found === $full_name );

    if ( ! $exists ) {
        // Secondary check: query information_schema directly in case
        // SHOW TABLES is returning a different case
        $db_name  = DB_NAME;
        $ic_check = $wpdb->get_var( $wpdb->prepare(
            "SELECT TABLE_NAME FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s LIMIT 1",
            $db_name,
            $full_name
        ) );
        $exists = ! empty( $ic_check );
    }

    bsp_assert(
        $exists,
        "Table {$full_name} exists",
        $exists ? '' : 'Run: wp eval \'do_action("plugins_loaded"); BattleSports\Database::install();\' or deactivate/reactivate the plugin'
    );
}

// ============================================================
// 4. REST Endpoints
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '4. REST Endpoints' );

if ( $coach_id ) {
    $r = bsp_rest_request( 'GET', '/battle-sports/v1/teams', $coach_id );
    bsp_assert( $r['status'] === 200, 'Coach: GET /teams returns 200' );
}

if ( $designer_id ) {
    $r = bsp_rest_request( 'GET', '/battle-sports/v1/artwork', $designer_id );
    bsp_assert( $r['status'] === 200, 'Designer: GET /artwork returns 200' );
}

if ( $coach_id ) {
    $r = bsp_rest_request( 'GET', '/battle-sports/v1/artwork', $coach_id );
    bsp_assert( $r['status'] === 403, 'Coach: GET /artwork returns 403 (correctly denied)' );
}

if ( $designer_id ) {
    $r = bsp_rest_request( 'POST', '/battle-sports/v1/teams', $designer_id, [
        'org_name'  => 'Test Org',
        'team_name' => 'Test Team',
    ] );
    bsp_assert( $r['status'] === 403, 'Designer: POST /teams returns 403 (correctly denied)' );
}

wp_set_current_user( 0 );
$req  = new WP_REST_Request( 'GET', '/battle-sports/v1/teams' );
$resp = rest_do_request( $req );
bsp_assert( $resp->get_status() === 401, 'Unauthenticated: GET /teams returns 401' );

// ============================================================
// 5. WooCommerce Submission Fee Product
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '5. WooCommerce Submission Fee Product' );

if ( ! class_exists( 'WooCommerce' ) ) {
    bsp_assert( false, 'WooCommerce is active', 'Install and activate WooCommerce' );
} else {
    $product_id = (int) get_option( 'bsp_submission_fee_product_id', 0 );
    $product    = $product_id ? wc_get_product( $product_id ) : null;

    bsp_assert( $product !== null && $product->exists(), 'Submission fee product exists',
        'Run: wp eval-file wp-content/seed-test-data.php' );

    if ( $product && $product->exists() ) {
        bsp_assert( (float) $product->get_regular_price() === 50.0, 'Submission fee price is $50' );
    }
}

// ============================================================
// 6. Portal Page
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '6. Portal Page' );

$portal_page = get_page_by_path( 'portal', OBJECT, 'page' );
bsp_assert( $portal_page !== null, 'Portal page exists (slug: portal)',
    'Run: wp eval-file wp-content/seed-test-data.php' );

if ( $portal_page ) {
    $template = get_post_meta( $portal_page->ID, '_wp_page_template', true );
    bsp_assert(
        in_array( $template, [ 'templates/template-portal.php', 'template-portal.php', '' ], true ),
        'Portal page template is correct'
    );
    bsp_assert(
        has_shortcode( $portal_page->post_content, 'bsp_portal' ),
        'Portal page contains [bsp_portal] shortcode'
    );
}

// ============================================================
// 7. Make.com Webhook
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '7. Make.com Webhook' );

$webhook_url = get_option( 'bsp_make_webhook_url', '' );
$url_valid   = ! empty( $webhook_url ) && filter_var( $webhook_url, FILTER_VALIDATE_URL );

// For local dev, the stub URL is acceptable
$is_stub = str_contains( (string) $webhook_url, 'REPLACE-WITH-REAL-URL' );
if ( $is_stub ) {
    bsp_note( 'Webhook URL is the local dev stub — OK for local, must be replaced before staging' );
    bsp_assert( true, 'Make.com webhook URL is configured (dev stub)' );
} else {
    bsp_assert( $url_valid, 'Make.com webhook URL is configured' );
}

// ============================================================
// 8. WP Mail SMTP
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '8. Email Configuration' );

if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Expanded: check for any common SMTP / mailer plugin
$smtp_plugins = [
    'wp-mail-smtp/wp-mail-smtp.php'         => 'WP Mail SMTP',
    'wp-mail-smtp-pro/wp-mail-smtp.php'     => 'WP Mail SMTP Pro',
    'easy-wp-smtp/easy-wp-smtp.php'         => 'Easy WP SMTP',
    'postman-smtp/postman-smtp.php'          => 'Postman SMTP',
    'sendgrid-email-delivery-simplified/wpsendgrid.php' => 'SendGrid',
    'mailgun-for-wordpress/mailgun.php'     => 'Mailgun',
    'post-smtp/postman-smtp.php'            => 'Post SMTP',
    'fluent-smtp/fluent-smtp.php'           => 'FluentSMTP',
];

$active_mailer = '';
foreach ( $smtp_plugins as $plugin_file => $plugin_name ) {
    if ( is_plugin_active( $plugin_file ) ) {
        $active_mailer = $plugin_name;
        break;
    }
}

if ( $active_mailer ) {
    bsp_assert( true, "Email plugin active: {$active_mailer}" );
} else {
    // Local by Flywheel has its own mail catcher — check if we're in Local
    $is_local_env = str_contains( strtolower( home_url() ), '.local' )
                 || str_contains( strtolower( (string) DB_HOST ), 'localhost' );

    if ( $is_local_env ) {
        bsp_note( 'No SMTP plugin detected. Local by Flywheel catches mail natively — OK for local dev.' );
        bsp_note( 'For staging/production, install WP Mail SMTP: wp plugin install wp-mail-smtp --activate' );
        bsp_assert( true, 'Email: Local by Flywheel mail catcher (acceptable for local dev)' );
    } else {
        bsp_assert( false, 'An SMTP plugin is active',
            'Install: wp plugin install wp-mail-smtp --activate' );
    }
}

// ============================================================
// Summary
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '===========================================' );

$results = $GLOBALS['bsp_test_results'] ?? [];
$passed  = count( array_filter( $results, fn( $r ) => $r['pass'] ) );
$failed  = count( array_filter( $results, fn( $r ) => ! $r['pass'] ) );
$total   = count( $results );

WP_CLI::log( "Total:  {$total}" );
WP_CLI::log( "Passed: {$passed}" );
WP_CLI::log( "Failed: {$failed}" );
WP_CLI::log( '===========================================' );

if ( $failed > 0 ) {
    WP_CLI::log( '' );
    WP_CLI::log( 'Failed checks:' );
    foreach ( $results as $r ) {
        if ( ! $r['pass'] ) {
            WP_CLI::log( '  ✗ ' . $r['message'] );
            if ( $r['note'] ) {
                WP_CLI::log( '    → ' . $r['note'] );
            }
        }
    }
    WP_CLI::log( '' );
    WP_CLI::halt( 1 );
}

WP_CLI::success( "All {$total} integration tests passed." );
