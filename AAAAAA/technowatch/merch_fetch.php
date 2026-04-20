<?php
// technowatch/merch_fetch.php - AJAX Data Fetch Endpoint (Returns JSON)

// Set content type to JSON
header('Content-Type: application/json');

// Define the default order form link
$order_form_link = "https://docs.google.com/forms/d/e/1FAIpQLSctwqm8bkV9t89R7HTjt86hH7sPmCRF5XeTuwceffdbAPBqgw/viewform";

// Include your standalone DB connection file
// Adjust path if necessary (assuming it's in a subdirectory like 'admin/includes')
include 'admin/includes/db_connect.php'; 

// Define allowed categories and default values
$categories = ['tshirts', 'pins', 'lanyards', 'caps', 'others'];
$merch_by_category = [];
$first_active_tab = '';

// Initialize data structure
foreach ($categories as $cat) {
    $merch_by_category[$cat] = [];
}

try {
    // Fetch all merch data, including 'is_in_stock'
    $query = "SELECT id, name, category, price, image_path, is_in_stock FROM merch ORDER BY category, id DESC";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cat = strtolower($row['category']);
            // Only store merch if the category is recognized
            if (in_array($cat, $categories)) {
                $merch_by_category[$cat][] = $row;
            }
        }
    }
} catch (Exception $e) {
    // Handle database connection or query errors
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
} finally {
    if (isset($conn)) $conn->close();
}

// Start output buffering to capture the HTML structure
ob_start();
?>

<div class="merch-tabs" id="merch-tabs">
    <?php foreach ($categories as $cat): 
        // Only display tabs that have products
        if (!empty($merch_by_category[$cat])): 
            if (empty($first_active_tab)) {
                $first_active_tab = $cat; // Set the first active tab to the first category with items
            }
    ?>
        <button class="tab-button" data-tab="<?php echo htmlspecialchars($cat); ?>">
            <?php echo ucwords($cat); ?>
        </button>
    <?php endif; endforeach; ?>
</div>

<div class="merch-content">
    <?php if (empty($first_active_tab)): // If no merch found at all ?>
        <p class="no-merch-message">No merch is currently available.</p>
    <?php else: ?>
        <?php foreach ($categories as $cat): ?>
            <div id="<?php echo htmlspecialchars($cat); ?>" class="tab-content">
                <?php if (!empty($merch_by_category[$cat])): ?>
                    <div class="product-grid"> 
                        <?php foreach ($merch_by_category[$cat] as $item): 
                            
                            // --- Stock Logic ---
                            $is_in_stock = (int)$item['is_in_stock'] === 1;
                            $stock_status_class = $is_in_stock ? 'available' : 'unavailable';
                            $stock_status_text = $is_in_stock ? 'In Stock' : 'Out of Stock';
                            $button_disabled = $is_in_stock ? '' : 'disabled';
                            $order_button_text = $is_in_stock ? 'Order Now' : 'Sold Out';
                            $stock_icon_class = $is_in_stock ? 'fa-check-circle' : 'fa-times-circle';
                            // -------------------

                        ?>
                            <div class="product-card <?php echo $stock_status_class; ?>" data-id="<?php echo $item['id']; ?>"> 
                                <div class="product-image-wrapper"> 
                                    <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                                        alt="<?php echo htmlspecialchars($item['name']); ?>"
                                        onerror="this.onerror=null; this.src='assets/imgs/placeholder.png';">
                                </div>
                                <div class="product-details"> 
                                    <h4 class="product-name"><?php echo htmlspecialchars($item['name']); ?></h4> 
                                    <p class="product-price">₱<?php echo number_format($item['price'], 2); ?></p> 
                                    
                                    <span class="product-status-label <?php echo $stock_status_class; ?>">
                                        <i class="fas <?php echo $stock_icon_class; ?>"></i> <?php echo $stock_status_text; ?>
                                    </span>

                                    <a href="<?php echo $order_form_link; ?>" class="btn btn-secondary <?php echo $button_disabled; ?>" <?php echo $button_disabled; ?>>
                                        <?php echo $order_button_text; ?>
                                    </a>
                                    
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-merch-message">No <?php echo htmlspecialchars($cat); ?> are currently listed.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
// Capture the generated HTML
$html_output = ob_get_clean();

// Prepare the JSON response
$response = [
    'success' => true,
    'html' => $html_output,
    'first_active_tab' => $first_active_tab,
];

// Output the JSON
echo json_encode($response);
?>