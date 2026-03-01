<?php
/**
 * Battle Sports Platform — Test Data Seed Script
 *
 * Run via: wp eval-file wp-content/seed-test-data.php
 *
 * Creates all MVP test data required to run the QA checklist end-to-end:
 *   - User accounts (test_coach, test_designer, test_subscriber, test_admin)
 *   - WordPress pages (Portal, Rosters, Artwork Queue, Catalog, product detail pages)
 *   - WooCommerce submission fee product
 *   - wp_bsp_teams records (2 teams for test_coach)
 *   - wp_bsp_rosters records (full roster for Team 1, empty for Team 2)
 *   - wp_bsp_artwork_queue records (one at each key workflow status)
 *   - WordPress options (portal config, make.com stub, submission fee product ID)
 *
 * SAFE TO RE-RUN: checks for existence before inserting. Will report
 * "already exists" for items already seeded.
 *
 * @package Battle_Sports_Platform
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    fwrite( STDERR, "Run via: wp eval-file wp-content/seed-test-data.php\n" );
    exit( 1 );
}

global $wpdb;

$seeded  = 0;
$skipped = 0;

function bsp_seed_log( string $status, string $message ): void {
    global $seeded, $skipped;
    if ( $status === 'created' ) {
        $seeded++;
        WP_CLI::log( "  ✓ [created]  {$message}" );
    } elseif ( $status === 'exists' ) {
        $skipped++;
        WP_CLI::log( "  · [exists]   {$message}" );
    } else {
        WP_CLI::warning( "  ✗ [error]    {$message}" );
    }
}

// ============================================================
// SECTION 1 — USER ACCOUNTS
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '1. User Accounts' );

$users = [
    [
        'login'     => 'test_coach',
        'email'     => 'coach@battle-sports-dev.local',
        'pass'      => 'Coach@Test2026!',
        'role'      => 'bsp_coach',
        'first'     => 'Alex',
        'last'      => 'Rivera',
        'display'   => 'Alex Rivera (Coach)',
    ],
    [
        'login'     => 'test_designer',
        'email'     => 'designer@battle-sports-dev.local',
        'pass'      => 'Design@Test2026!',
        'role'      => 'bsp_designer',
        'first'     => 'Jordan',
        'last'      => 'Kim',
        'display'   => 'Jordan Kim (Designer)',
    ],
    [
        'login'     => 'test_subscriber',
        'email'     => 'subscriber@battle-sports-dev.local',
        'pass'      => 'Sub@Test2026!',
        'role'      => 'subscriber',
        'first'     => 'Casey',
        'last'      => 'Morgan',
        'display'   => 'Casey Morgan',
    ],
    [
        'login'     => 'test_admin',
        'email'     => 'admin-test@battle-sports-dev.local',
        'pass'      => 'Admin@Test2026!',
        'role'      => 'administrator',
        'first'     => 'Battle',
        'last'      => 'Admin',
        'display'   => 'Battle Admin',
    ],
];

$user_ids = [];

foreach ( $users as $u ) {
    $existing = get_user_by( 'login', $u['login'] );
    if ( $existing ) {
        // Make sure the role is correct even if user was previously created without it
        $existing->set_role( $u['role'] );
        bsp_seed_log( 'exists', "User: {$u['login']} ({$u['role']})" );
        $user_ids[ $u['login'] ] = (int) $existing->ID;
    } else {
        $id = wp_insert_user( [
            'user_login'   => $u['login'],
            'user_email'   => $u['email'],
            'user_pass'    => $u['pass'],
            'role'         => $u['role'],
            'first_name'   => $u['first'],
            'last_name'    => $u['last'],
            'display_name' => $u['display'],
        ] );
        if ( is_wp_error( $id ) ) {
            bsp_seed_log( 'error', "User {$u['login']}: " . $id->get_error_message() );
        } else {
            $user_ids[ $u['login'] ] = $id;
            bsp_seed_log( 'created', "User: {$u['login']} ({$u['role']}) — pass: {$u['pass']}" );
        }
    }
}

$coach_id    = $user_ids['test_coach']    ?? 0;
$designer_id = $user_ids['test_designer'] ?? 0;

// ============================================================
// SECTION 2 — WORDPRESS PAGES
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '2. WordPress Pages' );

/**
 * Create a page if it doesn't already exist by slug.
 * Returns the page ID.
 */
