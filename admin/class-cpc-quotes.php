<?php
/**
 * Quotes management
 *
 * @package    Cleaning_Price_Calculator
 * @subpackage Cleaning_Price_Calculator/admin
 */

class CPC_Quotes {
    
    /**
     * Export quote as PDF
     */
    public function export_quote_pdf($quote_id) {
        // Get quote data
        $quote = CPC_Database::get_quote($quote_id);
        
        if (!$quote) {
            wp_die(__('Quote not found.', 'cleaning-price-calculator'));
        }
        
        // Basic HTML to PDF conversion
        // For production, consider using a library like TCPDF or Dompdf
        $this->generate_simple_pdf($quote);
    }
    
    /**
     * Generate professional PDF invoice
     */
    private function generate_simple_pdf($quote) {
        $company_name = get_option('cpc_company_name', get_bloginfo('name'));
        $company_phone = get_option('cpc_contact_phone', '');
        $company_email = get_option('cpc_admin_email', get_option('admin_email'));
        $currency = get_option('cpc_currency', 'EUR');
        
        // Currency symbols
        $currency_symbols = array(
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'CHF' => 'CHF',
            'AED' => 'د.إ',
            'SAR' => 'ر.س'
        );
        
        $currency_symbol = isset($currency_symbols[$currency]) ? $currency_symbols[$currency] : $currency;
        
        // Set headers for HTML download (will be converted to PDF with browser print)
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="quote-' . $quote->id . '.html"');
        
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html__('Quote', 'cleaning-price-calculator') . ' #' . $quote->id; ?></title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background: #f5f5f5;
                    padding: 20px;
                }
                
                .invoice-container {
                    max-width: 800px;
                    margin: 0 auto;
                    background: white;
                    box-shadow: 0 0 20px rgba(0,0,0,0.1);
                }
                
