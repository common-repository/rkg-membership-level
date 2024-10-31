<?php
/* 

Plugin Name: rkg_membership_level 
Description: Provide membership feature and  control post based on membership-level.
Version: 1.0.0
Author: Ravi Kumar Gupta
License: GPLv2 or later 
Text Domain: membership

*/


if (!defined('ABSPATH')) {
    header("Location:/");
    die("");
}

function add_membership_column()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'users';
    $column_name = 'membership_level';

    $has_run = get_option('add_membership_column_has_run');
    if (!$has_run) {
        $check_column = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE '$column_name'");
        if (!$check_column) {
            $sql = "ALTER TABLE $table_name ADD COLUMN $column_name varchar(255) DEFAULT NULL AFTER user_email";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        update_option('add_membership_column_has_run', true);
    }
}
add_membership_column();

function membership_deactivation()
{
    // global $wpdb, $table_prefix;
    // $wp_mem = $table_prefix . 'mem';
    // $q = "DROP TABLE `$wp_mem`";
    // $wpdb->query($q);
}
register_deactivation_hook(__FILE__, 'membership_deactivation');


function member_page()
{
    include 'member.php';
}


function membership_menu()
{
    add_menu_page('membership', 'Membership', 'manage_options', 'mp-page', 'member_page', '', 30);
}
add_action('admin_menu', 'membership_menu');


// Add Style Sheets
add_action('admin_enqueue_scripts', 'register_style');

// callable function in whih css file will be registered
function register_style()
{
    wp_enqueue_style('main_stylesheet', plugins_url('./bootstrap/css/bootstrap.min.css', __FILE__));
}


add_action('init', 'register_form');
function register_form()
{
    if (isset($_POST['submit'])) {
        if (empty($_POST['username']) || !ctype_alnum($_POST['username'])) {
            echo '<div id="message" class="error">Please enter a valid username using only letters and numbers.</div>';
            return;
        }
    
        if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            echo '<div id="message" class="error">Please enter a valid email address.</div>';
            return;
        }
    
        if (empty($_POST['password']) || strlen($_POST['password']) < 8) {
            echo '<div id="message" class="error">Please enter a password that is at least 8 characters long.</div>';
            return;
        }
    
        if (empty($_POST['membership_level']) || !in_array($_POST['membership_level'], array('basic', 'premium', 'vip'))) {
            echo '<div id="message" class="error">Please select a valid membership level.</div>';
            return;
        }
    
        $username = sanitize_text_field($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = wp_kses_post($_POST['password']);
        $membership_level = sanitize_text_field($_POST['membership_level']);
        $userdata = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'role' => 'subscriber'
        );
    
        $user_id = wp_insert_user($userdata);
    
        if (is_wp_error($user_id)) {
            $error_message = $user_id->get_error_message();
            echo '<div id="message" class="error">' .esc_html_e( $error_message ). '</div>';
        } else {
            global $wpdb, $table_prefix;
            $table_name = $table_prefix . 'users';
            $q = "UPDATE $table_name SET user_membership_level='$membership_level' WHERE user_login='$username'";
            $wpdb->query($q);
            echo '<div id="message" class="updated">Registration successful. Please log in.</div>';
            wp_redirect(home_url('/login'));
            exit;
        }
    }
}
function register_page()
{
    ob_start(); ?>
    <form action="" method="post">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" required>
        <br>
        <label for="email">Email</label>
        <input type="email" name="email" id="email" required>
        <br>
        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>
        <br>
        <label for="membership_level">Membership Level</label>
        <select name="membership_level" id="membership_level">
            <option value="">Select Membership Level</option>
            <option value="basic">Basic</option>
            <option value="premium">Premium</option>
            <option value="gold">Gold</option>
        </select>
        <br>
        <input type="submit" name="submit" value="Register">
    </form>
<?php
    return ob_get_clean();
}
add_shortcode('register_page', 'register_page');


add_action('init', 'login_form');

