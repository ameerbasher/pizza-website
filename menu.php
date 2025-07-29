<?php
session_start();
$is_manager = $_SESSION['is_manager'] ?? 'no';
$user_is_worker = $_SESSION['is_worker'] ?? 'no';
$user_role = $_SESSION['role'] ?? 'user';
$user_id = $_SESSION['user_id'] ?? 0;
$order_id = $_SESSION['order_id'] ?? 0;

$conn = mysqli_connect("localhost", "root", "", "pizza");
if (mysqli_connect_errno()) {
    die("Failed to connect to MySQL: " . mysqli_connect_error());
}

$is_manager = ($user_role === 'manager');
$username = $_SESSION['username'] ?? 'User';
$message = "";

/* حفظ المنتج المختار للإضافات */
$selected_product_id = $_SESSION['selected_product_id'] ?? 0;
if (isset($_POST['product_id'])) {
    $selected_product_id = (int)$_POST['product_id'];
    $_SESSION['selected_product_id'] = $selected_product_id;
}

/* تحديث حالة العامل */
if ($is_manager && isset($_POST['update_worker'], $_POST['selected_user'], $_POST['worker_status'])) {
    $user_id_to_update = (int)$_POST['selected_user'];
    $new_status = ($_POST['worker_status'] === 'yes') ? 'yes' : 'no';
    mysqli_query($conn, "UPDATE user SET user_is_worker = '$new_status' WHERE user_id = $user_id_to_update");
    $message = "✅ User status updated successfully.";
}