function bsp_seed_page( string $title, string $slug, string $content = '', string $template = '', ?int $parent_id = null ): int {
    $existing = get_page_by_path( $slug, OBJECT, 'page' );
    if ( $existing ) {
        // Update template meta if provided and not already set
        if ( $template ) {
            $current_template = get_post_meta( $existing->ID, '_wp_page_template', true );
            if ( $current_template !== $template ) {
                update_post_meta( $existing->ID, '_wp_page_template', $template );
            }
        }
        bsp_seed_log( 'exists', "Page: /{$slug}/ (ID: {$existing->ID})" );
        return (int) $existing->ID;
    }

    $args = [
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => $content,
    ];
    if ( $parent_id ) {
        $args['post_parent'] = $parent_id;
    }
    $id = wp_insert_post( $args );
    if ( is_wp_error( $id ) || ! $id ) {
        bsp_seed_log( 'error', "Page /{$slug}/ failed to create" );
        return 0;
    }
    if ( $template ) {
        update_post_meta( $id, '_wp_page_template', $template );
    }
    bsp_seed_log( 'created', "Page: /{$slug}/ (ID: {$id})" );
    return $id;
}

// Home / Catalog
$catalog_id = bsp_seed_page(
    'Products',
    'products',
    '',
    'templates/template-catalog.php'
);

// Product Detail pages — one per uniform line
$products = [
    [ '7v7 Uniforms',          '7v7',          'product=7v7' ],
    [ 'Flag Uniforms',         'flag',         'product=flag' ],
    [ "Women's Flag Uniforms", 'womens-flag',  'product=womens-flag' ],
    [ 'Charlie Tackle Uniforms', 'charlie-tackle', 'product=charlie-tackle' ],
    [ 'Alpha Tackle Uniforms', 'alpha-tackle', 'product=alpha-tackle' ],
    [ 'Bravo Tackle Uniforms', 'bravo-tackle', 'product=bravo-tackle' ],
];

foreach ( $products as [ $title, $slug, $attr ] ) {
    bsp_seed_page(
        $title,
        $slug,
        "[bsp_intake_form {$attr}]",
        'templates/template-product-detail.php',
        $catalog_id
    );
}

// Portal (parent)
$portal_id = bsp_seed_page(
    'My Portal',
    'portal',
    '[bsp_portal]',
    'templates/template-portal.php'
);

// Portal sub-pages
$roster_id  = bsp_seed_page( 'Manage Rosters',  'rosters',       '[bsp_roster_manager]', 'templates/template-full-width.php', $portal_id );
$artwork_id = bsp_seed_page( 'Artwork Queue',    'artwork-queue', '[bsp_artwork_queue]',  'templates/template-full-width.php', $portal_id );

// Flush rewrite rules so the new slugs resolve immediately
flush_rewrite_rules();
WP_CLI::log( '  · Rewrite rules flushed' );

// Set as WP nav pages option so theme nav picks them up
update_option( 'bsp_portal_page_id',       $portal_id );
update_option( 'bsp_roster_page_id',       $roster_id );
update_option( 'bsp_artwork_queue_page_id', $artwork_id );
update_option( 'bsp_catalog_page_id',      $catalog_id );

// ============================================================
// SECTION 3 — WOOCOMMERCE SUBMISSION FEE PRODUCT
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '3. WooCommerce Submission Fee Product' );

if ( ! class_exists( 'WooCommerce' ) ) {
    WP_CLI::warning( '  ✗ WooCommerce not active — skipping product creation' );
} else {
    $product_id = (int) get_option( 'bsp_submission_fee_product_id', 0 );
    $product    = $product_id ? wc_get_product( $product_id ) : null;

    if ( $product && $product->exists() ) {
        bsp_seed_log( 'exists', "Submission fee product (ID: {$product_id}, SKU: BSP-SUBMISSION-FEE, Price: \$50)" );
    } else {
        $product = new WC_Product_Simple();
        $product->set_name( 'Uniform Design Submission Fee' );
        $product->set_sku( 'BSP-SUBMISSION-FEE' );
        $product->set_regular_price( '50.00' );
        $product->set_virtual( true );
        $product->set_downloadable( false );
        $product->set_status( 'publish' );
        $product->set_catalog_visibility( 'hidden' ); // Not shown in shop, only added programmatically
        $product->set_description( 'One-time design submission fee for custom uniform orders.' );
        $saved_id = $product->save();
        if ( $saved_id ) {
            update_option( 'bsp_submission_fee_product_id', $saved_id );
            bsp_seed_log( 'created', "Submission fee product (ID: {$saved_id}, SKU: BSP-SUBMISSION-FEE, Price: \$50)" );
        } else {
            bsp_seed_log( 'error', 'Failed to create WooCommerce submission fee product' );
        }
    }
}

