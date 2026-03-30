<?php
/**
 * Plugin Name: LearnPress Stats Dashboard
 * Plugin URI: https://example.com/
 * Description: Hiển thị thống kê LearnPress ở Dashboard và ngoài Frontend bằng shortcode.
 * Version: 1.0.0
 * Author: NguyenKhanh
 * Author URI: https://example.com/
 * License: GPL2
 * Text Domain: lp-stats-addon
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Kiểm tra LearnPress có đang được kích hoạt hay không
 */
function lp_stats_is_learnpress_active() {
    return post_type_exists( 'lp_course' );
}

/**
 * Lấy tổng số khóa học hiện có
 */
function lp_stats_get_total_courses() {
    $args = array(
        'post_type'      => 'lp_course',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    );

    $courses = get_posts( $args );
    return is_array( $courses ) ? count( $courses ) : 0;
}

/**
 * Lấy tổng số học viên đã đăng ký
 * Đếm số user khác nhau đã có bản ghi course trong bảng learnpress_user_items
 */
function lp_stats_get_total_students() {
    global $wpdb;

    $table = $wpdb->prefix . 'learnpress_user_items';

    // Kiểm tra bảng có tồn tại không
    $table_exists = $wpdb->get_var(
        $wpdb->prepare( "SHOW TABLES LIKE %s", $table )
    );

    if ( $table_exists !== $table ) {
        return 0;
    }

    $sql = "
        SELECT COUNT(DISTINCT user_id)
        FROM {$table}
        WHERE item_type = 'lp_course'
          AND user_id > 0
    ";

    $count = $wpdb->get_var( $sql );

    return intval( $count );
}

/**
 * Lấy số lượng khóa học đã hoàn thành
 * Đếm các bản ghi course có status = completed
 */
function lp_stats_get_total_completed_courses() {
    global $wpdb;

    $table = $wpdb->prefix . 'learnpress_user_items';

    // Kiểm tra bảng có tồn tại không
    $table_exists = $wpdb->get_var(
        $wpdb->prepare( "SHOW TABLES LIKE %s", $table )
    );

    if ( $table_exists !== $table ) {
        return 0;
    }

    $sql = "
        SELECT COUNT(*)
        FROM {$table}
        WHERE item_type = 'lp_course'
          AND status = 'completed'
    ";

    $count = $wpdb->get_var( $sql );

    return intval( $count );
}

/**
 * Lấy toàn bộ dữ liệu thống kê
 */
function lp_stats_get_all_data() {
    return array(
        'total_courses'   => lp_stats_get_total_courses(),
        'total_students'  => lp_stats_get_total_students(),
        'completed_count' => lp_stats_get_total_completed_courses(),
    );
}

/**
 * Nội dung widget trong Dashboard
 */
function lp_stats_dashboard_widget_content() {
    if ( ! lp_stats_is_learnpress_active() ) {
        echo '<p><strong>LearnPress chưa được kích hoạt.</strong></p>';
        return;
    }

    $stats = lp_stats_get_all_data();

    echo '<div style="padding:10px 0;">';
    echo '<p><strong>Tổng số khóa học:</strong> ' . esc_html( $stats['total_courses'] ) . '</p>';
    echo '<p><strong>Tổng số học viên đã đăng ký:</strong> ' . esc_html( $stats['total_students'] ) . '</p>';
    echo '<p><strong>Số khóa học đã hoàn thành:</strong> ' . esc_html( $stats['completed_count'] ) . '</p>';
    echo '</div>';
}

/**
 * Đăng ký Dashboard Widget
 */
function lp_stats_register_dashboard_widget() {
    wp_add_dashboard_widget(
        'lp_stats_dashboard_widget',
        'LearnPress Stats Dashboard',
        'lp_stats_dashboard_widget_content'
    );
}
add_action( 'wp_dashboard_setup', 'lp_stats_register_dashboard_widget' );

/**
 * Shortcode [lp_total_stats]
 */
function lp_stats_shortcode() {
    if ( ! lp_stats_is_learnpress_active() ) {
        return '<div class="lp-stats-box"><strong>LearnPress chưa được kích hoạt.</strong></div>';
    }

    $stats = lp_stats_get_all_data();

    ob_start();
    ?>
    <div class="lp-stats-box" style="border:1px solid #ddd; padding:16px; border-radius:8px; max-width:500px;">
        <h3>Thống kê LearnPress</h3>
        <ul style="margin:0; padding-left:20px;">
            <li><strong>Tổng số khóa học:</strong> <?php echo esc_html( $stats['total_courses'] ); ?></li>
            <li><strong>Tổng số học viên đã đăng ký:</strong> <?php echo esc_html( $stats['total_students'] ); ?></li>
            <li><strong>Số khóa học đã hoàn thành:</strong> <?php echo esc_html( $stats['completed_count'] ); ?></li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'lp_total_stats', 'lp_stats_shortcode' );
