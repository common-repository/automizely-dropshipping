<?php
// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

$store_url = get_site_url();

$query = [
    'shop'=>$store_url,
    'utm_source' => 'wordpress_plugin',
    'utm_medium' => 'landingpage'
];

$debug = isset($_GET['debug']) ? $_GET['debug'] : 'no';

$go_to_dashboard_url = "https://accounts.aftership.com/oauth-session?callbackUrl=".urlencode("https://accounts.aftership.com/oauth/woocommerce-automizely-dropshipping?signature=".base64_encode(json_encode($query)));
$go_to_visit_url = "https://www.automizely.com/dropshipping/?utm_source=wordpress_plugin&utm_medium=landingpage";

if ($debug === 'yes') {
    $go_to_dashboard_url = "https://accounts.aftership.io/oauth-session?callbackUrl=".urlencode("https://accounts.aftership.io/oauth/woocommerce-automizely-dropshipping?signature=".base64_encode(json_encode($query)));
    $go_to_visit_url = "https://www.automizely.io/dropshipping/?utm_source=wordpress_plugin&utm_medium=landingpage";
}


?>

<!-- Main wrapper -->
<div class="automizely_overlay"></div>
<div class="automizely_wrap">
    <div class="automizely_content">
        <img alt="Automizely Dropshipping" class="automizely_logo" src="<?php echo esc_url(AUTOMIZELY_DROPSHIPPING_URL . '/assets/images/logo.svg') ?>" />
        <br />
        <img alt="WELCOME" class="automizely_welcome" src="<?php echo esc_url(AUTOMIZELY_DROPSHIPPING_URL . '/assets/images/welcome.svg') ?>" />
        <div class="automizely_desc">Choose products and start selling online in a few clicks. <br /> 3-10 days trackable shipping to US.</div>
        <img src="<?php echo esc_url(AUTOMIZELY_DROPSHIPPING_URL . '/assets/images/install.svg') ?>" />
        <a class="automizely_btn" href="<?php echo esc_url($go_to_dashboard_url); ?>" target="_blank">let’s get started</a>
        <div class="automizely_visit">Visit us at <a href="<?php echo esc_url($go_to_visit_url); ?>" target="_blank">Automizely.com</a></div>
    </div>
</div>