// ============================================================
// SECTION 4 — TEAMS
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '4. Teams (for test_coach)' );

$prefix = $wpdb->prefix;

/**
 * Insert a team if one with the same user_id + team_name doesn't already exist.
 * Returns team ID.
 */
function bsp_seed_team( int $user_id, array $data ): int {
    global $wpdb;
    $prefix   = $wpdb->prefix;
    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$prefix}bsp_teams WHERE user_id = %d AND team_name = %s LIMIT 1",
        $user_id, $data['team_name']
    ) );
    if ( $existing ) {
        bsp_seed_log( 'exists', "Team: {$data['team_name']} (ID: {$existing})" );
        return (int) $existing;
    }
    $wpdb->insert(
        "{$prefix}bsp_teams",
        array_merge( [ 'user_id' => $user_id ], $data ),
        [ '%d', '%s', '%s', '%s', '%s', '%s', '%d' ]
    );
    $id = (int) $wpdb->insert_id;
    bsp_seed_log( 'created', "Team: {$data['team_name']} (ID: {$id})" );
    return $id;
}

$team1_id = bsp_seed_team( $coach_id, [
    'org_name'            => 'Midwest Thunder Athletic Club',
    'team_name'           => 'Thunder 14U',
    'age_group'           => '14U',
    'primary_color'       => 'Black',
    'secondary_color'     => 'Purple',
    'logo_attachment_id'  => 0,
    'sport'               => '7v7',
] );

$team2_id = bsp_seed_team( $coach_id, [
    'org_name'            => 'Midwest Thunder Athletic Club',
    'team_name'           => 'Thunder 16U Tackle',
    'age_group'           => '16U',
    'primary_color'       => 'Lead',
    'secondary_color'     => 'White',
    'logo_attachment_id'  => 0,
    'sport'               => 'alpha-tackle',
] );

// ============================================================
// SECTION 5 — ROSTERS
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '5. Roster Players (Team 1 — Thunder 14U)' );

$players = [
    [ 'Marcus Johnson',  '1',  'YL',  'YL' ],
    [ 'Deon Williams',   '5',  'AM',  'AM' ],
    [ 'Tyler Brooks',    '7',  'AL',  'AL' ],
    [ 'Jaylen Carter',   '10', 'YXL', 'YXL' ],
    [ 'Malik Thompson',  '12', 'AS',  'AS' ],
    [ 'Caleb Foster',    '14', 'AM',  'AM' ],
    [ 'Elijah Harris',   '17', 'AL',  'AL' ],
    [ 'Devon Reed',      '20', 'AM',  'AM' ],
    [ 'Aiden Torres',    '21', 'YL',  'YL' ],
    [ 'Noah Simmons',    '23', 'AL',  'AL' ],
    [ 'Josiah White',    '25', 'AM',  'AM' ],
    [ 'Cam Davis',       '30', 'AXL', 'AXL' ],
];

foreach ( $players as [ $name, $number, $jersey, $short ] ) {
    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$prefix}bsp_rosters WHERE team_id = %d AND player_name = %s LIMIT 1",
        $team1_id, $name
    ) );
    if ( $existing ) {
        bsp_seed_log( 'exists', "Player: #{$number} {$name}" );
    } else {
        $wpdb->insert(
            "{$prefix}bsp_rosters",
            [
                'team_id'       => $team1_id,
                'player_name'   => $name,
                'player_number' => $number,
                'jersey_size'   => $jersey,
                'short_size'    => $short,
            ],
            [ '%d', '%s', '%s', '%s', '%s' ]
        );
        bsp_seed_log( 'created', "Player: #{$number} {$name} ({$jersey}/{$short})" );
    }
}

