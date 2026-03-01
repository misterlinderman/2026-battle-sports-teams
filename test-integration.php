<?php
/**
 * Battle Sports Platform — Integration Test Script
 *
 * Run via: wp eval-file test-integration.php
 *
 * Must be run from site root, e.g.:
 *   cd /path/to/wp && wp eval-file wp-content/test-integration.php
 *
 * Verifies:
 * 1. Custom roles (bsp_coach, bsp_designer) and capabilities
 * 2. Test user accounts exist for each role
 * 3. Custom DB tables exist (wp_bsp_teams, wp_bsp_rosters, wp_bsp_artwork_queue)
 * 4. REST endpoints respond correctly for each role
 * 5. WooCommerce submission fee product exists and price is $50
 * 6. Portal page exists with correct template
 * 7. Make.com webhook URL is configured
 * 8. WP Mail SMTP is active
 *
 * @package Battle_Sports_Platform
 */

if (!defined('WP_CLI') || !WP_CLI) {
    fwrite(STDERR, "This script must be run via: wp eval-file test-integration.php\n");
    exit(1);
}

/** @var int */
$passed = 0;
/** @var int */
$failed = 0;
/** @var array<int, string> */
$errors = [];

function bsp_test_assert(bool $condition, string $message): void {
    global $passed, $failed, $errors;
    if ($condition) {
        $passed++;
        WP_CLI::log('  ✓ ' . $message);
    } else {
        $failed++;
        $errors[] = $message;
        WP_CLI::error('  ✗ ' . $message, false);
    }
}

function bsp_rest_request(string $method, string $route, int $user_id, array $body = []): array {
    wp_set_current_user($user_id);
    $nonce = wp_create_nonce('wp_rest');
    $request = new WP_REST_Request($method, $route);
    $request->set_header('X-WP-Nonce', $nonce);
    if (!empty($body)) {
        $request->set_body_params($body);
    }
    $response = rest_do_request($request);
    return [
        'status' => $response->get_status(),
        'data'   => $response->get_data(),
        'error'  => is_wp_error($response->get_data()) ? $response->get_data()->get_error_message() : null,
    ];
}

// --- 1. Roles ---
WP_CLI::log('');
WP_CLI::log('1. Custom Roles');
$coach_role = get_role('bsp_coach');
$designer_role = get_role('bsp_designer');

bsp_test_assert($coach_role !== null, 'Role bsp_coach exists');
bsp_test_assert($designer_role !== null, 'Role bsp_designer exists');

if ($coach_role) {
    bsp_test_assert(!empty($coach_role->capabilities['read']), 'bsp_coach has read');
    bsp_test_assert(!empty($coach_role->capabilities['bsp_submit_order']), 'bsp_coach has bsp_submit_order');
    bsp_test_assert(!empty($coach_role->capabilities['bsp_view_portal']), 'bsp_coach has bsp_view_portal');
    bsp_test_assert(!empty($coach_role->capabilities['bsp_manage_roster']), 'bsp_coach has bsp_manage_roster');
}
if ($designer_role) {
    bsp_test_assert(!empty($designer_role->capabilities['read']), 'bsp_designer has read');
    bsp_test_assert(!empty($designer_role->capabilities['bsp_view_artwork_queue']), 'bsp_designer has bsp_view_artwork_queue');
    bsp_test_assert(!empty($designer_role->capabilities['bsp_upload_proof']), 'bsp_designer has bsp_upload_proof');
    bsp_test_assert(!empty($designer_role->capabilities['bsp_manage_artwork_queue']), 'bsp_designer has bsp_manage_artwork_queue');
}

// --- 2. Test Users ---
WP_CLI::log('');
WP_CLI::log('2. Test User Accounts');
$coach_user = get_user_by('login', 'test_coach');
$designer_user = get_user_by('login', 'test_designer');

bsp_test_assert($coach_user !== false, 'User test_coach exists');
bsp_test_assert($designer_user !== false, 'User test_designer exists');
if ($coach_user) {
    bsp_test_assert(in_array('bsp_coach', $coach_user->roles, true), 'test_coach has role bsp_coach');
}
if ($designer_user) {
    bsp_test_assert(in_array('bsp_designer', $designer_user->roles, true), 'test_designer has role bsp_designer');
}

$coach_id    = $coach_user ? (int) $coach_user->ID : 0;
$designer_id = $designer_user ? (int) $designer_user->ID : 0;

// --- 3. Custom DB Tables ---
WP_CLI::log('');
WP_CLI::log('3. Custom DB Tables');
global $wpdb;
$prefix = $wpdb->prefix;
$tables = ['bsp_teams', 'bsp_rosters', 'bsp_artwork_queue'];