function login_form()
{
    if (isset($_POST['Login'])) {
        $username = sanitize_text_field($_POST['username']);
        $password = wp_kses_post($_POST['password']);

        if (empty($username) || empty($password)) {
            echo '<div id="message" class="error">Please enter both username and password.</div>';
            return;
        }
        $creds = array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => true
        );

        $user = wp_signon($creds, false);

        if (is_wp_error($user)) {
            $error_message = $user->get_error_message();
            echo '<div id="message" class="error">' .esc_html_e( $error_message ). '</div>';
        } else {
            wp_set_current_user($user->ID, $user->user_login);
            wp_set_auth_cookie($user->ID);
            do_action('wp_login', $user->user_login);
            echo '<div id="message" class="updated">Login successful. Redirecting...</div>';
            echo '<script>window.location.href="' . home_url() . '"</script>';
        }
    }
}

function login_page()
{
    ob_start(); ?>
    <form action="" method="post">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" required>
        <br>
        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>
        <br>
        <input type="submit" name="Login" value="Login">
    </form>
<?php
    return ob_get_clean();
}
add_shortcode('login_page', 'login_page');




function post_pages()
{
    $page_title_1 = 'Register';
    $page_title_2 = 'Login';

    if (get_page_by_title($page_title_1) == NULL) {

        $register = array(
            'post_title' => $page_title_1,
            'post_content' => '[register_page]',
            'post_status' => 'publish',
            'post_type'   => 'page'
        );
        $insert_page = wp_insert_post($register);
    }
    if (get_page_by_title($page_title_2) == NULL) {

        $login = array(
            'post_title' => $page_title_2,
            'post_content' => '[login_page]',
            'post_status' => 'publish',
            'post_type'   => 'page'
        );
        $insert_page = wp_insert_post($login);
    }
    
}
register_activation_hook(__FILE__, 'post_pages');


// Filter the main query to limit the number of posts based on user's membership level
function post_limit($query)
{
    $user = wp_get_current_user();
    $membership_level = $user->user_membership_level;

    // Limit posts based on user's membership level
    if ($membership_level) {
        switch ($membership_level) {
            case 'basic':
                $post_limit = 2;
                break;
            case 'premium':
                $post_limit = 5;
                break;
            case 'gold':
                $post_limit = -1; // Unlimited posts
                break;
            default:
                $post_limit = 2;
        }
        if ($post_limit >= 0) {
            $query->set('posts_per_page', $post_limit);
            // Order posts by ascending post title
            $query->set('orderby', 'title');
            $query->set('order', 'ASC');
            global $post_limit;
            $post_count = wp_count_posts()->publish;
            global $post_count;
        }
    }
}
add_action('pre_get_posts', 'post_limit');

add_action( 'admin_menu', 'disable_button' );

function disable_button() {
    global $menu, $submenu;
    $post_count = wp_count_posts()->publish;
    global $post_limit; // set your post limit here
    $user = wp_get_current_user();
    
    if ( $post_count >= $post_limit && ! user_can( $user, 'publish_posts' ) ) {
        // Find the position of the "Add New" button in the $menu array
        $position = 10;
        foreach ( $menu as $index => $item ) {
            if ( $item[2] == 'post-new.php' ) {
                $position = $index;
                break;
            }
        }

        // Remove the "Add New" button from the menu
        unset( $menu[ $position ] );

        // Remove the "Add New" submenu item from the "Posts" menu
        if ( isset( $submenu['edit.php'] ) ) {
            foreach ( $submenu['edit.php'] as $index => $item ) {
                if ( $item[2] == 'post-new.php' ) {
                    unset( $submenu['edit.php'][ $index ] );
                    break;
                }
            }
        }

        // Add a notice to inform the user that they can't add new posts
        add_action( 'admin_notices', 'disable_notice' );
    }
}

function disable_notice() {
    echo '<div class="notice notice-warning is-dismissible"><p>New posts are disabled until existing posts are published by the admin.</p></div>';
}


function disable() {
    $post_count = wp_count_posts()->publish;
    global $post_limit;// set your post limit here
    global $post_count;// set your post limit here

    if ($post_count >= $post_limit) {
        global $pagenow;
        if ($pagenow == 'edit.php' && !current_user_can('publish_posts')) {
            echo '<style>
                .page-title-action { display: none !important; }
            </style>';
        }
    }
}
add_action('admin_head', 'disable');
