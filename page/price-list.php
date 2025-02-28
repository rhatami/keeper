<?php
require_once '../asset/lib/config.php';
require_once '../asset/lib/stock.php';

check_login();

// Get all assets
$user_id = $_SESSION['user_id'];
$sql = "SELECT asset.*, COALESCE(balance.balance, 0) AS balance FROM asset LEFT JOIN balance ON asset.id = balance.asset AND balance.user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$assets = [];

while ($row = $result->fetch_assoc()) {
    // stock price
    if($row['symbol'] == "Stock"){
        $current_price = get_total_stock_value($user_id);
        $balance = $current_price;
    }
    else{
        $current_price = get_current_price($row['symbol']);
        $balance = $row['balance'] * $current_price;
    }
    $assets[] = [
        'id' => $row['id'],
        'symbol' => $row['symbol'],
        'name' => $row['name'],
        'color' => $row['color'],
        'user_balance' => $balance,
        'current_price' => $current_price
    ];
}

// sort the array by user_balance (descending) then current_price (descending)
usort($assets, function($a, $b) {
    // Compare user_balance first
    $balanceComparison = $b['user_balance'] - $a['user_balance'];

    // If user_balance is the same, compare current_price
    if ($balanceComparison == 0) {
        return $b['current_price'] - $a['current_price'];
    }

    return $balanceComparison;
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قیمت روز دارایی ها</title>
    <link rel="stylesheet" href="../asset/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <div class="price-container">
        <header class="price-list-header">
            <h1>قیمت روز دارایی ها</h1>
            <a href="<?php echo "../index.php"?>" class="btn btn-back"><i class="fas fa-arrow-left"></i></a>
        </header>
        
        <main class="asset-grid">
            <?php foreach ($assets as $asset): ?>
                <a href="<?php echo get_detail_page($asset['id']); ?>" 
                class="asset-card" style="box-shadow: 0 2px 2px  <?php echo htmlspecialchars($asset['color'], ENT_QUOTES, 'UTF-8'); ?> !important;">
                    <div class="asset-info">
                        <div class="row row1">
                            <div class="asset-title-icon">
                                <img class="asset-icon" alt="Asset Icon" src=<?php echo "../asset/img/icon/" . $asset['symbol'] . ".png"; ?>>
                                <p class="asset-title" style="color: <?php echo htmlspecialchars($asset['color'], ENT_QUOTES, 'UTF-8'); ?> !important;">
                                    <?php echo htmlspecialchars($asset['name'], ENT_QUOTES, 'UTF-8'); ?></p>                            </div>
                            <p class="asset-value numeric"><?php echo format_price($asset['current_price']); ?></p>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </main>
    </div>
</body>
</html>