foreach ($tables as $table) {
    $full_name = $prefix . $table;
    $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $full_name)) === $full_name;
    bsp_test_assert($exists, "Table {$full_name} exists");
}

// --- 4. REST Endpoints ---
WP_CLI::log('');
WP_CLI::log('4. REST Endpoints');

// Coach: GET /teams (bsp_view_portal)
if ($coach_id) {
    $result = bsp_rest_request('GET', '/battle-sports/v1/teams', $coach_id);
    bsp_test_assert($result['status'] === 200, 'Coach: GET /teams returns 200');
}

// Designer: GET /artwork (bsp_view_artwork_queue)
if ($designer_id) {
    $result = bsp_rest_request('GET', '/battle-sports/v1/artwork', $designer_id);
    bsp_test_assert($result['status'] === 200, 'Designer: GET /artwork returns 200');
}

// Coach denied for designer-only endpoint (artwork list requires bsp_view_artwork_queue)
if ($coach_id) {
    $result = bsp_rest_request('GET', '/battle-sports/v1/artwork', $coach_id);
    bsp_test_assert($result['status'] === 403, 'Coach: GET /artwork returns 403 (correctly denied)');
}

// Designer denied for coach-only team create (needs bsp_submit_order)
if ($designer_id) {
    $result = bsp_rest_request('POST', '/battle-sports/v1/teams', $designer_id, [
        'org_name'  => 'Test Org',
        'team_name' => 'Test Team',
    ]);
    bsp_test_assert($result['status'] === 403, 'Designer: POST /teams returns 403 (correctly denied)');
}

// Unauthenticated: GET /teams should fail
wp_set_current_user(0);
$request  = new WP_REST_Request('GET', '/battle-sports/v1/teams');
$response = rest_do_request($request);
bsp_test_assert($response->get_status() === 401, 'Unauthenticated: GET /teams returns 401');

// --- 5. WooCommerce Submission Fee Product ---
WP_CLI::log('');
WP_CLI::log('5. WooCommerce Submission Fee Product');
$product_id = (int) get_option('bsp_submission_fee_product_id', 0);
$product    = $product_id && class_exists('WooCommerce') ? wc_get_product($product_id) : null;

bsp_test_assert($product !== null && $product->exists(), 'Submission fee product exists');
if ($product) {
    $price = $product->get_regular_price();
    bsp_test_assert((float) $price === 50.0, 'Submission fee product price is $50');
}

// --- 6. Portal Page ---
WP_CLI::log('');
WP_CLI::log('6. Portal Page');
$portal_page = get_page_by_path('portal', OBJECT, 'page');
bsp_test_assert($portal_page !== null, 'Portal page exists (slug: portal)');
if ($portal_page) {
    $template = get_post_meta($portal_page->ID, '_wp_page_template', true);
    $expected = 'templates/template-portal.php';
    $alt      = 'template-portal.php';
    bsp_test_assert(
        $template === $expected || $template === $alt || empty($template),
        'Portal page has correct template (template-portal.php or templates/template-portal.php)'
    );
    bsp_test_assert(
        has_shortcode($portal_page->post_content, 'bsp_portal'),
        'Portal page contains [bsp_portal] shortcode'
    );
}

// --- 7. Make.com Webhook URL ---
WP_CLI::log('');
WP_CLI::log('7. Make.com Webhook');
$webhook_url = get_option('bsp_make_webhook_url', '');
$url_valid   = !empty($webhook_url) && filter_var($webhook_url, FILTER_VALIDATE_URL);
$make_match  = str_contains($webhook_url, 'make.com');
bsp_test_assert($url_valid, 'Make.com webhook URL is configured');
bsp_test_assert($make_match || empty($webhook_url), 'Webhook URL contains make.com (or is unset for local dev)');

// --- 8. WP Mail SMTP ---
WP_CLI::log('');
WP_CLI::log('8. WP Mail SMTP');
if (!function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$smtp_active = is_plugin_active('wp-mail-smtp/wp-mail-smtp.php')
    || is_plugin_active('wp-mail-smtp-pro/wp-mail-smtp.php')
    || is_plugin_active('easy-wp-smtp/easy-wp-smtp.php');
bsp_test_assert($smtp_active, 'WP Mail SMTP (or compatible) plugin is active');

// --- Summary ---
WP_CLI::log('');
WP_CLI::log('--- Summary ---');
WP_CLI::log("Passed: {$passed}");
WP_CLI::log("Failed: {$failed}");

if ($failed > 0) {
    WP_CLI::log('');
    WP_CLI::log('Failed assertions:');
    foreach ($errors as $err) {
        WP_CLI::log('  - ' . $err);
    }
    WP_CLI::halt(1);
}

WP_CLI::success('All integration tests passed.');
