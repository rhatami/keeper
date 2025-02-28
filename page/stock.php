<?php
require_once '../asset/lib/config.php';
require_once '../asset/lib/stock.php';
require_once '../asset/lib/jdf.php';

check_login();

$user_id = $_SESSION['user_id'];
$asset_id = 12; // stock asset id

// Get stock asset details
$sql = "SELECT * FROM asset WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$asset = $stmt->get_result()->fetch_assoc();

if (!$asset) {
    header("Location: ../index.php");
    exit();
}

// Get stock balance details
$sql = "SELECT * FROM balance WHERE user = ? AND asset = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id , $asset_id);
$stmt->execute();
$balance = $stmt->get_result()->fetch_assoc();

if (!$balance) {
    header("Location: ../index.php");
    exit();
}

$total_buy = $balance['balance'] * $balance['average'];
$stock_count = get_stock_count($user_id);
$total_current = get_total_stock_value($user_id);
$stocks = get_stock_array($user_id);
$total_loss_benefit = $total_current - $total_buy;

// Group stocks by stock name and calculate summaries
$grouped_stocks = [];
foreach ($stocks as $stock) {
    $stock_name = $stock['stock_name'];
    
    // Initialize group if not exists
    if (!isset($grouped_stocks[$stock_name])) {
        $grouped_stocks[$stock_name] = [
            'transactions' => [],
            'total_quantity' => 0,
            'total_buy_amount' => 0,
            'total_sale_amount' => 0,
            'total_buy_quantity' => 0,
            'total_sale_quantity' => 0,
            'current_price' => $stock['current_price'],
            'average_price' => 0,
            'current_value' => 0,
            'total_investment' => 0,
            'total_benefit' => 0,
            'benefit_percentage' => 0
        ];
    }
    
    // Add transaction and update totals
    $grouped_stocks[$stock_name]['transactions'][] = $stock;
    
    // Update buy/sale amounts
    if ($stock['type'] == 'buy') {
        $grouped_stocks[$stock_name]['total_buy_amount'] += $stock['total_price'];
        $grouped_stocks[$stock_name]['total_buy_quantity'] += $stock['quantity'];
    } else {
        $grouped_stocks[$stock_name]['total_sale_amount'] += $stock['total_price'];
        $grouped_stocks[$stock_name]['total_sale_quantity'] += $stock['quantity'];
    }
    
    // Calculate running totals
    $group = &$grouped_stocks[$stock_name];
    $group['total_quantity'] = $group['total_buy_quantity'] - $group['total_sale_quantity'];
    
    // Calculate averages and benefits
    $group['average_price'] = $group['total_buy_quantity'] > 0 
        ? $group['total_buy_amount'] / $group['total_buy_quantity'] 
        : 0;
    
    $group['current_value'] = $group['total_quantity'] * $group['current_price'];
    $group['total_investment'] = $group['total_quantity'] * $group['average_price'];
    $group['total_benefit'] = $group['current_value'] - $group['total_investment'];
    $group['benefit_percentage'] = $group['total_investment'] > 0 
        ? ($group['total_benefit'] / $group['total_investment']) * 100 
        : 0;
}

// Sort the $grouped_stocks array by 'current_value' in descending order
uasort($grouped_stocks, function($a, $b) {
    // Compare 'current_value' of $a and $b
    if ($a['current_value'] == $b['current_value']) {
        return 0; // If the values are equal, leave them unchanged
    }
    return ($a['current_value'] > $b['current_value']) ? -1 : 1; // Sort descending
});

