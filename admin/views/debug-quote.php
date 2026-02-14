<?php
/**
 * Debug page for quotes
 *
 * @package    Cleaning_Price_Calculator
 * @subpackage Cleaning_Price_Calculator/admin/views
 */

if (!defined('WPINC')) {
    die;
}

global $wpdb;

$quotes_table = $wpdb->prefix . 'cpc_quotes';
$items_table = $wpdb->prefix . 'cpc_quote_items';

// Check if tables exist
$quotes_exists = $wpdb->get_var("SHOW TABLES LIKE '$quotes_table'") === $quotes_table;
$items_exists = $wpdb->get_var("SHOW TABLES LIKE '$items_table'") === $items_table;

// Get table structures
$quotes_structure = $wpdb->get_results("DESCRIBE $quotes_table");
$items_structure = $wpdb->get_results("DESCRIBE $items_table");

// Get all quotes
$all_quotes = $wpdb->get_results("SELECT * FROM $quotes_table ORDER BY id DESC LIMIT 10");

// Get all items
$all_items = $wpdb->get_results("SELECT * FROM $items_table ORDER BY quote_id DESC, id ASC LIMIT 50");

?>

<div class="wrap">
    <h1>Cleaning Price Calculator - Database Debug</h1>
    
    <!-- Table Status -->
    <div class="cpc-card" style="margin: 20px 0; padding: 20px; background: white; border: 1px solid #ccc;">
        <h2>Table Status</h2>
        <p><strong>Quotes Table (<?php echo esc_html($quotes_table); ?>):</strong> 
            <?php echo $quotes_exists ? '<span style="color: green;">✓ EXISTS</span>' : '<span style="color: red;">✗ NOT FOUND</span>'; ?>
        </p>
        <p><strong>Items Table (<?php echo esc_html($items_table); ?>):</strong> 
            <?php echo $items_exists ? '<span style="color: green;">✓ EXISTS</span>' : '<span style="color: red;">✗ NOT FOUND</span>'; ?>
        </p>
    </div>
    
    <!-- Quotes Table Structure -->
    <div class="cpc-card" style="margin: 20px 0; padding: 20px; background: white; border: 1px solid #ccc;">
        <h2>Quotes Table Structure</h2>
        <table class="widefat" style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Null</th>
                    <th>Key</th>
                    <th>Default</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quotes_structure as $column): ?>
                <tr>
                    <td><?php echo esc_html($column->Field); ?></td>
                    <td><?php echo esc_html($column->Type); ?></td>
                    <td><?php echo esc_html($column->Null); ?></td>
                    <td><?php echo esc_html($column->Key); ?></td>
                    <td><?php echo esc_html($column->Default); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Items Table Structure -->
    <div class="cpc-card" style="margin: 20px 0; padding: 20px; background: white; border: 1px solid #ccc;">
        <h2>Items Table Structure</h2>
        <table class="widefat" style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Null</th>
                    <th>Key</th>
                    <th>Default</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items_structure as $column): ?>
                <tr>
                    <td><?php echo esc_html($column->Field); ?></td>
                    <td><?php echo esc_html($column->Type); ?></td>
                    <td><?php echo esc_html($column->Null); ?></td>
                    <td><?php echo esc_html($column->Key); ?></td>
                    <td><?php echo esc_html($column->Default); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Recent Quotes -->
    <div class="cpc-card" style="margin: 20px 0; padding: 20px; background: white; border: 1px solid #ccc;">
        <h2>Recent Quotes (Last 10)</h2>
        <?php if (!empty($all_quotes)): ?>
        <table class="widefat striped" style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer Name</th>
                    <th>Email</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_quotes as $quote): ?>
                <tr>
                    <td><strong><?php echo esc_html($quote->id); ?></strong></td>
                    <td><?php echo esc_html($quote->customer_name); ?></td>
                    <td><?php echo esc_html($quote->customer_email); ?></td>
                    <td><?php echo esc_html($quote->total_price); ?> <?php echo esc_html($quote->currency); ?></td>
                    <td><?php echo esc_html($quote->status); ?></td>
                    <td><?php echo esc_html($quote->created_at); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No quotes found in database.</p>
        <?php endif; ?>
    </div>
    
    <!-- All Items -->
    <div class="cpc-card" style="margin: 20px 0; padding: 20px; background: white; border: 1px solid #ccc;">
        <h2>All Quote Items (Last 50)</h2>
        <?php if (!empty($all_items)): ?>
        <table class="widefat striped" style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Quote ID</th>
                    <th>Room Name</th>
                    <th>Room Count</th>
                    <th>Area</th>
                    <th>Price/m²</th>
                    <th>Subtotal</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_items as $item): ?>
                <tr>
                    <td><?php echo esc_html($item->id); ?></td>
                    <td><strong><?php echo esc_html($item->quote_id); ?></strong></td>
                    <td><?php echo esc_html($item->room_name); ?></td>
                    <td><?php echo isset($item->room_count) ? esc_html($item->room_count) : 'N/A'; ?></td>
                    <td><?php echo esc_html($item->area); ?></td>
                    <td><?php echo esc_html($item->price_per_sqm); ?></td>
                    <td><?php echo esc_html($item->subtotal); ?></td>
                    <td><?php echo esc_html($item->created_at); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color: red; font-weight: bold;">⚠️ NO ITEMS FOUND IN DATABASE! This is the problem!</p>
        <p>Quotes exist but have no items. This means items are not being saved during quote submission.</p>
        <?php endif; ?>
    </div>
    
    <!-- Test Query -->
    <div class="cpc-card" style="margin: 20px 0; padding: 20px; background: white; border: 1px solid #ccc;">
        <h2>Test Quote Item Lookup</h2>
        <?php if (!empty($all_quotes)): 
            $test_quote_id = $all_quotes[0]->id;
            $test_items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $items_table WHERE quote_id = %d",
                $test_quote_id
            ));
        ?>
        <p><strong>Testing Quote ID:</strong> <?php echo esc_html($test_quote_id); ?></p>
        <p><strong>Query:</strong> <code>SELECT * FROM <?php echo esc_html($items_table); ?> WHERE quote_id = <?php echo esc_html($test_quote_id); ?></code></p>
        <p><strong>Items Found:</strong> <?php echo count($test_items); ?></p>
        <?php if (!empty($test_items)): ?>
            <pre style="background: #f5f5f5; padding: 10px; overflow: auto;"><?php print_r($test_items); ?></pre>
        <?php else: ?>
            <p style="color: red;">No items found for this quote!</p>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Raw SQL Check -->
    <div class="cpc-card" style="margin: 20px 0; padding: 20px; background: white; border: 1px solid #ccc;">
        <h2>Item Count Per Quote</h2>
        <?php 
        $item_counts = $wpdb->get_results("
            SELECT quote_id, COUNT(*) as item_count 
            FROM $items_table 
            GROUP BY quote_id 
            ORDER BY quote_id DESC
        ");
        ?>
        <?php if (!empty($item_counts)): ?>
        <table class="widefat striped" style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>Quote ID</th>
                    <th>Number of Items</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($item_counts as $count): ?>
                <tr>
                    <td><?php echo esc_html($count->quote_id); ?></td>
                    <td><?php echo esc_html($count->item_count); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color: red; font-weight: bold;">⚠️ NO ITEMS IN DATABASE AT ALL!</p>
        <?php endif; ?>
    </div>
</div>