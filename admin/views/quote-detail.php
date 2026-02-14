<?php
/**
 * Quote detail view - Professional Design
 *
 * @package    Cleaning_Price_Calculator
 * @subpackage Cleaning_Price_Calculator/admin/views
 */

if (!defined('WPINC')) {
    die;
}

// Get quote ID
$quote_id = isset($_GET['quote_id']) ? intval($_GET['quote_id']) : 0;

if (!$quote_id) {
    wp_die(__('Invalid quote ID', 'cleaning-price-calculator'));
}

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['quote_status'])) {
    check_admin_referer('cpc_update_status_' . $quote_id);
    global $wpdb;
    $new_status = sanitize_text_field($_POST['quote_status']);
    $table = $wpdb->prefix . 'cpc_quotes';
    $wpdb->update($table, array('status' => $new_status), array('id' => $quote_id));
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Status updated successfully.', 'cleaning-price-calculator') . '</p></div>';
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    check_admin_referer('cpc_delete_quote_' . $quote_id);
    global $wpdb;
    $quotes_table = $wpdb->prefix . 'cpc_quotes';
    $items_table = $wpdb->prefix . 'cpc_quote_items';
    
    $wpdb->delete($items_table, array('quote_id' => $quote_id));
    $wpdb->delete($quotes_table, array('id' => $quote_id));
    
    wp_redirect(admin_url('admin.php?page=cpc-quotes&message=' . urlencode(__('Quote deleted successfully.', 'cleaning-price-calculator'))));
    exit;
}

// Get quote data
$quote = CPC_Database::get_quote($quote_id);

if (!$quote) {
    wp_die(__('Quote not found', 'cleaning-price-calculator'));
}

$currency = get_option('cpc_currency', 'EUR');
$company_name = get_option('cpc_company_name', get_bloginfo('name'));

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    check_admin_referer('cpc_export_quote_' . $quote_id);
    $quotes_handler = new CPC_Quotes();
    $quotes_handler->export_quote_pdf($quote_id);
    exit;
}
?>

