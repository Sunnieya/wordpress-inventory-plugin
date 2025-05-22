<?php
/**
 * Plugin Name: 简易库存管理
 * Description: 在WordPress中管理产品库存
 * Version: 1.0
 * Author: Your Name
 */

// 防止直接访问插件文件
if (!defined('ABSPATH')) {
    exit;
}

// 添加管理菜单
function inventory_management_menu() {
    add_menu_page(
        '库存管理',
        '库存管理',
        'manage_options',
        'inventory-management',
        'inventory_management_page',
        'dashicons-clipboard',
        25
    );
}
add_action('admin_menu', 'inventory_management_menu');

// 库存管理页面
function inventory_management_page() {
    // 处理表单提交（添加/更新库存）
    if (isset($_POST['submit'])) {
        // 安全验证
        if (!wp_verify_nonce($_POST['inventory_nonce'], 'inventory_action')) {
            die('安全验证失败');
        }
        
        // 处理库存更新逻辑
        $product_name = sanitize_text_field($_POST['product_name']);
        $quantity = intval($_POST['quantity']);
        
        // 存储数据到数据库
        global $wpdb;
        $table_name = $wpdb->prefix . 'inventory';
        
        // 检查表是否存在，不存在则创建
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                product_name varchar(255) NOT NULL,
                quantity int(11) NOT NULL,
                last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // 插入或更新库存
        $wpdb->replace(
            $table_name,
            array(
                'product_name' => $product_name,
                'quantity' => $quantity
            )
        );
        
        echo '<div class="updated"><p>库存已更新</p></div>';
    }
    
    // 显示库存表单和列表
    ?>
    <div class="wrap">
        <h1>库存管理</h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('inventory_action', 'inventory_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="product_name">产品名称</label></th>
                    <td><input type="text" name="product_name" id="product_name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="quantity">数量</label></th>
                    <td><input type="number" name="quantity" id="quantity" min="0" required></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit" class="button-primary" value="更新库存">
            </p>
        </form>
        
        <h2>当前库存列表</h2>
        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . 'inventory';
        $products = $wpdb->get_results("SELECT * FROM $table_name ORDER BY last_updated DESC");
        
        if ($products) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>产品名称</th><th>数量</th><th>最后更新</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($products as $product) {
                echo '<tr>';
                echo '<td>' . esc_html($product->product_name) . '</td>';
                echo '<td>' . esc_html($product->quantity) . '</td>';
                echo '<td>' . esc_html($product->last_updated) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>暂无库存数据</p>';
        }
        ?>
    </div>
    <?php
}

// 激活插件时创建数据库表
function inventory_activation() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'inventory';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product_name varchar(255) NOT NULL,
        quantity int(11) NOT NULL,
        last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'inventory_activation');