WP_CLI::log( '' );
WP_CLI::log( '  Note: Team 2 (Thunder 16U Tackle) intentionally left empty — tests "no roster" quantity flow.' );

// ============================================================
// SECTION 6 — ARTWORK QUEUE RECORDS
// Seeded at each key status so every QA workflow step has pre-existing data.
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '6. Artwork Queue Records (one per key status)' );

/**
 * Seed one artwork queue record at a given status.
 */
function bsp_seed_artwork( array $data ): void {
    global $wpdb;
    $prefix   = $wpdb->prefix;
    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$prefix}bsp_artwork_queue WHERE order_ref = %s LIMIT 1",
        $data['order_ref']
    ) );
    if ( $existing ) {
        bsp_seed_log( 'exists', "Artwork: {$data['order_ref']} (status: {$data['status']})" );
        return;
    }
    $wpdb->insert(
        "{$prefix}bsp_artwork_queue",
        $data,
        [ '%s', '%d', '%d', '%s', '%d', '%s', '%s' ]
    );
    $id = (int) $wpdb->insert_id;
    bsp_seed_log( 'created', "Artwork: {$data['order_ref']} — {$data['status']} (ID: {$id})" );
}

global $coach_id, $designer_id, $team1_id, $team2_id;

// 1. submitted — fresh intake, no action taken yet
bsp_seed_artwork( [
    'order_ref'           => 'BS-2026-SEED01',
    'team_id'             => $team1_id,
    'user_id'             => $coach_id,
    'status'              => 'submitted',
    'assigned_designer_id'=> 0,
    'monday_item_id'      => '',
    'product_type'        => '7v7',
] );

// 2. in_queue — payment cleared, queued for design team
bsp_seed_artwork( [
    'order_ref'           => 'BS-2026-SEED02',
    'team_id'             => $team1_id,
    'user_id'             => $coach_id,
    'status'              => 'in_queue',
    'assigned_designer_id'=> 0,
    'monday_item_id'      => 'monday-seed-002',
    'product_type'        => 'womens-flag',
] );

// 3. in_progress — designer has claimed this item
bsp_seed_artwork( [
    'order_ref'           => 'BS-2026-SEED03',
    'team_id'             => $team2_id,
    'user_id'             => $coach_id,
    'status'              => 'in_progress',
    'assigned_designer_id'=> $designer_id,
    'monday_item_id'      => 'monday-seed-003',
    'product_type'        => 'alpha-tackle',
] );

// 4. proof_sent — designer uploaded proof; customer needs to act (primary approval test scenario)
bsp_seed_artwork( [
    'order_ref'           => 'BS-2026-SEED04',
    'team_id'             => $team1_id,
    'user_id'             => $coach_id,
    'status'              => 'proof_sent',
    'assigned_designer_id'=> $designer_id,
    'monday_item_id'      => 'monday-seed-004',
    'product_type'        => 'charlie-tackle',
] );

// 5. revision_requested — customer asked for changes, back to designer
bsp_seed_artwork( [
    'order_ref'           => 'BS-2026-SEED05',
    'team_id'             => $team2_id,
    'user_id'             => $coach_id,
    'status'              => 'revision_requested',
    'assigned_designer_id'=> $designer_id,
    'monday_item_id'      => 'monday-seed-005',
    'product_type'        => 'bravo-tackle',
] );

// 6. approved — customer approved, awaiting fulfillment
bsp_seed_artwork( [
    'order_ref'           => 'BS-2026-SEED06',
    'team_id'             => $team1_id,
    'user_id'             => $coach_id,
    'status'              => 'approved',
    'assigned_designer_id'=> $designer_id,
    'monday_item_id'      => 'monday-seed-006',
    'product_type'        => 'flag',
] );

// 7. complete — fully fulfilled, historical reference
bsp_seed_artwork( [
    'order_ref'           => 'BS-2026-SEED00',
    'team_id'             => $team1_id,
    'user_id'             => $coach_id,
    'status'              => 'complete',
    'assigned_designer_id'=> $designer_id,
    'monday_item_id'      => 'monday-seed-000',
    'product_type'        => '7v7',
] );

