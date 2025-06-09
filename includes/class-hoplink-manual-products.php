<?php
/**
 * Manual Products Management Class
 *
 * Handles ASIN-based manual product management for Amazon products
 * without using PA-API
 */
class HopLink_Manual_Products {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add hooks for AJAX handlers
        add_action('wp_ajax_hoplink_get_manual_products', [$this, 'ajax_get_products']);
        add_action('wp_ajax_hoplink_save_manual_product', [$this, 'ajax_save_product']);
        add_action('wp_ajax_hoplink_delete_manual_product', [$this, 'ajax_delete_product']);
        add_action('wp_ajax_hoplink_import_manual_products', [$this, 'ajax_import_products']);
    }
    
    /**
     * Get all manual products
     *
     * @param array $args Query arguments
     * @return array Products array
     */
    public function get_products($args = []) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hoplink_manual_products';
        
        $defaults = [
            'status' => 'active',
            'orderby' => 'product_name',
            'order' => 'ASC',
            'limit' => -1,
            'offset' => 0
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = ['1=1'];
        $where_values = [];
        
        if ($args['status'] !== 'all') {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        if (!empty($args['category'])) {
            $where_clauses[] = 'category = %s';
            $where_values[] = $args['category'];
        }
        
        if (!empty($args['search'])) {
            $where_clauses[] = '(product_name LIKE %s OR asin LIKE %s OR keywords LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $query = "SELECT * FROM $table_name WHERE $where_sql";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        // Add order by
        $allowed_orderby = ['product_name', 'price', 'created_at', 'updated_at'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'product_name';
        $order = $args['order'] === 'DESC' ? 'DESC' : 'ASC';
        $query .= " ORDER BY $orderby $order";
        
        // Add limit
        if ($args['limit'] > 0) {
            $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get a single product by ASIN
     *
     * @param string $asin ASIN code
     * @return array|null Product data or null
     */
    public function get_product_by_asin($asin) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hoplink_manual_products';
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE asin = %s", $asin),
            ARRAY_A
        );
    }
    
    /**
     * Save or update a manual product
     *
     * @param array $data Product data
     * @return int|false Product ID or false on error
     */
    public function save_product($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hoplink_manual_products';
        
        // Validate required fields
        if (empty($data['asin']) || empty($data['product_name'])) {
            return false;
        }
        
        // Prepare data
        $product_data = [
            'asin' => sanitize_text_field($data['asin']),
            'product_name' => sanitize_text_field($data['product_name']),
            'price' => !empty($data['price']) ? floatval($data['price']) : null,
            'image_url' => !empty($data['image_url']) ? esc_url_raw($data['image_url']) : '',
            'description' => !empty($data['description']) ? wp_kses_post($data['description']) : '',
            'features' => !empty($data['features']) ? wp_json_encode($data['features']) : '',
            'category' => !empty($data['category']) ? sanitize_text_field($data['category']) : '',
            'keywords' => !empty($data['keywords']) ? sanitize_text_field($data['keywords']) : '',
            'status' => !empty($data['status']) ? sanitize_text_field($data['status']) : 'active'
        ];
        
        // Check if product exists
        $existing = $this->get_product_by_asin($product_data['asin']);
        
        if ($existing) {
            // Update existing product
            $result = $wpdb->update(
                $table_name,
                $product_data,
                ['id' => $existing['id']],
                ['%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s'],
                ['%d']
            );
            
            return $result !== false ? $existing['id'] : false;
        } else {
            // Insert new product
            $result = $wpdb->insert(
                $table_name,
                $product_data,
                ['%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s']
            );
            
            return $result !== false ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Delete a manual product
     *
     * @param int $id Product ID
     * @return bool Success
     */
    public function delete_product($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hoplink_manual_products';
        
        return $wpdb->delete($table_name, ['id' => intval($id)], ['%d']) !== false;
    }
    
    /**
     * Search manual products by keyword
     *
     * @param string $keyword Search keyword
     * @param int $limit Number of results
     * @return array Products array
     */
    public function search_products($keyword, $limit = 10) {
        return $this->get_products([
            'search' => $keyword,
            'limit' => $limit,
            'status' => 'active'
        ]);
    }
    
    /**
     * Generate affiliate URL for manual product
     *
     * @param string $asin ASIN code
     * @return string Affiliate URL
     */
    public function generate_affiliate_url($asin) {
        $associate_tag = get_option('hoplink_amazon_associate_tag', '');
        
        if (empty($associate_tag)) {
            return "https://www.amazon.co.jp/dp/{$asin}";
        }
        
        return "https://www.amazon.co.jp/dp/{$asin}?tag={$associate_tag}";
    }
    
    /**
     * Format product for display
     *
     * @param array $product Raw product data
     * @return array Formatted product data
     */
    public function format_product($product) {
        $features = !empty($product['features']) ? json_decode($product['features'], true) : [];
        
        return [
            'product_id' => $product['asin'],
            'platform' => 'amazon',
            'title' => $product['product_name'],
            'price' => $product['price'],
            'price_formatted' => !empty($product['price']) ? '¥' . number_format($product['price']) : '',
            'image_url' => $product['image_url'],
            'description' => $product['description'],
            'features' => is_array($features) ? $features : [],
            'affiliate_url' => $this->generate_affiliate_url($product['asin']),
            'availability' => 'In Stock',
            'is_prime' => false,
            'is_manual' => true
        ];
    }
    
    /**
     * Import products from CSV
     *
     * @param string $csv_file Path to CSV file
     * @return array Import results
     */
    public function import_from_csv($csv_file) {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        if (!file_exists($csv_file) || !is_readable($csv_file)) {
            $results['errors'][] = 'CSV file not found or not readable';
            return $results;
        }
        
        $handle = fopen($csv_file, 'r');
        if ($handle === false) {
            $results['errors'][] = 'Failed to open CSV file';
            return $results;
        }
        
        // Skip header row
        $header = fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 2) {
                continue;
            }
            
            $product_data = [
                'asin' => $data[0],
                'product_name' => $data[1],
                'price' => !empty($data[2]) ? floatval($data[2]) : null,
                'image_url' => !empty($data[3]) ? $data[3] : '',
                'description' => !empty($data[4]) ? $data[4] : '',
                'category' => !empty($data[5]) ? $data[5] : '',
                'keywords' => !empty($data[6]) ? $data[6] : ''
            ];
            
            if ($this->save_product($product_data)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed to import ASIN: {$data[0]}";
            }
        }
        
        fclose($handle);
        
        return $results;
    }
    
    /**
     * AJAX handler: Get manual products
     */
    public function ajax_get_products() {
        // Check nonce
        if (!check_ajax_referer('hoplink_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }
        
        $args = [
            'status' => sanitize_text_field($_POST['status'] ?? 'active'),
            'search' => sanitize_text_field($_POST['search'] ?? ''),
            'category' => sanitize_text_field($_POST['category'] ?? ''),
            'orderby' => sanitize_text_field($_POST['orderby'] ?? 'product_name'),
            'order' => sanitize_text_field($_POST['order'] ?? 'ASC'),
            'limit' => intval($_POST['limit'] ?? -1),
            'offset' => intval($_POST['offset'] ?? 0)
        ];
        
        $products = $this->get_products($args);
        
        wp_send_json_success([
            'products' => $products,
            'total' => count($products)
        ]);
    }
    
    /**
     * AJAX handler: Save manual product
     */
    public function ajax_save_product() {
        // Check nonce
        if (!check_ajax_referer('hoplink_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }
        
        $data = [
            'asin' => sanitize_text_field($_POST['asin'] ?? ''),
            'product_name' => sanitize_text_field($_POST['product_name'] ?? ''),
            'price' => floatval($_POST['price'] ?? 0),
            'image_url' => esc_url_raw($_POST['image_url'] ?? ''),
            'description' => wp_kses_post($_POST['description'] ?? ''),
            'features' => !empty($_POST['features']) ? array_map('sanitize_text_field', $_POST['features']) : [],
            'category' => sanitize_text_field($_POST['category'] ?? ''),
            'keywords' => sanitize_text_field($_POST['keywords'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'active')
        ];
        
        $product_id = $this->save_product($data);
        
        if ($product_id) {
            wp_send_json_success([
                'message' => 'Product saved successfully',
                'product_id' => $product_id
            ]);
        } else {
            wp_send_json_error('Failed to save product');
        }
    }
    
    /**
     * AJAX handler: Delete manual product
     */
    public function ajax_delete_product() {
        // Check nonce
        if (!check_ajax_referer('hoplink_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if ($this->delete_product($id)) {
            wp_send_json_success(['message' => 'Product deleted successfully']);
        } else {
            wp_send_json_error('Failed to delete product');
        }
    }
    
    /**
     * AJAX handler: Import products from CSV
     */
    public function ajax_import_products() {
        // Check nonce
        if (!check_ajax_referer('hoplink_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }
        
        // Handle file upload
        if (empty($_FILES['csv_file'])) {
            wp_send_json_error('No file uploaded');
            return;
        }
        
        $uploaded_file = $_FILES['csv_file'];
        
        if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('File upload failed');
            return;
        }
        
        // Validate file type
        $file_type = wp_check_filetype($uploaded_file['name']);
        if ($file_type['ext'] !== 'csv') {
            wp_send_json_error('Please upload a CSV file');
            return;
        }
        
        // Import products
        $results = $this->import_from_csv($uploaded_file['tmp_name']);
        
        if (!empty($results['errors'])) {
            wp_send_json_error([
                'message' => 'Import completed with errors',
                'results' => $results
            ]);
        } else {
            wp_send_json_success([
                'message' => sprintf('Import completed: %d products imported successfully', $results['success']),
                'results' => $results
            ]);
        }
    }
}