                /* Header */
                .invoice-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 40px;
                    position: relative;
                }
                
                .invoice-header::after {
                    content: '';
                    position: absolute;
                    bottom: -20px;
                    left: 0;
                    right: 0;
                    height: 20px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    clip-path: polygon(0 0, 100% 0, 100% 100%, 0 0);
                }
                
                .company-info {
                    margin-bottom: 30px;
                }
                
                .company-name {
                    font-size: 32px;
                    font-weight: 700;
                    margin-bottom: 10px;
                    letter-spacing: -0.5px;
                }
                
                .company-details {
                    font-size: 14px;
                    opacity: 0.95;
                    line-height: 1.8;
                }
                
                .invoice-title-section {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-end;
                }
                
                .invoice-title {
                    font-size: 24px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                }
                
                .invoice-number {
                    font-size: 36px;
                    font-weight: 700;
                    text-align: right;
                    line-height: 1;
                }
                
                .invoice-number small {
                    font-size: 14px;
                    display: block;
                    margin-bottom: 5px;
                    opacity: 0.9;
                    font-weight: 400;
                }
                
                /* Body */
                .invoice-body {
                    padding: 60px 40px 40px;
                }
                
                /* Info Section */
                .info-section {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 40px;
                    margin-bottom: 40px;
                }
                
                .info-block h3 {
                    font-size: 12px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    color: #666;
                    margin-bottom: 15px;
                    font-weight: 600;
                }
                
                .info-content {
                    background: #f9fafb;
                    padding: 20px;
                    border-radius: 8px;
                    border-left: 4px solid #667eea;
                }
                
                .info-content p {
                    margin-bottom: 8px;
                    font-size: 14px;
                    color: #333;
                }
                
                .info-content p:last-child {
                    margin-bottom: 0;
                }
                
                .info-label {
                    font-weight: 600;
                    color: #555;
                    display: inline-block;
                    min-width: 80px;
                }
                
                /* Items Table */
                .items-section {
                    margin: 40px 0;
                }
                
                .section-title {
                    font-size: 18px;
                    font-weight: 700;
                    color: #1f2937;
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 3px solid #667eea;
                }
                
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 30px;
                    background: white;
                }
                
                thead {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                }
                
                th {
                    padding: 15px 12px;
                    text-align: left;
                    font-weight: 600;
                    font-size: 12px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                th.text-center {
                    text-align: center;
                }
                
                th.text-right {
                    text-align: right;
                }
                
                tbody tr {
                    border-bottom: 1px solid #e5e7eb;
                }
                
                tbody tr:hover {
                    background: #f9fafb;
                }
                
                td {
                    padding: 15px 12px;
                    font-size: 14px;
                    color: #374151;
                }
                
                td.text-center {
                    text-align: center;
                }
                
                td.text-right {
                    text-align: right;
                }
                
                .item-name {
                    font-weight: 600;
                    color: #1f2937;
                }
                
                .count-badge {
                    display: inline-block;
                    background: #667eea;
                    color: white;
                    padding: 4px 12px;
                    border-radius: 12px;
                    font-weight: 700;
                    font-size: 12px;
                }
                
                .calculation {
                    font-family: 'Courier New', monospace;
                    font-size: 12px;
                    color: #6b7280;
                    background: #f3f4f6;
                    padding: 4px 8px;
                    border-radius: 4px;
                }
                
                /* Totals */
                .totals-section {
                    background: #f9fafb;
                    padding: 30px;
                    border-radius: 8px;
                    margin-top: 30px;
                }
                
                .total-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 12px 0;
                    font-size: 14px;
                    color: #374151;
                }
                
                .total-row.subtotal {
                    border-bottom: 1px solid #e5e7eb;
                }
                
                .total-row.grand-total {
                    border-top: 3px solid #667eea;
                    padding-top: 20px;
                    margin-top: 10px;
                    font-size: 20px;
                    font-weight: 700;
                    color: #1f2937;
                }
                
                .total-row .amount {
                    font-weight: 700;
                    color: #667eea;
                }
                
                .total-row.grand-total .amount {
                    font-size: 28px;
                    color: #667eea;
                }
                
                /* Message */
                .message-section {
                    margin-top: 30px;
                    padding: 20px;
                    background: #eff6ff;
                    border-left: 4px solid #3b82f6;
                    border-radius: 4px;
                }
                
                .message-section h3 {
                    font-size: 14px;
                    color: #1e40af;
                    margin-bottom: 10px;
                    font-weight: 600;
                }
                
                .message-section p {
                    color: #1f2937;
                    font-size: 14px;
                    line-height: 1.8;
                }
                
                /* Footer */
                .invoice-footer {
                    background: #1f2937;
                    color: white;
                    padding: 30px 40px;
                    text-align: center;
                }
                
                .footer-text {
                    font-size: 13px;
                    opacity: 0.9;
                    line-height: 1.8;
                }
                
                .thank-you {
                    font-size: 18px;
                    font-weight: 600;
                    margin-bottom: 15px;
                    color: #667eea;
                }
                
                /* Status Badge */
                .status-badge {
                    display: inline-block;
                    padding: 6px 16px;
                    border-radius: 20px;
                    font-size: 11px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                
                .status-pending {
                    background: #fef3c7;
                    color: #92400e;
                }
                
                .status-processing {
                    background: #dbeafe;
                    color: #1e40af;
                }
                
                .status-completed {
                    background: #d1fae5;
                    color: #065f46;
                }
                
                .status-cancelled {
                    background: #fee2e2;
                    color: #991b1b;
                }
                
                /* Print Styles */
                @media print {
                    body {
                        background: white;
                        padding: 0;
                    }
                    
                    .invoice-container {
                        box-shadow: none;
                        max-width: 100%;
                    }
                    
                    .invoice-header::after {
                        display: none;
                    }
                    
                    tbody tr:hover {
                        background: transparent;
                    }
                }
                
                @page {
                    margin: 0;
                    size: A4;
                }
            </style>
        </head>
        <body>
            <div class="invoice-container">
                <!-- Header -->
                <div class="invoice-header">
                    <div class="company-info">
                        <div class="company-name"><?php echo esc_html($company_name); ?></div>
                        <div class="company-details">
                            <?php if ($company_phone): ?>
                            📞 <?php echo esc_html($company_phone); ?>
                            <?php endif; ?>
                            <?php if ($company_email): ?>
                            <br>✉️ <?php echo esc_html($company_email); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="invoice-title-section">
                        <div class="invoice-title">
                            <?php esc_html_e('Quote', 'cleaning-price-calculator'); ?>
                            <span class="status-badge status-<?php echo esc_attr($quote->status); ?>">
                                <?php echo esc_html(ucfirst($quote->status)); ?>
                            </span>
                        </div>
                        <div class="invoice-number">
                            <small><?php esc_html_e('Quote No.', 'cleaning-price-calculator'); ?></small>
                            #<?php echo esc_html($quote->id); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Body -->
                <div class="invoice-body">
                    <!-- Customer & Date Info -->
                    <div class="info-section">
                        <div class="info-block">
                            <h3><?php esc_html_e('Bill To', 'cleaning-price-calculator'); ?></h3>
                            <div class="info-content">
                                <p><strong><?php echo esc_html($quote->customer_name); ?></strong></p>
                                <p>✉️ <?php echo esc_html($quote->customer_email); ?></p>
                                <p>📞 <?php echo esc_html($quote->customer_phone); ?></p>
                                <?php if (!empty($quote->customer_address)): ?>
                                <p>📍 <?php echo nl2br(esc_html($quote->customer_address)); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-block">
                            <h3><?php esc_html_e('Quote Details', 'cleaning-price-calculator'); ?></h3>
                            <div class="info-content">
                                <p>
                                    <span class="info-label"><?php esc_html_e('Date:', 'cleaning-price-calculator'); ?></span>
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($quote->created_at))); ?>
                                </p>
                                <p>
                                    <span class="info-label"><?php esc_html_e('Time:', 'cleaning-price-calculator'); ?></span>
                                    <?php echo esc_html(date_i18n(get_option('time_format'), strtotime($quote->created_at))); ?>
                                </p>
                                <p>
                                    <span class="info-label"><?php esc_html_e('Currency:', 'cleaning-price-calculator'); ?></span>
                                    <?php echo esc_html($currency); ?>
                                </p>
                                <p>
                                    <span class="info-label"><?php esc_html_e('Status:', 'cleaning-price-calculator'); ?></span>
                                    <?php echo esc_html(ucfirst($quote->status)); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items Table -->
                    <div class="items-section">
                        <h2 class="section-title"><?php esc_html_e('Service Details', 'cleaning-price-calculator'); ?></h2>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th><?php esc_html_e('Description', 'cleaning-price-calculator'); ?></th>
                                    <th class="text-center" style="width: 80px;"><?php esc_html_e('Qty', 'cleaning-price-calculator'); ?></th>
                                    <th class="text-center" style="width: 100px;"><?php esc_html_e('Area (m²)', 'cleaning-price-calculator'); ?></th>
                                    <th class="text-center" style="width: 100px;"><?php esc_html_e('Rate', 'cleaning-price-calculator'); ?></th>
                                    <th class="text-right" style="width: 120px;"><?php esc_html_e('Amount', 'cleaning-price-calculator'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $item_number = 1;
                                $subtotal = 0;
                                foreach ($quote->items as $item): 
                                    $room_count = isset($item->room_count) ? intval($item->room_count) : 1;
                                    $subtotal += $item->subtotal;
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo esc_html($item_number++); ?></td>
                                    <td>
                                        <div class="item-name"><?php echo esc_html($item->room_name); ?></div>
                                        <small class="calculation">
                                            <?php echo $room_count; ?> × <?php echo number_format($item->area, 2); ?> m² × <?php echo $currency_symbol; ?><?php echo number_format($item->price_per_sqm, 2); ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="count-badge"><?php echo esc_html($room_count); ?></span>
                                    </td>
                                    <td class="text-center"><?php echo esc_html(number_format($item->area, 2)); ?></td>
                                    <td class="text-center"><?php echo $currency_symbol; ?><?php echo esc_html(number_format($item->price_per_sqm, 2)); ?></td>
                                    <td class="text-right"><strong><?php echo $currency_symbol; ?><?php echo esc_html(number_format($item->subtotal, 2)); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Totals -->
                        <div class="totals-section">
                            <div class="total-row subtotal">
                                <span><?php esc_html_e('Subtotal', 'cleaning-price-calculator'); ?></span>
                                <span class="amount"><?php echo $currency_symbol; ?><?php echo esc_html(number_format($subtotal, 2)); ?></span>
                            </div>
                            <div class="total-row grand-total">
                                <span><?php esc_html_e('Total Amount', 'cleaning-price-calculator'); ?></span>
                                <span class="amount"><?php echo $currency_symbol; ?><?php echo esc_html(number_format($quote->total_price, 2)); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($quote->message)): ?>
                    <!-- Customer Message -->
                    <div class="message-section">
                        <h3><?php esc_html_e('Additional Notes', 'cleaning-price-calculator'); ?></h3>
                        <p><?php echo nl2br(esc_html($quote->message)); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Footer -->
                <div class="invoice-footer">
                    <div class="thank-you"><?php esc_html_e('Thank You For Your Business!', 'cleaning-price-calculator'); ?></div>
                    <div class="footer-text">
                        <?php 
                        printf(
                            esc_html__('This quote is valid for 30 days from the date of issue. For questions, please contact us at %s', 'cleaning-price-calculator'),
                            esc_html($company_email)
                        );
                        ?>
                    </div>
                    <div class="footer-text" style="margin-top: 15px; opacity: 0.7;">
                        &copy; <?php echo date('Y'); ?> <?php echo esc_html($company_name); ?>. <?php esc_html_e('All rights reserved.', 'cleaning-price-calculator'); ?>
                    </div>
                </div>
            </div>
            
            <script>
                // Auto-print when page loads
                window.onload = function() {
                    window.print();
                };
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}