// ============================================================
// SECTION 7 — PROOF ATTACHMENT (for BS-2026-SEED04 / proof_sent)
// Injects a placeholder proof image into WP media library so the
// customer approval flow has something to "view".
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '7. Proof Attachment (for proof_sent artwork BS-2026-SEED04)' );

$seed04_id = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT id FROM {$prefix}bsp_artwork_queue WHERE order_ref = %s",
    'BS-2026-SEED04'
) );

if ( $seed04_id ) {
    $meta_key = '_bsp_proof_attachment_id';
    $existing_proof = $wpdb->get_var( $wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s LIMIT 1",
        "_bsp_artwork_{$seed04_id}_proof",
        '%'
    ) );

    // We'll use wp_options as a simple meta store for the artwork proof
    $option_key = "_bsp_artwork_{$seed04_id}_proof_id";
    if ( get_option( $option_key ) ) {
        bsp_seed_log( 'exists', "Proof attachment already registered for artwork {$seed04_id}" );
    } else {
        // Create a minimal placeholder attachment (1x1 white PNG, base64 encoded)
        $png_data = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        );
        $upload_dir = wp_upload_dir();
        $file_path  = $upload_dir['path'] . '/seed-proof-BS-2026-SEED04.png';

        if ( ! file_exists( $file_path ) ) {
            file_put_contents( $file_path, $png_data );
        }

        $attachment = [
            'post_mime_type' => 'image/png',
            'post_title'     => 'Design Proof — BS-2026-SEED04',
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];
        $att_id = wp_insert_attachment( $attachment, $file_path );
        if ( $att_id && ! is_wp_error( $att_id ) ) {
            update_option( $option_key, $att_id );
            bsp_seed_log( 'created', "Proof placeholder attachment (ID: {$att_id}) linked to artwork {$seed04_id}" );
            WP_CLI::log( "  · Proof URL: " . wp_get_attachment_url( $att_id ) );
        } else {
            bsp_seed_log( 'error', 'Failed to create proof attachment' );
        }
    }
}

// ============================================================
// SECTION 8 — WORDPRESS OPTIONS / SETTINGS
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '8. WordPress Options & Settings' );

/**
 * Set an option only if it isn't already set.
 */
function bsp_seed_option( string $key, mixed $value, string $label ): void {
    if ( get_option( $key ) !== false ) {
        bsp_seed_log( 'exists', "Option: {$key}" );
    } else {
        update_option( $key, $value );
        bsp_seed_log( 'created', "Option: {$key} — {$label}" );
    }
}

// Stub Make.com webhook — use a local echo endpoint so trigger_make_webhook()
// doesn't fail during dev/testing. Replace with real Make.com URL in staging.
bsp_seed_option(
    'bsp_make_webhook_url',
    'https://hook.make.com/REPLACE-WITH-REAL-URL',
    'Make.com webhook URL (stub — replace before staging)'
);

bsp_seed_option(
    'bsp_make_webhook_secret',
    'bsp-dev-webhook-secret-2026',
    'Make.com webhook HMAC secret (dev only)'
);

bsp_seed_option(
    'bsp_monday_board_id',
    '',
    'Monday.com board ID (empty until real credentials added)'
);

bsp_seed_option(
    'bsp_monday_api_key',
    '',
    'Monday.com API key (empty until real credentials added)'
);

bsp_seed_option(
    'bsp_db_version',
    '1.0',
    'DB schema version'
);

// Confirm portal/roster/artwork page IDs are registered
// (may have been set in Section 2 but also ensure they survive re-runs)
if ( $portal_id ) {
    update_option( 'bsp_portal_page_id', $portal_id );
    bsp_seed_log( 'exists', "Option: bsp_portal_page_id = {$portal_id}" );
}

// ============================================================
// SECTION 9 — NAVIGATION MENU
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '9. Primary Navigation Menu' );

$menu_name     = 'Primary Navigation';
$menu_location = 'primary'; // Must match register_nav_menus() in functions.php

$menu_id = wp_get_nav_menu_object( $menu_name );
if ( $menu_id ) {
    bsp_seed_log( 'exists', "Nav menu: {$menu_name}" );
    $menu_id = $menu_id->term_id;
} else {
    $menu_id = wp_create_nav_menu( $menu_name );
    bsp_seed_log( 'created', "Nav menu: {$menu_name}" );
}

