<?php
require_once 'asset/lib/config.php';
require_once 'asset/lib/stock.php';
check_login();

$user_id = $_SESSION['user_id'];

// Get user's assets and their balances
$sql = "SELECT a.id, a.symbol, a.name, a.unit,a.color, 
               b.balance as total_quantity, b.average as average
        FROM asset a
        INNER JOIN balance b ON b.asset = a.id
        WHERE b.user = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total_portfolio_value = 0;
$assets = [];

while ($row = $result->fetch_assoc()) {
    // stock price 
    if($row['symbol'] == "Stock"){
        $current_value = get_total_stock_value($user_id);
        $buy_value = $row['average'] * $row['total_quantity'];
        $profit_loss_percentage = (($current_value - $buy_value) / $buy_value) * 100;
        $quantity = get_stock_count($user_id);
    }
    else if($row['symbol'] == "Car"){
        $current_price = get_current_price("Car","MVM_X22_1400");
        $current_value = $current_price * $row['total_quantity'];
        $profit_loss_percentage = (($current_price - $row['average']) / $row['average']) * 100;
        $quantity = $row['total_quantity'];
    }
    else if($row['symbol'] == "House"){
        $current_price = $row['average'];
        $current_value = $current_price * $row['total_quantity'];
        $profit_loss_percentage = 0;
        $quantity = $row['total_quantity'];
    }
    // other assets
    else{
        $current_price = get_current_price($row['symbol']);
        $current_value = $current_price * $row['total_quantity'];
        $profit_loss_percentage = (($current_price - $row['average']) / $row['average']) * 100;
        $quantity = $row['total_quantity'];
    }

    $total_portfolio_value += $current_value;
    $assets[] = [
        'id' => $row['id'],
        'symbol' => $row['symbol'],
        'name' => $row['name'],
        'unit' => $row['unit'],
        'color' => $row['color'],
        'quantity' => $quantity,
        'profit_lost_percentage' => number_format($profit_loss_percentage, 2),
        'current_value' => $current_value
    ];
}

usort($assets, function ($a, $b) {
    return $b['current_value'] <=> $a['current_value'];
});

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت دارایی</title>
    <link rel="stylesheet" href="asset/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="price-list-btn"><a class="btn btn-primary" href="page/price-list.php"><i class="fas fa-chart-line"></i> قیمت روز </a></div>
            <nav class="top-nav">
                <a href="page/change_password.php" class="btn btn-secondary"><i class="fas fa-key"></i> تغییر رمز </a>
                <a href="page/logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> خروج </a>
            </nav>
        </header>
        <section class="portfolio">
            <div class="portfolio-summary">
                <div class="portfolio-value">
                    <p class="portfolio-title">ارزش روز دارایی ها</p>
                    <p class="value highlight"><?php echo format_price($total_portfolio_value); ?></p>
                </div>
                <div class="prtfolio-buttons">
                    <a href="page/transaction.php?action=add&type=buy&asset=Gold18" class="btn btn-add"><i class="fas fa-plus"></i></a>
                    <a class="btn btn-add btn-reload" onclick="window.location.reload();"><i class="fas fa-sync-alt"></i></a>
                </div>
            </div>
            <div id="chartContainer">
                <canvas id="assetChart"></canvas>
            </div>
        </section>
        <h1>دارایی های من</h1>
        <main class="asset-grid">
           <?php foreach ($assets as $asset): ?>
                <a href="<?php echo "page/" . get_detail_page($asset["id"]); ?>" 
                class="asset-card" style="box-shadow: 0 2px 2px <?php echo htmlspecialchars($asset['color'], ENT_QUOTES, 'UTF-8'); ?> !important;">
                    <div class="asset-info">
                        <div class="row row1">
                            <div class="asset-title-icon">
                                <img class="asset-icon" alt="Asset Icon" src=<?php echo "asset/img/icon/" . $asset['symbol'] . ".png"; ?>>
                                <p class="asset-title" style="color: <?php echo htmlspecialchars($asset['color'], ENT_QUOTES, 'UTF-8'); ?> !important;">
                                    <?php echo htmlspecialchars($asset['name'], ENT_QUOTES, 'UTF-8'); ?></p>                            </div>
                            <p class="asset-value numeric"><?php echo format_price($asset['current_value']); ?></p>
                        </div>
                        <div class="row row2">
                            <p class="asset-quantity"><?php echo number_format($asset['quantity'], 3); ?> <?php echo htmlspecialchars($asset['unit']); ?></p>
                            <p class="<?php echo ($asset['profit_lost_percentage'] >= 0) ? 'profit' : 'loss'; ?>">
                                <?php echo  ' % ' . ($asset['profit_lost_percentage'] >= 0 ? $asset['profit_lost_percentage'] : -$asset['profit_lost_percentage']); ?></p>
                    </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </main>
    </div>
</body>
<script id="labels" type="application/json"><?php echo json_encode(array_column($assets, 'name')); ?></script>
<script id="values" type="application/json"><?php echo json_encode(array_column($assets, 'current_value')); ?></script>
<script id="colors" type="application/json"><?php echo json_encode(array_column($assets, 'color')); ?></script>
<script id="percentages" type="application/json"><?php echo json_encode(array_column($assets, 'profit_lost_percentage')); ?></script>
<script src="asset/js/chart.js"></script>
<script src="asset/js/chartjs-plugin-datalabels@2.0.0.js"></script>
<script src="asset/js/assetChart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeAssetChart();
    });
</script>
</html>
