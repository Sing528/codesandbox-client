<?php
/**
 * Tak Shing Cleaning Theme Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

const TAKSHING_VERSION = '1.0.0';

add_action('after_setup_theme', function () {
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_theme_support('responsive-embeds');
    add_theme_support('title-tag');
    add_theme_support('custom-logo', [
        'height'      => 64,
        'width'       => 64,
        'flex-width'  => true,
        'flex-height' => true,
    ]);

    register_nav_menus([
        'primary' => __('Primary Menu', 'takshing'),
        'footer'  => __('Footer Menu', 'takshing'),
    ]);
});

add_action('wp_enqueue_scripts', function () {
    // Main script compiled from TypeScript
    $script_path = get_stylesheet_directory() . '/assets/js/main.js';
    if (file_exists($script_path)) {
        wp_enqueue_script('takshing-main', get_stylesheet_directory_uri() . '/assets/js/main.js', [], TAKSHING_VERSION, [ 'in_footer' => true ]);
    }

    // Extra CSS if present
    $extra_css_path = get_stylesheet_directory() . '/assets/css/extra.css';
    if (file_exists($extra_css_path)) {
        wp_enqueue_style('takshing-extra', get_stylesheet_directory_uri() . '/assets/css/extra.css', [], TAKSHING_VERSION);
    }

    wp_localize_script('takshing-main', 'takshing', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'postUrl' => admin_url('admin-post.php'),
        'nonce'   => wp_create_nonce('takshing_nonce'),
    ]);
});

/**
 * Quote/Booking form shortcode
 */
