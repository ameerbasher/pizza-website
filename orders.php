<?php
session_start();
$user_is_worker = $_SESSION['is_worker'] ?? 'no';

if (!isset($_SESSION['user_id'])) {
    die("Access denied. You must be logged in.");
}

$conn = mysqli_connect("localhost", "root", "", "pizza");
if (mysqli_connect_errno()) {
    die("Failed to connect to MySQL: " . mysqli_connect_error());
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ready_id'])) {
    $ready_id = (int)$_POST['ready_id'];
    $fetch_sql = "SELECT * FROM order_product WHERE product_order_id = $ready_id AND user_id = $user_id LIMIT 1";
    $fetch_result = mysqli_query($conn, $fetch_sql);
    if ($fetch_result && mysqli_num_rows($fetch_result) > 0) {
        $row = mysqli_fetch_assoc($fetch_result);
        $insert_sql = "INSERT INTO ready_orders (
            product_order_order_id,
            product_order_product_id,
            product_order_amount,
            user_id
        ) VALUES (
            {$row['product_order_order_id']},
            {$row['product_order_product_id']},
            {$row['product_order_amount']},
            $user_id
        )";
        mysqli_query($conn, $insert_sql);
        mysqli_query($conn, "DELETE FROM order_product WHERE product_order_id = $ready_id AND user_id = $user_id");
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ready_id'])) {
    $delete_ready_id = (int)$_POST['delete_ready_id'];
    mysqli_query($conn, "DELETE FROM ready_orders WHERE product_order_id = $delete_ready_id AND user_id = $user_id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$sql = "
    SELECT op.product_order_id, p.product_name, p.product_price, op.product_order_amount
    FROM order_product op
    JOIN product p ON op.product_order_product_id = p.product_id
    WHERE op.user_id = $user_id
    ORDER BY p.product_name
";
$result = mysqli_query($conn, $sql);

$ready_sql = "
    SELECT ro.*, p.product_name, p.product_price
    FROM ready_orders ro
    JOIN product p ON ro.product_order_product_id = p.product_id
    WHERE ro.user_id = $user_id
    ORDER BY ro.product_order_id DESC
";
$ready_result = mysqli_query($conn, $ready_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Orders</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma;
            background: linear-gradient(135deg, #fff6e6, #ffc680);
            color: #4b2e00;
            padding: 30px;
            min-height: 100vh;
        }
        .nav-bar {
            background-color: #ffb84d;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            font-size: 1.1rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .nav-bar a {
            color: #4b2e00;
            text-decoration: none;
            margin: 0 20px;
            padding: 5px 10px;
            border-radius: 6px;
        }
        .nav-bar a:hover {
            background-color: #ffe0b2;
        }
        table {
            border-collapse: collapse;
            width: 80%;
            max-width: 900px;
            margin: 30px auto;
            background: #fff9f0;
            box-shadow: 0 12px 30px rgba(255, 132, 0, 0.3);
            border-radius: 20px;
            overflow: hidden;
            font-weight: 600;
            color: #7a4900;
        }
        caption {
            font-size: 2rem;
            font-weight: bold;
            color: #d35400;
            margin-bottom: 20px;
        }
        th, td {
            padding: 15px 20px;
            text-align: center;
            border-bottom: 1px solid #ffb84d;
        }
        th {
            background-color: #ffb84d;
            color: #4b2e00;
        }
        tr:hover {
            background-color: #fff1d6;
        }
        .total-row td {
            font-weight: bold;
            background-color: #ffdb9a;
            color: #d35400;
        }
        button.ready-btn {
            background-color: #d35400;
            border: none;
            color: white;
            padding: 8px 14px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        button.ready-btn:hover {
            background-color: #a03b00;
        }
    </style>
</head>
<body>

<!-- ✅ Navigation Bar -->
<div class="nav-bar">
    <a href="menu.php">🏠 Home</a>
    <a href="orders.php">📦 Orders</a>
</div>

<!-- ✅ Active Orders -->
<form method="POST">
    <table>
        <caption>Orders Summary</caption>
        <thead>
            <tr>
                <th>Product</th>
                <th>Price (₪)</th>
                <th>Quantity</th>
                <th>Total (₪)</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $grand_total = 0;
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $sub_total = $row['product_price'] * $row['product_order_amount'];
                    $grand_total += $sub_total;
                    echo "<tr>
                        <td>{$row['product_name']}</td>
                        <td>" . number_format($row['product_price'], 2) . "</td>
                        <td>{$row['product_order_amount']}</td>
                        <td>" . number_format($sub_total, 2) . "</td>
                        <td>";
                    if ($user_is_worker === 'yes') {
                        echo "<button name='ready_id' value='{$row['product_order_id']}' class='ready-btn'>Ready</button>";
                    }
                    echo "</td></tr>";
                }
                echo "<tr class='total-row'>
                    <td colspan='3'>Grand Total</td>
                    <td>" . number_format($grand_total, 2) . " ₪</td>
                    <td></td>
                </tr>";
            } else {
                echo "<tr><td colspan='5'>No orders found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</form>

<!-- ✅ Ready Orders -->
<form method="POST">
    <table>
        <caption>Ready Orders</caption>
        <thead>
            <tr>
                <th>Product</th>
                <th>Price (₪)</th>
                <th>Quantity</th>
                <th>Total (₪)</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $ready_total = 0;
            if ($ready_result && mysqli_num_rows($ready_result) > 0) {
                while ($row = mysqli_fetch_assoc($ready_result)) {
                    $sub_total = $row['product_price'] * $row['product_order_amount'];
                    $ready_total += $sub_total;
                    echo "<tr>
                        <td>{$row['product_name']}</td>
                        <td>" . number_format($row['product_price'], 2) . "</td>
                        <td>{$row['product_order_amount']}</td>
                        <td>" . number_format($sub_total, 2) . "</td>
                        <td>";
                    if ($user_is_worker === 'yes') {
                        echo "<button name='delete_ready_id' value='{$row['product_order_id']}' class='ready-btn'>Delete</button>";
                    }
                    echo "</td></tr>";
                }
                echo "<tr class='total-row'>
                    <td colspan='4'>Grand Total</td>
                    <td>" . number_format($ready_total, 2) . " ₪</td>
                </tr>";
            }else {
    echo "<tr><td colspan='5'>No ready orders yet.</td></tr>";
}
?>
        </tbody>
    </table>
</form>

</body>
</html>