$seq = 0;
foreach ($grouped_stocks as &$group) {
    $group['id'] = $seq++;
}
unset($group); // unset the reference after use

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($asset['name']); ?></title>
    <link rel="stylesheet" href="../asset/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <div class="detail-container">
        <header class="detail-header">
            <h2><?php echo htmlspecialchars($asset['name']); ?></h2>
            <a href="../index.php" class="btn btn-back"><i class="fas fa-arrow-left"></i></a>
        </header>
        <section class="portfolio">
            <div class="stock-summary">
                <div class="column column1">
                    <img class="asset-icon-large" alt="Asset Icon" src=<?php echo "../asset/img/icon/" . $asset['symbol'] . ".png"; ?>>
                     <p class="quantity"><?php echo $stock_count . ' ' . htmlspecialchars($asset['unit']); ?></p>
                </div>
                <div class="column column2">
                    <div class="average-value">
                        <label class="average-label">مجموع خرید: </label>
                        <span class="current-average-value"><?php echo format_price($total_buy); ?></span>
                    </div>
                    <div class="total-value">
                        <label class="current-label">ارزش فعلی: </label>
                        <span class="current-total-value"><?php echo format_price($total_current); ?></span>
                    </div>
                    <div class="loss-profit <?php echo ($total_loss_benefit >= 0) ? 'profit' : 'loss'; ?>">
                         <span class="loss-profit-value"><?php echo format_price($total_loss_benefit >= 0 ? $total_loss_benefit : -$total_loss_benefit); ?></span>
                    </div>
                </div>
            </div>
            <div id="chartContainer">
                <canvas id="assetChart"></canvas>
            </div>
        </section>
        <nav class="action-buttons">
            <a href="transaction.php?type=buy&asset=<?php echo urlencode($asset['symbol']); ?>" class="btn btn-buy">خرید</a>
            <a href="transaction.php?type=sell&asset=<?php echo urlencode($asset['symbol']); ?>" class="btn btn-sell">فروش</a>
        </nav>
  
        <h2 class="transactions-label">سهم های من</h2>
        <p class="transactions-description">برای مشاهده تراکنش های هر سهم، روی آن کلیک کنید.</p>
        <main class="transactions">
        <?php foreach ($grouped_stocks as $stock_name => $group): ?>
            <?php if (!isset($group['total_quantity']) || $group['total_quantity'] <= 0) continue; ?>
                  <div class="stock-group">
                    <div class="transaction-card">
                        <div class="card-body" onclick="toggleTransactions('<?php echo $group['id']; ?>')">
                            <div class="row row1">
                                <div>
                                    <i class="fas fa-coins"></i>
                                    <span class="stock-name"><?php echo htmlspecialchars($stock_name); ?></span>
                                </div>
                                <p>
                                    <i class="fas fa-chart-line"></i><span class="total-profit-loss <?php echo $group['total_benefit'] >= 0 ? 'profit' : 'loss'; ?>">
                                    <?php echo $group['benefit_percentage'] == 0 ? '0' :  number_format($group['benefit_percentage'] > 0 ? $group['benefit_percentage'] : -$group['benefit_percentage'], 2) . ' % '; ?></span>
                                </p>
                            </div>
                            <div class="row row2">
                                <p><i class="fas fa-box"></i><span class="total-quantity"><?php echo htmlspecialchars(number_format($group['total_quantity'])) . ' ' . htmlspecialchars($asset['unit']); ?></span></p>
                                <div>
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span class="stock-total-current"><?php echo htmlspecialchars(format_price($group['current_value'])); ?></span>
                                </div>
                            </div>
                            <div class="row row3">
                                <div class="group-average-price"><label>میانگین خرید: </label><?php echo format_price($group['average_price'], 0); ?></div>
                                <div><span class="group-current-price"><label>قیمت روز: </label><?php echo format_price($group['current_price'], 0); ?></span></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="transactions-container" id="transactions-<?php echo $group['id']; ?>">
                        <?php foreach ($group['transactions'] as $stock): ?>
                            <div class="transaction-card <?php echo $stock['card_class']; ?>">
                                <div class="card-body">
                                    <div class="row row1">
                                        <div><i class="fas fa-coins"></i>
                                            <span class="buy-sale"><?php echo $stock['type'] == "buy" ? "خرید " : " فروش "; echo htmlspecialchars($stock['quantity']) . ' ' . htmlspecialchars($asset['unit']) . ' ' . htmlspecialchars($stock['stock_name']); ?></span>
                                        </div>
                                    </div>
                                    <div class="row row2">
                                        <p><i class="fas fa-money-bill-wave"></i><span class="total-buy-price"><?php echo format_price($stock['total_price']); ?></span></p>
                                        <p><i class="fas fa-chart-line"></i><span class="total-profit-loss <?php echo $stock['benefit'] >= 0 ? 'profit' : 'loss'; ?>">
                                            <?php echo $stock['benefit'] == 0 ? '0' : format_price($stock['benefit']); ?>
                                        </span></p>
                                    </div>
                                    <div class="row row3">
                                        <p><i class="fas fa-dollar-sign"></i><span class="each-unit-price"><?php echo "هر " . htmlspecialchars($asset['unit']) . " " . format_price($stock['total_price'] / $stock['quantity'],0); ?></span>
                                    </div>
                                    <?php if ($stock['note']): ?>
                                        <div class="row row4">
                                           <p class="note"><i class="fas fa-sticky-note"></i><?php echo htmlspecialchars($stock['note']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <a href="transaction.php?id=<?php echo $stock['id']; ?>&type=<?php echo $stock['type']; ?>" class="btn btn-edit"><i class="fas fa-edit"></i></a>
                                    <span class="transaction-date">
                                        <?php
                                        $gregorian_date = $stock['date'];
                                        $jalali_date = jdate('l d F Y', strtotime($gregorian_date));
                                        echo htmlspecialchars($jalali_date);
                                        ?>
                                    </span> 
                                    <button type="button" class="btn btn-delete delete-transaction" data-type="<?php echo $stock['type']; ?>" data-id="<?php echo $stock['id']; ?>"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </main>
    </div>
    <script src="../asset/js/stock.js"></script>
    <script id="labels" type="application/json"><?php echo json_encode(array_keys($grouped_stocks)); ?></script>
    <script id="values" type="application/json"><?php echo json_encode(array_column($grouped_stocks, 'current_value')); ?></script>
    <script id="percentages" type="application/json">
        <?php $formatted_percentages = array_map(function($percentage) {return number_format($percentage, 2);}, array_column($grouped_stocks, 'benefit_percentage'));
        echo json_encode($formatted_percentages);?>
    </script>
    <script id="colors" type="application/json">
        <?php
            // color palette with visually appealing colors
            $color_palette = [
            '#5D8AA8', // Cadet Blue
            '#A8B9C1', // Grayish Blue
            '#8E9A9D', // Grayish Green
            '#A4B8B2', // Pale Teal
            '#C1D6D2', // Light Grayish Cyan
            '#B8B8B1', // Olive Green
            '#D0D0B1', // Beige
            '#8F9779', // Moss Green
            '#A9C6C4', // Soft Cyan
            '#D1D8D0'  // Light Gray
            ];
            
            // If there are more stocks than colors, repeat the color palette
            $colors = [];
            foreach ($grouped_stocks as $stock_name => $stock_data) {
                // Use modulo to repeat colors if there are more stocks than colors
                $colors[] = $color_palette[count($colors) % count($color_palette)];
            }
            echo json_encode($colors); 
        ?>
    </script>
    <script src="../asset/js/chart.js"></script>
    <script src="../asset/js/chartjs-plugin-datalabels@2.0.0.js"></script>
    <script src="../asset/js/assetChart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeAssetChart();
        });
    </script>
</body>
</html>