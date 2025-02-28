<?php
function get_total_stock_value($user_id) {
    global $conn;
    $asset_id = 12;
    $total_current = 0;

    // Get all stock transactions (both buy and sell)
    $sql = "(SELECT 'buy' as transaction_type, quantity, type, total_price FROM buy WHERE user = ? AND asset = ?)
            UNION ALL
            (SELECT 'sell' as transaction_type, quantity, type, total_price FROM sell WHERE user = ? AND asset = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $user_id, $asset_id, $user_id, $asset_id);
    $stmt->execute();
    $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Calculate net quantities and total prices for each stock
    $net_quantities = [];
    $total_prices = [];
    foreach ($transactions as $transaction) {
        $stock_name = $transaction['type'];
        $quantity = floatval($transaction['quantity']);
        $total_price = floatval($transaction['total_price']);
        
        if (!isset($net_quantities[$stock_name])) {
            $net_quantities[$stock_name] = 0;
            $total_prices[$stock_name] = 0;
        }
        
        if ($transaction['transaction_type'] == 'buy') {
            $net_quantities[$stock_name] += $quantity;
            $total_prices[$stock_name] += $total_price;
        } else {
            $net_quantities[$stock_name] -= $quantity;
        }
    }

    // Calculate total current value
    foreach ($net_quantities as $stock => $net_quantity) {
        if ($net_quantity > 0) {
            $current_price = get_current_price("Stock",$stock);
            if ($current_price == 0) {
                // Calculate unit price if current price is 0
                $unit_price = $total_prices[$stock] / $net_quantity;
                $current_price = $unit_price;
            }
            $total_current += $current_price * $net_quantity;
        }
    }

    return $total_current;
}

function get_stock_count($user_id) {
    global $conn;
    $asset_id = 12; // Assuming this is the asset ID for stocks

    // Get all stock transactions (both buy and sell)
    $sql = "(SELECT 'buy' as transaction_type, quantity, type FROM buy WHERE user = ? AND asset = ?)
            UNION ALL
            (SELECT 'sell' as transaction_type, quantity, type FROM sell WHERE user = ? AND asset = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $user_id, $asset_id, $user_id, $asset_id);
    $stmt->execute();
    $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Calculate net quantities for each stock
    $net_quantities = [];
    foreach ($transactions as $transaction) {
        $stock_name = $transaction['type'];
        $quantity = floatval($transaction['quantity']);
        
        if (!isset($net_quantities[$stock_name])) {
            $net_quantities[$stock_name] = 0;
        }
        
        $net_quantities[$stock_name] += ($transaction['transaction_type'] == 'buy' ? $quantity : -$quantity);
    }

    // Count stocks with positive net quantity
    $unique_remaining_stocks = 0;
    foreach ($net_quantities as $net_quantity) {
        if ($net_quantity > 0) {
            $unique_remaining_stocks++;
        }
    }

    return $unique_remaining_stocks;
}

function get_stock_array($user_id) {
    global $conn;
    $asset_id = 12;

    // Get all stock transactions (both buy and sell)
    $sql = "(SELECT 'buy' as transaction_type, id, buy_date as date, quantity, total_price, note, type FROM buy WHERE user = ? AND asset = ?)
            UNION ALL
            (SELECT 'sell' as transaction_type, id, sell_date as date, quantity, total_price, note, type FROM sell WHERE user = ? AND asset = ?)
            ORDER BY date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $user_id, $asset_id, $user_id, $asset_id);
    $stmt->execute();
    $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get unique stocks
    $unique_stocks = array_unique(array_column($transactions, 'type'));

    // Fetch current prices for all unique stocks
    $current_prices = [];
    foreach ($unique_stocks as $stock) {
        $current_prices[$stock] = get_current_price("Stock",$stock);
    }

    // Create stocks array
    $stocks = [];

    foreach ($transactions as $transaction) {
        $quantity = floatval($transaction['quantity']);
        $total_price = floatval($transaction['total_price']);
        $unit_price = $quantity > 0 ? $total_price / $quantity : 0;
        $stock_name = $transaction['type'];
        $current_price = $current_prices[$stock_name];
        $current_price = $current_price == 0 ? $unit_price : $current_price;
        $current_value = $current_price * $quantity;
        $benefit = $current_price == 0 ? 0 : ($transaction['transaction_type'] == 'buy' ? $current_value - $total_price : $total_price - $current_value);
        $card_class = $transaction['type'] == 'buy' ? 'card-buy' : 'card-sell';

        // Add the processed information to the $stocks array
        $stocks[] = [
            'stock_name' => $stock_name,
            'quantity' => $quantity,
            'total_price' => $total_price,
            'unit_price' => $unit_price,
            'current_price' => $current_price,
            'current_value' => $current_value,
            'benefit' => $benefit,
            'type' => $transaction['transaction_type'],
            'note' => $transaction['note'],
            'date' => $transaction['date'],
            'id' => $transaction['id'],
            'card_class' => $card_class
        ];
    }

    return $stocks;
}

?>