/* ✅ حذف عنصر من الطلب بدون شرط Order Type */
if (isset($_POST['delete_item'])) {
    $product_id_to_delete = $_POST['delete_product_id'] ?? null;
    if ($product_id_to_delete && $order_id && $user_id) {
        $product_id_to_delete = (int)$product_id_to_delete;
        mysqli_query($conn, "
            DELETE FROM order_product
            WHERE product_order_order_id = $order_id
            AND user_id = $user_id
            AND product_order_product_id = $product_id_to_delete
        ");
        $message = "✅ Item deleted successfully.";
    }
}

/* حذف منتج */
if (isset($_POST['delete_product'])) {
    $product_id_to_delete = (int)($_POST['delete_product_id'] ?? 0);
    if ($product_id_to_delete > 0) {
        mysqli_query($conn, "DELETE FROM order_product WHERE product_order_product_id = $product_id_to_delete");
        mysqli_query($conn, "DELETE FROM product WHERE product_id = $product_id_to_delete");
        $message = "Product deleted successfully.";
    } else {
        $message = "Invalid product selection.";
    }
}

/* إضافة عرض */
if (isset($_POST['add_sale'])) {
    $sale_name = $_POST['sale_name'] ?? '';
    $from_date = $_POST['sale_from_date'] ?? '';
    $to_date = $_POST['sale_to_date'] ?? '';
    $from_time = $_POST['sale_from_time'] ?? '';
    $to_time = $_POST['sale_to_time'] ?? '';
    $coupon_code = $_POST['sale_kupan'] ?? '';
    $required_product_id = $_POST['sale_if_buy_product_id'] ?? 0;
    $required_amount = $_POST['sale_buy_amount'] ?? 0;
    $discount_value = $_POST['sale_discount'] ?? 0;

    mysqli_query($conn, "
        INSERT INTO sale (
            sale_name, sale_from_date, sale_to_date, sale_from_time, sale_to_time,
            sale_kupan, sale_if_buy_product_id, sale_buy_amount, sale_discount
        ) VALUES (
            '$sale_name', '$from_date', '$to_date', '$from_time', '$to_time',
            '$coupon_code', $required_product_id, $required_amount, $discount_value
        )
    ");
}

if (isset($_POST['add_product'])) {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name'] ?? '');
    $product_price = (float)($_POST['product_price'] ?? 0);
    $father_id = (int)($_POST['product_father_product_id'] ?? 0);

    $check_exist = mysqli_query($conn, "SELECT product_id FROM product WHERE product_name = '$product_name' LIMIT 1");
    if (mysqli_num_rows($check_exist) > 0) {
        $message = "Product with the same name already exists.";
    } else {
        $get_last_id = mysqli_query($conn, "SELECT MAX(product_id) AS max_id FROM product");
        $row = mysqli_fetch_assoc($get_last_id);
        $next_id = ($row['max_id'] ?? 0) + 1;

        mysqli_query($conn, "
            INSERT INTO product (product_id, product_name, product_price, product_father_product_id)
            VALUES ($next_id, '$product_name', $product_price, $father_id)
        ");
        $message = "Product added successfully.";
    }
}

if (!isset($_SESSION['order_id'])) {
    $existing = mysqli_query($conn, "
        SELECT order_id FROM `order`
        WHERE order_user_id = $user_id AND order_state = 'pending'
        LIMIT 1
    ");
    if (mysqli_num_rows($existing) > 0) {
        $row = mysqli_fetch_assoc($existing);
        $_SESSION['order_id'] = $row['order_id'];
    } else {
        $order_time = date("Y-m-d H:i:s");
        mysqli_query($conn, "
            INSERT INTO `order` (order_user_id, order_time, order_state)
            VALUES ($user_id, '$order_time', 'pending')
        ");
        $_SESSION['order_id'] = mysqli_insert_id($conn);
    }
}
$order_id = $_SESSION['order_id'];

if (isset($_POST["add_to_order"]) && isset($_POST["product_id"]) && (int)$_POST["product_id"] > 0) {
    $product_id = (int)$_POST["product_id"];
    $amount = isset($_POST["amount"]) ? (int)$_POST["amount"] : 1;
    $status = 'pending';

    mysqli_query($conn, "
        INSERT INTO order_product (
            product_order_order_id, product_order_product_id, product_order_amount, user_id, product_order_status
        ) VALUES (
            $order_id, $product_id, $amount, $user_id, '$status'
        )
    ");

    if (isset($_POST["product_addon_id"])) {
        foreach ($_POST["product_addon_id"] as $addon_id) {
            $addon_id = (int)$addon_id;
            mysqli_query($conn, "
                INSERT INTO order_product (
                    product_order_order_id, product_order_product_id, product_order_amount, user_id, product_order_status
                ) VALUES (
                    $order_id, $addon_id, $amount, $user_id, '$status'
                )
            ");
        }
    }

    $message = "✅ Product added successfully.";
}

/* إرسال الطلب */
if (isset($_POST["submit_order"])) {
    $total_price = 0;
    $coupon_code = $_POST["coupon_code"] ?? '';
    $discount = 0;

    $items = mysqli_query($conn, "
        SELECT p.product_price, op.product_order_amount
        FROM order_product op
        JOIN product p ON p.product_id = op.product_order_product_id
        WHERE op.product_order_order_id = $order_id AND op.user_id = $user_id
    ");

    while ($row = mysqli_fetch_assoc($items)) {
        $total_price += (float)$row['product_price'] * (int)$row['product_order_amount'];
    }

    if (!empty($coupon_code)) {
        $coupon_result = mysqli_query($conn, "
        SELECT sale_discount 
        FROM sale 
        WHERE sale_kupan = '$coupon_code'
        AND NOW() BETWEEN 
        CONCAT(sale_from_date, ' ', sale_from_time) 
        AND CONCAT(sale_to_date, ' ', sale_to_time)
        LIMIT 1;
        ");

        if (mysqli_num_rows($coupon_result) > 0) {
            $coupon = mysqli_fetch_assoc($coupon_result);
            $discount = (float)$coupon['sale_discount'];
            $total_price -= $discount;
            if ($total_price < 0) $total_price = 0;
        }
    }

    mysqli_query($conn, "
        UPDATE `order`
        SET order_price = $total_price, order_state = 'submitted'
        WHERE order_id = $order_id AND order_user_id = $user_id
    ");

    mysqli_query($conn, "
        UPDATE order_product
        SET product_order_status ='to_prepaire'
        WHERE product_order_order_id = $order_id
    ");

    unset($_SESSION['order_id']);
    $new_time = date("Y-m-d H:i:s");
    mysqli_query($conn, "
        INSERT INTO `order` (order_user_id, order_time, order_state)
        VALUES ($user_id, '$new_time', 'pending')
    ");

    $_SESSION['order_id'] = mysqli_insert_id($conn);
    $message = "Order submitted successfully!";
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Pizza Royal - Order</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
body { background: linear-gradient(135deg, #fff6e6 0%, #ffc680 100%); min-height: 100vh; padding: 30px; color: #4b2e00; }
h1, h2 { text-align: center; margin-bottom: 20px; text-shadow: 1px 1px 3px #b46a00aa; font-weight: 900; letter-spacing: 2px; }
h1 { font-size: 3rem; color: #d35400; }
h2 { font-size: 2rem; color: #7a4900; margin-top: 40px; }
form { background: #fff9f0; max-width: 700px; margin: 0 auto 40px auto; padding: 30px 40px; border-radius: 25px; box-shadow: 0 12px 30px rgba(255, 132, 0, 0.3); }
p { font-weight: 700; margin-bottom: 10px; font-size: 1.2rem; color: #a35400; }
input[type="number"],input[type="text"],input[type="date"],input[type="time"],select { width: 100%; padding: 12px 14px; margin: 8px 0 20px 0; border-radius: 12px; border: 2px solid #ff9d33; font-size: 1rem; outline: none; }
button { background-color: #d35400; color: white; font-weight: 700; font-size: 1.3rem; padding: 12px 0; border: none; border-radius: 16px; cursor: pointer; width: 100%; margin-top: 10px; box-shadow: 0 6px 20px rgba(211, 84, 0, 0.4); }
button:hover { background-color: #b04100; }
table { width: 100%; border-collapse: collapse; margin-top: 15px; box-shadow: 0 8px 20px rgba(211, 84, 0, 0.2); border-radius: 15px; overflow: hidden; }
th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ffb84d; font-weight: 600; color: #7a4900; }
th { background-color: #ffb84d; color: #4b2e00; font-size: 1.1rem; }
.message { max-width: 700px; margin: 10px auto 40px auto; padding: 15px 25px; background-color: #fff3e0; border: 2px solid #ffb84d; border-radius: 15px; color: #ad4d00; font-weight: 700; text-align: center; }
.nav-bar { background-color: #ffb84d; padding: 15px; font-weight: bold; font-size: 1.1rem; box-shadow: 0 2px 6px rgba(0,0,0,0.1); margin-bottom: 30px; display: flex; justify-content: center; gap: 20px; align-items: center; }
.nav-bar a { color: #4b2e00; text-decoration: none; padding: 6px 12px; border-radius: 6px; }
.nav-bar a:hover { background-color: #ffe0b2; }

.inline-form { display: inline-block; margin: 0; background: none; padding: 0; box-shadow: none; }
.delete-btn { background: #ff9800; color: #fff; border: none; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: bold; width: auto; display: inline-block; }
.delete-btn:hover { background: #e65100; }
.flex-container { max-width: 700px; margin: 0 auto 40px auto; display: flex; justify-content: space-between; gap: 20px; }
.flex-box { background: #fff9f0; padding: 20px; border-radius: 25px; box-shadow: 0 12px 30px rgba(255, 132, 0, 0.3); flex: 1; }
.flex-box h3 { margin-bottom: 15px; color: #a35400; font-weight: 700; font-size: 1.4rem; text-align: center; }
.flex-box form label { font-weight: 700; margin-bottom: 5px; display: block; }
.flex-box form input, .flex-box form select { margin-bottom: 15px; }
.flex-box form button { margin-top: 10px; }
</style>
<script>
function autoSubmit() {
    document.getElementById('productForm').submit();
}
</script>
</head>
<body>

<div class="nav-bar">
    <a href="menu.php">Home Page</a>
    <a href="orders.php">Orders</a>
    <a href="conact us"></a>
    <div><?= htmlspecialchars($username); ?> | <a href="logout.php">Log out</a></div>
</div>

<?php if (!empty($message)): ?>
    <div class="message"><?= htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($is_manager): ?>
<h2>User Permissions Management</h2>
<form method="POST">
    <label for="selected_user">Select User:</label>
    <select name="selected_user" id="selected_user" required>
        <?php
        $result = mysqli_query($conn, "SELECT user_id, user_username, user_is_worker FROM user ORDER BY user_username");
        while ($row = mysqli_fetch_assoc($result)) {
            $status = ($row['user_is_worker'] === 'yes') ? 'Worker' : 'Regular';
            echo "<option value='{$row['user_id']}'>{$row['user_username']} ({$status})</option>";
        }
        ?>
    </select>
    <br><br>
    <input type="radio" name="worker_status" value="yes" required> Worker
    <input type="radio" name="worker_status" value="no"> Customer.
    <br><br>
    <button type="submit" name="update_worker">Save Changes</button>
</form>
<?php endif; ?>

<?php if ($user_is_worker === 'yes' || $is_manager): ?>
<div class="flex-container">
  <div class="flex-box">
    <h3>Add Product</h3>
    <form method="POST" autocomplete="off" novalidate>
      <label for="product_name">Product Name:</label>
      <input type="text" id="product_name" name="product_name" required>
      <label for="product_price">Product Price:</label>
      <input type="number" id="product_price" name="product_price" min="0" step="0.01" required>
      <label for="product_father_product_id">Father Product ID:</label>
      <input type="number" id="product_father_product_id" name="product_father_product_id" min="0" value="0" required>
      <button type="submit" name="add_product">Add Product</button>
    </form>
  </div>

  <div class="flex-box">
    <h3>Delete Product</h3>
    <form method="POST" autocomplete="off" novalidate>
      <label for="delete_product_id">Select Product to Delete:</label>
      <select id="delete_product_id" name="delete_product_id" required>
        <option value="">-- Select Product --</option>
        <?php
        $products = mysqli_query($conn, "SELECT product_id, product_name FROM product ORDER BY product_name");
        while ($prod = mysqli_fetch_assoc($products)) {
            echo "<option value='{$prod['product_id']}'>" . htmlspecialchars($prod['product_name']) . "</option>";
        }
        ?>
      </select>
      <button type="submit" name="delete_product" onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">Delete Product</button>
    </form>
  </div>
</div>
<?php endif; ?>

<h2>Select Product</h2>
<form id="productForm" method="post" autocomplete="off" novalidate>
    <p>Select your Product:</p>
    <?php
    $result = mysqli_query($conn, "SELECT product_id, product_name, product_price FROM product WHERE product_father_product_id = 0 ORDER BY product_name");
    while ($row = mysqli_fetch_array($result)) {
        echo "<label>
                <input type='radio' name='product_id' value='{$row['product_id']}' " . ($selected_product_id == $row['product_id'] ? 'checked' : '') . " onchange='autoSubmit()'> 
                {$row['product_name']} - {$row['product_price']} ₪
              </label><br>";
    }
    ?>
</form>

<form method="POST" autocomplete="off" novalidate>
    <p>Select your add-ons:</p>
    <div>
      <?php
      if ($selected_product_id > 0) {
          $result = mysqli_query($conn, "SELECT product_id, product_name, product_price FROM product WHERE product_father_product_id = $selected_product_id ORDER BY product_name");
          while ($row = mysqli_fetch_array($result)) {
              echo "<label><input type='checkbox' name='product_addon_id[]' value='{$row['product_id']}'> " . htmlspecialchars($row["product_name"]) . " - {$row["product_price"]} ₪</label><br>";
          }
      } else {
          echo "<p style='color:red;'>Please choose a product first.</p>";
      }
      ?>
    </div>
    <label for="amount">Amount:</label>
    <input type="number" min="1" name="amount" id="amount" value="1" required>
    <input type="hidden" name="product_id" value="<?= $selected_product_id ?>">
    <button type="submit" name="add_to_order">Add To Order</button>
</form>

<h2>Current Order:</h2>
<?php
$result = mysqli_query($conn, "
SELECT product.product_id, product.product_name, product.product_price, order_product.product_order_amount 
FROM order_product
JOIN product ON order_product.product_order_product_id = product.product_id
WHERE order_product.product_order_order_id = $order_id
AND product_order_status = 'pending'");

$total_price = 0;

echo "<table>
        <thead>
            <tr>
                <th>Product - Price × Quantity</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>";

while ($row = mysqli_fetch_array($result)) {
    $name = htmlspecialchars($row['product_name']);
    $price = $row['product_price'];
    $amount = $row['product_order_amount'];
    $item_total = $price * $amount;
    $total_price += $item_total;

    echo "<tr>
            <td>$name - $price ₪ × $amount = $item_total ₪</td>
            <td>
            <form method='POST' class='inline-form'>
                <input type='hidden' name='delete_product_id' value='{$row['product_id']}'>
                <button type='submit' name='delete_item' class='delete-btn'>❌ Delete</button>
            </form>
            </td>
          </tr>";
}

echo "<tr>
        <td colspan='2' style='text-align:right; font-weight:bold; font-size:1.1rem; color:#d35400;'>
            Total: $total_price ₪
        </td>
      </tr>";

echo "</tbody></table>";
?>

<form method="POST" style="max-width: 700px; margin: 30px auto 60px auto;">
  <label for="order_type">Order Type:</label>
  <select name="order_type" id="order_type" required>
    <option value="">-- Select Order Type --</option>
    <option value="Delivery" <?= (isset($_POST['order_type']) && $_POST['order_type'] == "Delivery") ? 'selected' : '' ?>>Delivery</option>
    <option value="Pickup" <?= (isset($_POST['order_type']) && $_POST['order_type'] == "Pickup") ? 'selected' : '' ?>>Pickup</option>
  </select>

  <label for="order_address">Order Address:</label>
  <input type="text" name="order_address" id="order_address" value="<?= isset($_POST['order_address']) ? htmlspecialchars($_POST['order_address']) : '' ?>">

  <label for="coupon_code">Coupon Code:</label>
  <input type="text" name="coupon_code" id="coupon_code" placeholder="Enter coupon code" value="<?= isset($_POST['coupon_code']) ? htmlspecialchars($_POST['coupon_code']) : '' ?>">

  <button name="submit_order" type="submit" style="margin-right:10px;">Submit Order</button>
  <button name="clean_order" type="submit" style="background-color:#f44336; box-shadow:none;">Clean Order</button>
</form>

<hr style="max-width:700px; margin: 0 auto 40px auto; border:1px solid #ffb84d;">

<?php if ($user_is_worker === 'yes' || $is_manager): ?>
  <h2>Add Sale</h2>
  <?php if (isset($sale_message)) : ?>
    <p class="message"><?= htmlspecialchars($sale_message) ?></p>
  <?php endif; ?>
  <form method="POST" style="max-width: 700px; margin: 0 auto 40px auto;">
    <label for="sale_name">Sale Name :</label>
    <input type="text" name="sale_name" id="sale_name" required>

    <label for="sale_from_date">From Date :</label>
    <input type="date" name="sale_from_date" id="sale_from_date" required>

    <label for="sale_to_date">To Date :</label>
    <input type="date" name="sale_to_date" id="sale_to_date" required>

    <label for="sale_from_time">From Time :</label>
    <input type="time" name="sale_from_time" id="sale_from_time" required>

    <label for="sale_to_time">To Time :</label>
    <input type="time" name="sale_to_time" id="sale_to_time" required>

    <label for="sale_kupan">Coupon :</label>
    <input type="text" name="sale_kupan" id="sale_kupan">

    <label for="sale_if_buy_product_id">Product ID Required :</label>
    <input type="number" name="sale_if_buy_product_id" id="sale_if_buy_product_id">

    <label for="sale_buy_amount">Amount Required :</label>
    <input type="number" name="sale_buy_amount" id="sale_buy_amount">

    <label for="sale_discount">Discount :</label>
    <input type="number" step="0.01" name="sale_discount" id="sale_discount">

    <button type="submit" name="add_sale">Add Sale</button>
  </form>
<?php endif; ?>

</body>
</html>