function takshing_quote_form_shortcode(): string {
    $action = esc_url(admin_url('admin-post.php'));
    $nonce_field = wp_nonce_field('takshing_quote', 'takshing_nonce', true, false);

    ob_start();
    ?>
    <form id="takshing-quote-form" action="<?php echo $action; ?>" method="post" class="wp-block-group" style="gap: var(--wp--preset--spacing--30);">
        <input type="hidden" name="action" value="takshing_submit_quote" />
        <?php echo $nonce_field; ?>

        <div class="wp-block-columns">
            <div class="wp-block-column">
                <label><strong><?php esc_html_e('Full Name', 'takshing'); ?></strong><br>
                    <input required type="text" name="name" placeholder="e.g. Chan Tai Man" />
                </label>
            </div>
            <div class="wp-block-column">
                <label><strong><?php esc_html_e('Phone', 'takshing'); ?></strong><br>
                    <input required type="tel" name="phone" placeholder="6626 4702" />
                </label>
            </div>
        </div>

        <div class="wp-block-columns">
            <div class="wp-block-column">
                <label><strong><?php esc_html_e('Email', 'takshing'); ?></strong><br>
                    <input type="email" name="email" placeholder="you@example.com" />
                </label>
            </div>
            <div class="wp-block-column">
                <label><strong><?php esc_html_e('District / Location', 'takshing'); ?></strong><br>
                    <input type="text" name="district" placeholder="e.g. Tsim Sha Tsui" />
                </label>
            </div>
        </div>

        <label><strong><?php esc_html_e('Property Type', 'takshing'); ?></strong><br>
            <select required name="property_type">
                <option value="">--</option>
                <option>Home</option>
                <option>Office</option>
                <option>School</option>
                <option>Restaurant</option>
                <option>Retail / Chain Store</option>
                <option>Car Dealership</option>
                <option>Government / Institution</option>
                <option>Industrial</option>
            </select>
        </label>

        <label><strong><?php esc_html_e('Services Needed', 'takshing'); ?></strong><br>
            <select required multiple name="services[]" size="6">
                <option>Home Cleaning</option>
                <option>Office Cleaning</option>
                <option>Deep Cleaning</option>
                <option>Post-Renovation Cleaning</option>
                <option>Marble Maintenance & Restoration</option>
                <option>Nano Disinfection Spray</option>
                <option>Post-Construction Professional Cleaning</option>
                <option>High-Altitude Signage Cleaning</option>
                <option>Pest Control - Inspection & Assessment</option>
                <option>Pest Control - Eco-Friendly Treatments</option>
                <option>Pest Control - Targeted Extermination</option>
                <option>Carpet Cleaning</option>
            </select>
        </label>

        <div class="wp-block-columns">
            <div class="wp-block-column">
                <label><strong><?php esc_html_e('Preferred Date', 'takshing'); ?></strong><br>
                    <input type="date" name="preferred_date" />
                </label>
            </div>
            <div class="wp-block-column">
                <label><strong><?php esc_html_e('Preferred Time', 'takshing'); ?></strong><br>
                    <input type="time" name="preferred_time" />
                </label>
            </div>
        </div>

        <label><strong><?php esc_html_e('Additional Details', 'takshing'); ?></strong><br>
            <textarea name="message" rows="5" placeholder="Tell us the size, frequency, any special requirements..."></textarea>
        </label>

        <button type="submit" class="wp-block-button__link wp-element-button"><?php esc_html_e('Request a Quote', 'takshing'); ?></button>
    </form>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('takshing_quote_form', 'takshing_quote_form_shortcode');

/**
 * Handle form submission
 */
function takshing_handle_quote_submission() {
    if (!isset($_POST['takshing_nonce']) || !wp_verify_nonce($_POST['takshing_nonce'], 'takshing_quote')) {
        wp_die(__('Security check failed', 'takshing'));
    }

    $name           = sanitize_text_field($_POST['name'] ?? '');
    $phone          = sanitize_text_field($_POST['phone'] ?? '');
    $email          = sanitize_email($_POST['email'] ?? '');
    $district       = sanitize_text_field($_POST['district'] ?? '');
    $property_type  = sanitize_text_field($_POST['property_type'] ?? '');
    $services       = isset($_POST['services']) ? array_map('sanitize_text_field', (array) $_POST['services']) : [];
    $preferred_date = sanitize_text_field($_POST['preferred_date'] ?? '');
    $preferred_time = sanitize_text_field($_POST['preferred_time'] ?? '');
    $message        = sanitize_textarea_field($_POST['message'] ?? '');

    $to = get_option('admin_email');
    // Override with company email if provided
    $company_email = 'takshingemc5678@gmail.com';
    if (is_email($company_email)) {
        $to = $company_email;
    }

    $subject = 'New Quote Request - Tak Shing';
    $body_lines = [
        'Name: ' . $name,
        'Phone: ' . $phone,
        'Email: ' . $email,
        'District: ' . $district,
        'Property Type: ' . $property_type,
        'Services: ' . implode(', ', $services),
        'Preferred: ' . trim($preferred_date . ' ' . $preferred_time),
        'Message: ' . $message,
        'IP: ' . $_SERVER['REMOTE_ADDR'],
    ];
    $body = implode("\n", $body_lines);

    $headers = [];
    if (!empty($email) && is_email($email)) {
        $headers[] = 'Reply-To: ' . $email;
    }

    wp_mail($to, $subject, $body, $headers);

    $redirect = add_query_arg('quote_submitted', '1', wp_get_referer() ?: home_url('/'));
    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_nopriv_takshing_submit_quote', 'takshing_handle_quote_submission');
add_action('admin_post_takshing_submit_quote', 'takshing_handle_quote_submission');

/**
 * Basic LocalBusiness / CleaningService schema
 */
add_action('wp_head', function () {
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'CleaningService',
        'name' => 'Tak Shing Environmental Management Company',
        'alternateName' => '達成環境管理服務公司',
        'url' => home_url('/'),
        'telephone' => '+85266264702',
        'email' => 'takshingemc5678@gmail.com',
        'areaServed' => 'Hong Kong',
        'description' => 'Trusted professional cleaning in Hong Kong for over 20 years. Home, office, deep cleaning, post-renovation, marble, disinfection, pest control.',
        'foundingDate' => '2005',
        'founder' => [
            '@type' => 'Person',
            'name' => 'Andy Lam',
            'alternateName' => '林文浩',
        ],
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => 'Hong Kong',
            'postalCode' => 'PO Box 84111',
            'postOfficeBoxNumber' => '84111',
            'addressRegion' => 'Kowloon',
        ],
        'logo' => get_site_icon_url() ?: (get_theme_file_uri('assets/logo.png')),
        'sameAs' => [],
        'serviceType' => [
            'Home Cleaning','Office Cleaning','Deep Cleaning','Post-Renovation Cleaning','Marble Maintenance & Restoration','Nano Disinfection','Post-Construction Cleaning','High-Altitude Signage Cleaning','Pest Control','Carpet Cleaning'
        ],
    ];

    echo '<script type="application/ld+json">' . wp_json_encode($data) . '</script>' . "\n";
}, 20);