// Check if menu already has items
$menu_items = wp_get_nav_menu_items( $menu_id );
if ( ! empty( $menu_items ) ) {
    WP_CLI::log( '  · Menu items already populated — skipping' );
} else {
    $nav_pages = [
        [ 'title' => 'Home',      'url' => home_url( '/' ) ],
        [ 'title' => 'Products',  'url' => get_permalink( $catalog_id ) ],
        [ 'title' => 'My Portal', 'url' => get_permalink( $portal_id ) ],
    ];

    foreach ( $nav_pages as $item ) {
        wp_update_nav_menu_item( $menu_id, 0, [
            'menu-item-title'  => $item['title'],
            'menu-item-url'    => $item['url'],
            'menu-item-status' => 'publish',
            'menu-item-type'   => 'custom',
        ] );
        bsp_seed_log( 'created', "Nav item: {$item['title']}" );
    }

    // Assign menu to primary location
    $locations = get_theme_mod( 'nav_menu_locations', [] );
    $locations[ $menu_location ] = $menu_id;
    set_theme_mod( 'nav_menu_locations', $locations );
    WP_CLI::log( "  · Assigned to theme location: {$menu_location}" );
}

// ============================================================
// SECTION 10 — WORDPRESS READING SETTINGS
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '10. Reading Settings' );

// Set Products/Catalog as static front page
$front_page_id = get_option( 'page_on_front' );
$show_on_front = get_option( 'show_on_front' );

if ( $show_on_front === 'page' && $front_page_id ) {
    WP_CLI::log( "  · Static front page already set (ID: {$front_page_id}) — not overriding" );
} else {
    // Create a dedicated Home page if it doesn't exist
    $home_id = bsp_seed_page(
        'Home',
        'home',
        '',
        'templates/template-full-width.php'
    );
    update_option( 'show_on_front', 'page' );
    update_option( 'page_on_front', $home_id );
    WP_CLI::log( "  · Front page set to Home (ID: {$home_id})" );
}

// ============================================================
// SUMMARY
// ============================================================
WP_CLI::log( '' );
WP_CLI::log( '==========================================' );
WP_CLI::log( "  Seed complete: {$seeded} created, {$skipped} already existed" );
WP_CLI::log( '==========================================' );
WP_CLI::log( '' );
WP_CLI::log( 'Test Credentials:' );
WP_CLI::log( '  test_coach      / Coach@Test2026!     (role: bsp_coach)' );
WP_CLI::log( '  test_designer   / Design@Test2026!    (role: bsp_designer)' );
WP_CLI::log( '  test_subscriber / Sub@Test2026!       (role: subscriber)' );
WP_CLI::log( '  test_admin      / Admin@Test2026!     (role: administrator)' );
WP_CLI::log( '' );
WP_CLI::log( 'Key URLs:' );
WP_CLI::log( '  Portal:        ' . home_url( '/portal/' ) );
WP_CLI::log( '  Rosters:       ' . home_url( '/portal/rosters/' ) );
WP_CLI::log( '  Artwork Queue: ' . home_url( '/portal/artwork-queue/' ) );
WP_CLI::log( '  Products:      ' . home_url( '/products/' ) );
WP_CLI::log( '  7v7 Form:      ' . home_url( '/products/7v7/' ) );
WP_CLI::log( '' );
WP_CLI::log( 'Artwork Queue Seeds (order_ref → status):' );
WP_CLI::log( '  BS-2026-SEED00 → complete         (historical reference)' );
WP_CLI::log( '  BS-2026-SEED01 → submitted        (fresh intake, no action yet)' );
WP_CLI::log( '  BS-2026-SEED02 → in_queue         (payment cleared, awaiting designer)' );
WP_CLI::log( '  BS-2026-SEED03 → in_progress      (assigned to test_designer)' );
WP_CLI::log( '  BS-2026-SEED04 → proof_sent       (has proof attachment, ready for approval)' );
WP_CLI::log( '  BS-2026-SEED05 → revision_requested (customer requested changes)' );
WP_CLI::log( '  BS-2026-SEED06 → approved         (customer approved, pending fulfillment)' );
WP_CLI::log( '' );
WP_CLI::log( 'Next Step: wp eval-file wp-content/test-integration.php' );
WP_CLI::log( '' );
