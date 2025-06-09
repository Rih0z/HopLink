<?php
/**
 * Manual Products Management Page
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Initialize manual products manager
$manual_products = new HopLink_Manual_Products();

// Handle bulk actions
if (isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'delete' && !empty($_POST['product_ids'])) {
    check_admin_referer('hoplink_bulk_action');
    
    $deleted = 0;
    foreach ($_POST['product_ids'] as $id) {
        if ($manual_products->delete_product(intval($id))) {
            $deleted++;
        }
    }
    
    echo '<div class="notice notice-success"><p>' . sprintf(__('%d products deleted successfully.', 'hoplink'), $deleted) . '</p></div>';
}

// Get products
$products = $manual_products->get_products();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Manual Products', 'hoplink'); ?></h1>
    <a href="#" class="page-title-action" id="add-new-product"><?php _e('Add New', 'hoplink'); ?></a>
    <a href="#" class="page-title-action" id="import-products"><?php _e('Import CSV', 'hoplink'); ?></a>
    
    <hr class="wp-header-end">
    
    <div class="notice notice-info">
        <p><?php _e('Manage Amazon products manually without using PA-API. Add ASIN codes and product information to create affiliate links.', 'hoplink'); ?></p>
    </div>
    
    <!-- Search Box -->
    <p class="search-box">
        <label class="screen-reader-text" for="product-search-input"><?php _e('Search Products:', 'hoplink'); ?></label>
        <input type="search" id="product-search-input" name="s" value="">
        <input type="submit" id="search-submit" class="button" value="<?php _e('Search Products', 'hoplink'); ?>">
    </p>
    
    <!-- Products Table -->
    <form method="post" id="products-form">
        <?php wp_nonce_field('hoplink_bulk_action'); ?>
        
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'hoplink'); ?></label>
                <select name="bulk_action" id="bulk-action-selector-top">
                    <option value=""><?php _e('Bulk Actions', 'hoplink'); ?></option>
                    <option value="delete"><?php _e('Delete', 'hoplink'); ?></option>
                </select>
                <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'hoplink'); ?>">
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-1">
                    </td>
                    <th class="manage-column column-asin"><?php _e('ASIN', 'hoplink'); ?></th>
                    <th class="manage-column column-image"><?php _e('Image', 'hoplink'); ?></th>
                    <th class="manage-column column-name"><?php _e('Product Name', 'hoplink'); ?></th>
                    <th class="manage-column column-price"><?php _e('Price', 'hoplink'); ?></th>
                    <th class="manage-column column-category"><?php _e('Category', 'hoplink'); ?></th>
                    <th class="manage-column column-status"><?php _e('Status', 'hoplink'); ?></th>
                    <th class="manage-column column-date"><?php _e('Updated', 'hoplink'); ?></th>
                    <th class="manage-column column-actions"><?php _e('Actions', 'hoplink'); ?></th>
                </tr>
            </thead>
            <tbody id="products-list">
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="9" class="no-items"><?php _e('No products found.', 'hoplink'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr data-product-id="<?php echo esc_attr($product['id']); ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="product_ids[]" value="<?php echo esc_attr($product['id']); ?>">
                            </th>
                            <td class="column-asin">
                                <strong><?php echo esc_html($product['asin']); ?></strong>
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="<?php echo esc_url($manual_products->generate_affiliate_url($product['asin'])); ?>" target="_blank"><?php _e('View', 'hoplink'); ?></a>
                                    </span>
                                </div>
                            </td>
                            <td class="column-image">
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="<?php echo esc_url($product['image_url']); ?>" alt="" style="width: 50px; height: auto;">
                                <?php else: ?>
                                    <span class="dashicons dashicons-format-image" style="font-size: 30px; color: #ccc;"></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-name">
                                <?php echo esc_html($product['product_name']); ?>
                                <?php if (!empty($product['keywords'])): ?>
                                    <br><small><?php _e('Keywords:', 'hoplink'); ?> <?php echo esc_html($product['keywords']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="column-price">
                                <?php if (!empty($product['price'])): ?>
                                    ¥<?php echo number_format($product['price']); ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="column-category">
                                <?php echo esc_html($product['category'] ?: '—'); ?>
                            </td>
                            <td class="column-status">
                                <?php if ($product['status'] === 'active'): ?>
                                    <span class="status-active" style="color: green;">●</span> <?php _e('Active', 'hoplink'); ?>
                                <?php else: ?>
                                    <span class="status-inactive" style="color: red;">●</span> <?php _e('Inactive', 'hoplink'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="column-date">
                                <?php echo date_i18n(get_option('date_format'), strtotime($product['updated_at'])); ?>
                            </td>
                            <td class="column-actions">
                                <button type="button" class="button button-small edit-product" data-id="<?php echo esc_attr($product['id']); ?>"><?php _e('Edit', 'hoplink'); ?></button>
                                <button type="button" class="button button-small delete-product" data-id="<?php echo esc_attr($product['id']); ?>"><?php _e('Delete', 'hoplink'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>

<!-- Add/Edit Product Modal -->
<div id="product-modal" class="hoplink-modal" style="display: none;">
    <div class="hoplink-modal-content">
        <span class="hoplink-modal-close">&times;</span>
        <h2 id="modal-title"><?php _e('Add New Product', 'hoplink'); ?></h2>
        
        <form id="product-form">
            <input type="hidden" id="product-id" name="id" value="">
            
            <table class="form-table">
                <tr>
                    <th><label for="product-asin"><?php _e('ASIN Code', 'hoplink'); ?> <span class="required">*</span></label></th>
                    <td>
                        <input type="text" id="product-asin" name="asin" class="regular-text" required>
                        <p class="description"><?php _e('Amazon Standard Identification Number', 'hoplink'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="product-name"><?php _e('Product Name', 'hoplink'); ?> <span class="required">*</span></label></th>
                    <td>
                        <input type="text" id="product-name" name="product_name" class="large-text" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="product-price"><?php _e('Price (¥)', 'hoplink'); ?></label></th>
                    <td>
                        <input type="number" id="product-price" name="price" class="regular-text" min="0" step="1">
                    </td>
                </tr>
                <tr>
                    <th><label for="product-image"><?php _e('Image URL', 'hoplink'); ?></label></th>
                    <td>
                        <input type="url" id="product-image" name="image_url" class="large-text">
                        <button type="button" class="button" id="upload-image"><?php _e('Upload Image', 'hoplink'); ?></button>
                        <div id="image-preview" style="margin-top: 10px;"></div>
                    </td>
                </tr>
                <tr>
                    <th><label for="product-description"><?php _e('Description', 'hoplink'); ?></label></th>
                    <td>
                        <textarea id="product-description" name="description" rows="4" class="large-text"></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="product-features"><?php _e('Features', 'hoplink'); ?></label></th>
                    <td>
                        <div id="features-container">
                            <div class="feature-input">
                                <input type="text" name="features[]" class="regular-text">
                                <button type="button" class="button remove-feature" style="display: none;"><?php _e('Remove', 'hoplink'); ?></button>
                            </div>
                        </div>
                        <button type="button" class="button" id="add-feature"><?php _e('Add Feature', 'hoplink'); ?></button>
                    </td>
                </tr>
                <tr>
                    <th><label for="product-category"><?php _e('Category', 'hoplink'); ?></label></th>
                    <td>
                        <select id="product-category" name="category" class="regular-text">
                            <option value=""><?php _e('Select Category', 'hoplink'); ?></option>
                            <option value="beer"><?php _e('Beer', 'hoplink'); ?></option>
                            <option value="glass"><?php _e('Glass', 'hoplink'); ?></option>
                            <option value="gift"><?php _e('Gift Set', 'hoplink'); ?></option>
                            <option value="book"><?php _e('Book', 'hoplink'); ?></option>
                            <option value="accessory"><?php _e('Accessory', 'hoplink'); ?></option>
                            <option value="other"><?php _e('Other', 'hoplink'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="product-keywords"><?php _e('Keywords', 'hoplink'); ?></label></th>
                    <td>
                        <input type="text" id="product-keywords" name="keywords" class="large-text">
                        <p class="description"><?php _e('Comma-separated keywords for matching', 'hoplink'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="product-status"><?php _e('Status', 'hoplink'); ?></label></th>
                    <td>
                        <select id="product-status" name="status" class="regular-text">
                            <option value="active"><?php _e('Active', 'hoplink'); ?></option>
                            <option value="inactive"><?php _e('Inactive', 'hoplink'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Save Product', 'hoplink'); ?></button>
                <button type="button" class="button cancel-modal"><?php _e('Cancel', 'hoplink'); ?></button>
            </p>
        </form>
    </div>
</div>

<!-- Import CSV Modal -->
<div id="import-modal" class="hoplink-modal" style="display: none;">
    <div class="hoplink-modal-content">
        <span class="hoplink-modal-close">&times;</span>
        <h2><?php _e('Import Products from CSV', 'hoplink'); ?></h2>
        
        <form id="import-form" enctype="multipart/form-data">
            <p><?php _e('Upload a CSV file with the following columns:', 'hoplink'); ?></p>
            <ol>
                <li>ASIN</li>
                <li>Product Name</li>
                <li>Price (optional)</li>
                <li>Image URL (optional)</li>
                <li>Description (optional)</li>
                <li>Category (optional)</li>
                <li>Keywords (optional)</li>
            </ol>
            
            <p>
                <label for="csv-file"><?php _e('CSV File:', 'hoplink'); ?></label><br>
                <input type="file" id="csv-file" name="csv_file" accept=".csv" required>
            </p>
            
            <p>
                <a href="<?php echo HOPLINK_PLUGIN_URL; ?>assets/sample-products.csv" download><?php _e('Download Sample CSV', 'hoplink'); ?></a>
            </p>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Import', 'hoplink'); ?></button>
                <button type="button" class="button cancel-modal"><?php _e('Cancel', 'hoplink'); ?></button>
            </p>
        </form>
    </div>
</div>

<style>
.hoplink-modal {
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
}

.hoplink-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
}

.hoplink-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.hoplink-modal-close:hover,
.hoplink-modal-close:focus {
    color: black;
}

.required {
    color: red;
}

.feature-input {
    margin-bottom: 5px;
}

#image-preview img {
    max-width: 200px;
    height: auto;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Modal handling
    $('#add-new-product').on('click', function(e) {
        e.preventDefault();
        $('#modal-title').text('<?php _e('Add New Product', 'hoplink'); ?>');
        $('#product-form')[0].reset();
        $('#product-id').val('');
        $('#image-preview').empty();
        $('#product-modal').show();
    });
    
    $('#import-products').on('click', function(e) {
        e.preventDefault();
        $('#import-modal').show();
    });
    
    $('.hoplink-modal-close, .cancel-modal').on('click', function() {
        $('.hoplink-modal').hide();
    });
    
    // Edit product
    $(document).on('click', '.edit-product', function() {
        var productId = $(this).data('id');
        var row = $(this).closest('tr');
        
        // Populate form with product data
        $('#modal-title').text('<?php _e('Edit Product', 'hoplink'); ?>');
        $('#product-id').val(productId);
        
        // You would normally load product data via AJAX here
        // For now, we'll use data from the table
        $('#product-modal').show();
    });
    
    // Delete product
    $(document).on('click', '.delete-product', function() {
        if (!confirm('<?php _e('Are you sure you want to delete this product?', 'hoplink'); ?>')) {
            return;
        }
        
        var productId = $(this).data('id');
        var row = $(this).closest('tr');
        
        $.post(hoplink_ajax.ajax_url, {
            action: 'hoplink_delete_manual_product',
            nonce: hoplink_ajax.nonce,
            id: productId
        }, function(response) {
            if (response.success) {
                row.fadeOut(function() {
                    row.remove();
                });
            } else {
                alert(response.data || '<?php _e('Failed to delete product', 'hoplink'); ?>');
            }
        });
    });
    
    // Save product
    $('#product-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.post(hoplink_ajax.ajax_url, {
            action: 'hoplink_save_manual_product',
            nonce: hoplink_ajax.nonce,
            ...Object.fromEntries(new URLSearchParams(formData))
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || '<?php _e('Failed to save product', 'hoplink'); ?>');
            }
        });
    });
    
    // Import CSV
    $('#import-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'hoplink_import_manual_products');
        formData.append('nonce', hoplink_ajax.nonce);
        
        $.ajax({
            url: hoplink_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || '<?php _e('Import failed', 'hoplink'); ?>');
                }
            }
        });
    });
    
    // Add feature input
    $('#add-feature').on('click', function() {
        var newFeature = $('.feature-input:first').clone();
        newFeature.find('input').val('');
        newFeature.find('.remove-feature').show();
        $('#features-container').append(newFeature);
    });
    
    // Remove feature input
    $(document).on('click', '.remove-feature', function() {
        $(this).closest('.feature-input').remove();
    });
    
    // Image upload
    $('#upload-image').on('click', function(e) {
        e.preventDefault();
        
        var frame = wp.media({
            title: '<?php _e('Select or Upload Image', 'hoplink'); ?>',
            button: {
                text: '<?php _e('Use this image', 'hoplink'); ?>'
            },
            multiple: false
        });
        
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#product-image').val(attachment.url);
            $('#image-preview').html('<img src="' + attachment.url + '" alt="">');
        });
        
        frame.open();
    });
    
    // Search functionality
    $('#search-submit').on('click', function(e) {
        e.preventDefault();
        var searchTerm = $('#product-search-input').val();
        
        // Implement search via AJAX
        $.post(hoplink_ajax.ajax_url, {
            action: 'hoplink_get_manual_products',
            nonce: hoplink_ajax.nonce,
            search: searchTerm
        }, function(response) {
            if (response.success) {
                // Update table with search results
                // Implementation depends on your needs
            }
        });
    });
});
</script>