<div class="wrap cpc-admin-wrap cpc-quote-detail-wrap">
    <!-- Header Section -->
    <div class="cpc-detail-header">
        <div class="cpc-detail-header-left">
            <div class="cpc-detail-title-group">
                <h1 class="cpc-detail-title">
                    <?php esc_html_e('Quote', 'cleaning-price-calculator'); ?> 
                    <span class="cpc-detail-id">#<?php echo esc_html($quote->id); ?></span>
                </h1>
                <span class="cpc-status-badge cpc-status-<?php echo esc_attr($quote->status); ?>">
                    <?php echo esc_html(ucfirst($quote->status)); ?>
                </span>
            </div>
            <p class="cpc-detail-date">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php 
                printf(
                    esc_html__('Submitted on %s at %s', 'cleaning-price-calculator'),
                    '<strong>' . date_i18n(get_option('date_format'), strtotime($quote->created_at)) . '</strong>',
                    '<strong>' . date_i18n(get_option('time_format'), strtotime($quote->created_at)) . '</strong>'
                );
                ?>
            </p>
        </div>
        <div class="cpc-detail-header-right no-print">
            <button type="button" class="button button-secondary" onclick="window.print();">
                <span class="dashicons dashicons-printer"></span>
                <?php esc_html_e('Print', 'cleaning-price-calculator'); ?>
            </button>
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=cpc-quotes&action=view&quote_id=' . $quote->id . '&export=pdf'), 'cpc_export_quote_' . $quote->id)); ?>" 
               class="button button-secondary">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Export PDF', 'cleaning-price-calculator'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=cpc-quotes')); ?>" class="button">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
                <?php esc_html_e('Back to Quotes', 'cleaning-price-calculator'); ?>
            </a>
        </div>
    </div>
    
    <div class="cpc-detail-container">
        <!-- Left Column -->
        <div class="cpc-detail-main">
            
            <!-- Customer Information Card -->
            <div class="cpc-detail-card cpc-customer-card">
                <div class="cpc-card-header">
                    <h2>
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php esc_html_e('Customer Information', 'cleaning-price-calculator'); ?>
                    </h2>
                </div>
                <div class="cpc-card-body">
                    <div class="cpc-info-grid">
                        <div class="cpc-info-item">
                            <div class="cpc-info-icon">
                                <span class="dashicons dashicons-admin-users"></span>
                            </div>
                            <div class="cpc-info-content">
                                <label><?php esc_html_e('Full Name', 'cleaning-price-calculator'); ?></label>
                                <p><?php echo esc_html($quote->customer_name); ?></p>
                            </div>
                        </div>
                        
                        <div class="cpc-info-item">
                            <div class="cpc-info-icon">
                                <span class="dashicons dashicons-email"></span>
                            </div>
                            <div class="cpc-info-content">
                                <label><?php esc_html_e('Email Address', 'cleaning-price-calculator'); ?></label>
                                <p><a href="mailto:<?php echo esc_attr($quote->customer_email); ?>"><?php echo esc_html($quote->customer_email); ?></a></p>
                            </div>
                        </div>
                        
                        <div class="cpc-info-item">
                            <div class="cpc-info-icon">
                                <span class="dashicons dashicons-phone"></span>
                            </div>
                            <div class="cpc-info-content">
                                <label><?php esc_html_e('Phone Number', 'cleaning-price-calculator'); ?></label>
                                <p><a href="tel:<?php echo esc_attr($quote->customer_phone); ?>"><?php echo esc_html($quote->customer_phone); ?></a></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($quote->customer_address)): ?>
                        <div class="cpc-info-item cpc-info-item-full">
                            <div class="cpc-info-icon">
                                <span class="dashicons dashicons-location"></span>
                            </div>
                            <div class="cpc-info-content">
                                <label><?php esc_html_e('Address', 'cleaning-price-calculator'); ?></label>
                                <p><?php echo nl2br(esc_html($quote->customer_address)); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($quote->message)): ?>
                    <div class="cpc-customer-message">
                        <div class="cpc-message-header">
                            <span class="dashicons dashicons-format-chat"></span>
                            <?php esc_html_e('Customer Message', 'cleaning-price-calculator'); ?>
                        </div>
                        <div class="cpc-message-body">
                            <?php echo nl2br(esc_html($quote->message)); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quote Items Card -->
            <div class="cpc-detail-card cpc-items-card">
                <div class="cpc-card-header">
                    <h2>
                        <span class="dashicons dashicons-building"></span>
                        <?php esc_html_e('Selected Rooms', 'cleaning-price-calculator'); ?>
                    </h2>
                </div>
                <div class="cpc-card-body">
                    <?php if (!empty($quote->items) && is_array($quote->items)): ?>
                    <div class="cpc-items-table-wrapper">
                        <table class="cpc-items-table">
                            <thead>
                                <tr>
                                    <th class="col-number"><?php esc_html_e('#', 'cleaning-price-calculator'); ?></th>
                                    <th class="col-name"><?php esc_html_e('Room Name', 'cleaning-price-calculator'); ?></th>
                                    <th class="col-count"><?php esc_html_e('Qty', 'cleaning-price-calculator'); ?></th>
                                    <th class="col-area"><?php esc_html_e('Area', 'cleaning-price-calculator'); ?></th>
                                    <th class="col-price"><?php esc_html_e('Rate', 'cleaning-price-calculator'); ?></th>
                                    <th class="col-calc no-print"><?php esc_html_e('Calculation', 'cleaning-price-calculator'); ?></th>
                                    <th class="col-subtotal"><?php esc_html_e('Subtotal', 'cleaning-price-calculator'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $item_number = 1;
                                foreach ($quote->items as $item): 
                                    $room_count = isset($item->room_count) ? intval($item->room_count) : 1;
                                    $calculation = $room_count . ' × ' . number_format($item->area, 2) . ' × ' . number_format($item->price_per_sqm, 2);
                                ?>
                                <tr>
                                    <td class="col-number"><?php echo esc_html($item_number++); ?></td>
                                    <td class="col-name"><strong><?php echo esc_html($item->room_name); ?></strong></td>
                                    <td class="col-count"><span class="count-badge"><?php echo esc_html($room_count); ?></span></td>
                                    <td class="col-area"><?php echo esc_html(number_format($item->area, 2)); ?> m²</td>
                                    <td class="col-price"><?php echo esc_html(number_format($item->price_per_sqm, 2)); ?> <?php echo esc_html($currency); ?></td>
                                    <td class="col-calc no-print"><code><?php echo esc_html($calculation); ?></code></td>
                                    <td class="col-subtotal"><strong><?php echo esc_html(number_format($item->subtotal, 2)); ?> <?php echo esc_html($currency); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="6" class="total-label">
                                        <?php esc_html_e('Total Price', 'cleaning-price-calculator'); ?>
                                    </td>
                                    <td class="total-amount">
                                        <?php echo esc_html(number_format($quote->total_price, 2)); ?> <?php echo esc_html($currency); ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="cpc-empty-state">
                        <span class="dashicons dashicons-warning"></span>
                        <p><?php esc_html_e('No items found for this quote.', 'cleaning-price-calculator'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        
        <!-- Right Sidebar -->
        <div class="cpc-detail-sidebar">
            
            <!-- Status Management Card -->
            <div class="cpc-detail-card cpc-status-card no-print">
                <div class="cpc-card-header">
                    <h2>
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php esc_html_e('Quote Status', 'cleaning-price-calculator'); ?>
                    </h2>
                </div>
                <div class="cpc-card-body">
                    <form method="post" class="cpc-status-form">
                        <?php wp_nonce_field('cpc_update_status_' . $quote_id); ?>
                        <div class="cpc-form-group">
                            <label for="quote_status"><?php esc_html_e('Update Status', 'cleaning-price-calculator'); ?></label>
                            <select name="quote_status" id="quote_status" class="cpc-select">
                                <option value="pending" <?php selected($quote->status, 'pending'); ?>><?php esc_html_e('Pending', 'cleaning-price-calculator'); ?></option>
                                <option value="processing" <?php selected($quote->status, 'processing'); ?>><?php esc_html_e('Processing', 'cleaning-price-calculator'); ?></option>
                                <option value="completed" <?php selected($quote->status, 'completed'); ?>><?php esc_html_e('Completed', 'cleaning-price-calculator'); ?></option>
                                <option value="cancelled" <?php selected($quote->status, 'cancelled'); ?>><?php esc_html_e('Cancelled', 'cleaning-price-calculator'); ?></option>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="button button-primary button-block">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Update Status', 'cleaning-price-calculator'); ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Quick Actions Card -->
            <div class="cpc-detail-card cpc-actions-card no-print">
                <div class="cpc-card-header">
                    <h2>
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php esc_html_e('Quick Actions', 'cleaning-price-calculator'); ?>
                    </h2>
                </div>
                <div class="cpc-card-body">
                    <div class="cpc-action-buttons">
                        <a href="mailto:<?php echo esc_attr($quote->customer_email); ?>?subject=<?php echo esc_attr(sprintf(__('Re: Quote #%d', 'cleaning-price-calculator'), $quote->id)); ?>" 
                           class="cpc-action-btn cpc-action-email">
                            <span class="dashicons dashicons-email"></span>
                            <span><?php esc_html_e('Send Email', 'cleaning-price-calculator'); ?></span>
                        </a>
                        
                        <a href="tel:<?php echo esc_attr($quote->customer_phone); ?>" 
                           class="cpc-action-btn cpc-action-phone">
                            <span class="dashicons dashicons-phone"></span>
                            <span><?php esc_html_e('Call Customer', 'cleaning-price-calculator'); ?></span>
                        </a>
                        
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=cpc-quotes&action=view&quote_id=' . $quote->id . '&action=delete'), 'cpc_delete_quote_' . $quote->id)); ?>" 
                           class="cpc-action-btn cpc-action-delete"
                           onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this quote? This action cannot be undone.', 'cleaning-price-calculator'); ?>');">
                            <span class="dashicons dashicons-trash"></span>
                            <span><?php esc_html_e('Delete Quote', 'cleaning-price-calculator'); ?></span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Summary Card -->
            <div class="cpc-detail-card cpc-summary-card">
                <div class="cpc-card-header">
                    <h2>
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e('Summary', 'cleaning-price-calculator'); ?>
                    </h2>
                </div>
                <div class="cpc-card-body">
                    <div class="cpc-summary-item">
                        <span class="label"><?php esc_html_e('Quote ID', 'cleaning-price-calculator'); ?></span>
                        <span class="value">#<?php echo esc_html($quote->id); ?></span>
                    </div>
                    <div class="cpc-summary-item">
                        <span class="label"><?php esc_html_e('Total Rooms', 'cleaning-price-calculator'); ?></span>
                        <span class="value"><?php echo count($quote->items); ?></span>
                    </div>
                    <div class="cpc-summary-item">
                        <span class="label"><?php esc_html_e('Currency', 'cleaning-price-calculator'); ?></span>
                        <span class="value"><?php echo esc_html($quote->currency); ?></span>
                    </div>
                    <div class="cpc-summary-item">
                        <span class="label"><?php esc_html_e('IP Address', 'cleaning-price-calculator'); ?></span>
                        <span class="value"><code><?php echo esc_html($quote->ip_address); ?></code></span>
                    </div>
                    <div class="cpc-summary-total">
                        <span class="label"><?php esc_html_e('Total Amount', 'cleaning-price-calculator'); ?></span>
                        <span class="value"><?php echo esc_html(number_format($quote->total_price, 2)); ?> <?php echo esc_html($currency); ?></span>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

