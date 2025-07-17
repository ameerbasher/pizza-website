<?php
session_start();

$user_is_worker = $_SESSION['is_worker'] ?? 'no';

$conn = mysqli_connect("localhost", "root", "", "pizza");
if (mysqli_connect_errno()) {
    die("Failed to connect to MySQL: " . mysqli_connect_error());
}

$user_id  = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'User';
$message  = "";

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

if (isset($_POST["add_to_order"])) {
    $product_id = (int)$_POST["product_id"];
    $amount     = max(1, (int)$_POST["amount"]);
    $status     = 'pending';

    $result = mysqli_query($conn, "
        INSERT INTO order_product (
            product_order_order_id,
            product_order_product_id,
            product_order_amount,
            user_id,
            product_order_status
        )
        VALUES (
            $order_id,
            $product_id,
            $amount,
            $user_id,
            '$status'
        )
    ");

    if (!$result) {
        die("❌ Error in product entry" . mysqli_error($conn));
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST["submit_order"])) {
    $total_price = 0;
    $items = mysqli_query($conn, "
        SELECT p.product_price, op.product_order_amount
        FROM order_product op
        JOIN product p ON p.product_id = op.product_order_product_id
        WHERE op.product_order_order_id = $order_id AND op.user_id = $user_id
    ");
    if (!$items) {
    die("❌ Error in the query: " . mysqli_error($conn));
}

   
       

        $update = mysqli_query($conn, "
            UPDATE `order`
            SET order_price = $total_price,
                order_state = 'submitted'
            WHERE order_id = $order_id AND order_user_id = $user_id
        ");

        if (!$update) {
            die("❌ Error in updating the order: " . mysqli_error($conn));
        }

        unset($_SESSION['order_id']);
        $new_time = date("Y-m-d H:i:s");
        mysqli_query($conn, "
            INSERT INTO `order` (order_user_id, order_time, order_state)
            VALUES ($user_id, '$new_time', 'pending')
        ");
        $_SESSION['order_id'] = mysqli_insert_id($conn);

        $message = "✅ The order was sent successfully!";
    }

?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8" />
  <title>Home page</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma;
      background: linear-gradient(135deg, #fff6e6, #ffc680);
      color: #4b2e00;
      padding: 30px;
    }
    .nav-bar {
      background-color: #ffb84d;
      padding: 15px;
      font-weight: bold;
      font-size: 1.1rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      margin-bottom: 30px;
      display: flex;
      justify-content: center;
      gap: 20px;
      align-items: center;
    }
    .nav-bar a {
      color: #4b2e00;
      text-decoration: none;
      padding: 6px 12px;
      border-radius: 6px;
    }
    .nav-bar a:hover {
      background-color: #ffe0b2;
    }
    .user-dropdown {
      position: relative;
    }
    .user-toggle {
      cursor: pointer;
      padding: 6px 12px;
      border-radius: 8px;
      background-color: #ffd699;
    }
    .user-toggle:hover {
      background-color: #ffc680;
    }
    .dropdown-menu {
      display: none;
      position: absolute;
      top: 120%;
      right: 0;
      background-color: #fffbe6;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      border-radius: 8px;
      min-width: 160px;
      z-index: 100;
    }
    .dropdown-menu a {
      display: block;
      padding: 10px;
      text-decoration: none;
      color: #4b2e00;
    }
    .dropdown-menu a:hover {
      background-color: #ffe8c2;
    }
    .user-dropdown:hover .dropdown-menu {
      display: block;
    }
    .success-message {
      text-align: center;
      font-weight: bold;
      margin-top: 20px;
      color: #2e7d32;
    }
  </style>
</head>
<body>
<?php
$products = mysqli_query($conn, "SELECT * FROM product");
while ($product = mysqli_fetch_assoc($products)):
?>
 
  
<?php endwhile; ?>
 <!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8" />
  <title>Pizza Royal - Order</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background: linear-gradient(135deg, #fff6e6 0%, #ffc680 100%);
      min-height: 100vh;
      padding: 30px;
      color: #4b2e00;
    }

    h1, h2 {
      text-align: center;
      margin-bottom: 20px;
      text-shadow: 1px 1px 3px #b46a00aa;
      font-weight: 900;
      letter-spacing: 2px;
    }

    h1 {
      font-size: 3rem;
      color: #d35400;
    }

    h2 {
      font-size: 2rem;
      color: #7a4900;
      margin-top: 40px;
    }

    form {
      background: #fff9f0;
      max-width: 700px;
      margin: 0 auto 40px auto;
      padding: 30px 40px;
      border-radius: 25px;
      box-shadow: 0 12px 30px rgba(255, 132, 0, 0.3);
    }

    p {
      font-weight: 700;
      margin-bottom: 10px;
      font-size: 1.2rem;
      color: #a35400;
    }

    input[type="radio"],
    input[type="checkbox"] {
      transform: scale(1.2);
      margin-right: 10px;
      cursor: pointer;
    }

    input[type="number"],
    input[type="text"],
    input[type="date"],
    input[type="time"],
    select {
      width: 100%;
      padding: 12px 14px;
      margin: 8px 0 20px 0;
      border-radius: 12px;
      border: 2px solid #ff9d33;
      font-size: 1rem;
      outline: none;
      transition: border-color 0.3s ease;
    }

    input[type="number"]:focus,
    input[type="text"]:focus,
    input[type="date"]:focus,
    input[type="time"]:focus,
    select:focus {
      border-color: #d35400;
      box-shadow: 0 0 8px #ffb84d;
    }

    button {
      background-color: #d35400;
      color: white;
      font-weight: 700;
      font-size: 1.3rem;
      padding: 12px 0;
      border: none;
      border-radius: 16px;
      cursor: pointer;
      width: 100%;
      margin-top: 10px;
      box-shadow: 0 6px 20px rgba(211, 84, 0, 0.4);
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #b04100;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
      box-shadow: 0 8px 20px rgba(211, 84, 0, 0.2);
      border-radius: 15px;
      overflow: hidden;
    }

    th, td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #ffb84d;
      font-weight: 600;
      color: #7a4900;
    }

    th {
      background-color: #ffb84d;
      color: #4b2e00;
      font-size: 1.1rem;
    }

    tr:last-child td {
      border-bottom: none;
      font-size: 1.15rem;
      font-weight: 800;
      color: #d35400;
    }

    .message {
      max-width: 700px;
      margin: 10px auto 40px auto;
      padding: 15px 25px;
      background-color: #fff3e0;
      border: 2px solid #ffb84d;
      border-radius: 15px;
      color: #ad4d00;
      font-weight: 700;
      text-align: center;
      box-shadow: 0 6px 20px rgba(255, 152, 0, 0.3);
    }

    .orders-btn {
      max-width: 700px;
      margin: 0 auto 25px auto;
      text-align: right;
    }

    .orders-btn a {
      background-color: #d35400;
      color: white;
      padding: 10px 22px;
      border-radius: 14px;
      text-decoration: none;
      font-weight: 700;
      box-shadow: 0 6px 15px rgba(211, 84, 0, 0.4);
      transition: background-color 0.3s ease;
      user-select: none;
      display: inline-block;
    }

    .orders-btn a:hover {
      background-color: #b04100;
    }

    .user-toggle {
      cursor: pointer;
      font-weight: 600;
      user-select: none;
      padding: 5px;
    }

    .dropdown-menu {
      display: none;
      position: absolute;
      background-color: #fff3e0;
      border: 2px solid #ffb84d;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(211, 84, 0, 0.2);
      padding: 10px 15px;
      z-index: 1000;
    }

    .dropdown-menu.show {
      display: block;
    }

    @media (max-width: 768px) {
      form {
        padding: 25px 20px;
        width: 90%;
      }

      table, th, td {
        font-size: 0.9rem;
      }

      button, .orders-btn a {
        font-size: 1.1rem;
      }
    }
  </style>
</head>
<body>
  <div class="nav-bar">
    <a href="menu.php">Home Page</a>
    <a href="orders.php">Orders</a>
    <div class="user-dropdown">
      <div class="user-toggle" onclick="toggleDropdown()">
        <?php echo htmlspecialchars($username); ?> ⮟
      </div>
      <div class="dropdown-menu" id="dropdownMenu">
        <a href="logout.php">🔓 Log out</a>
      </div>
    </div>
  </div>

  <?php if (!empty($message)): ?>
    <div class="success-message"><?php echo $message; ?></div>
  <?php endif; ?>

  <script>
    function toggleDropdown() {
      const menu = document.getElementById("dropdownMenu");
      menu.classList.toggle("show");
    }

    document.addEventListener("click", function (event) {
      const menu = document.getElementById("dropdownMenu");
      const toggle = document.querySelector(".user-toggle");

      if (!menu.contains(event.target) && !toggle.contains(event.target)) {
        menu.classList.remove("show");
      }
    });
  </script>
</body>
</html>
<body>
    <h1>Pizza Royal</h1>

    

    <form method="POST" autocomplete="off" novalidate>
        <p>Select your Product:</p>
        <div>
            <?php
            $result = mysqli_query($conn, "SELECT product_id, product_name, product_price FROM product WHERE product_father_product_id = 0 ORDER BY product_name");
            while ($row = mysqli_fetch_array($result)) {
                $checked = (isset($_POST["product_id"]) && $row["product_id"] == $_POST["product_id"]) ? "checked" : "";
                echo "<label><input type='radio' name='product_id' value='{$row['product_id']}' onchange='this.form.submit();' $checked> {$row["product_name"]} - {$row["product_price"]} ₪</label><br>";
            }
            ?>
        </div>

        <p>Select your add-ons:</p>
        <div>
            <?php
            if (isset($_POST["product_id"])) {
                $pid = (int)$_POST["product_id"];
                $result = mysqli_query($conn, "SELECT product_id, product_name, product_price FROM product WHERE product_father_product_id = $pid ORDER BY product_name");
                while ($row = mysqli_fetch_array($result)) {
                    echo "<label><input type='checkbox' name='product_addon_id[]' value='{$row['product_id']}'> {$row["product_name"]} - {$row["product_price"]} ₪</label><br>";
                }
            }
            ?>
        </div>

        <label for="amount">Amount:</label>
        <input type="number" min="1" name="amount" id="amount" value="1" required>

        <label for="order_type">Order Type:</label>
        <select name="order_type" id="order_type" required>
            <option value="">-- Select Order Type --</option>
            <option value="Delivery" <?= (isset($_POST['order_type']) && $_POST['order_type'] == "Delivery") ? 'selected' : '' ?>>Delivery</option>
            <option value="Pickup" <?= (isset($_POST['order_type']) && $_POST['order_type'] == "Pickup") ? 'selected' : '' ?>>Pickup</option>
        </select>

        <label for="order_address">Order Address:</label>
        <input type="text" name="order_address" id="order_address" value="<?= isset($_POST['order_address']) ? htmlspecialchars($_POST['order_address']) : '' ?>">

        <label for="order_kupan">Coupon Code:</label>
        <input type="text" name="order_kupan" id="order_kupan" value="<?= isset($_POST['order_kupan']) ? htmlspecialchars($_POST['order_kupan']) : '' ?>">

        <button name="add_to_order" type="submit">Add To Order</button>
    </form>

    <h2>Current Order:</h2>
    <?php
    if (isset($_SESSION['order_submitted']) && $_SESSION['order_submitted'] === true) {
        echo "<p class='message'><em>Order submitted. Current order is empty.</em></p>";
    } else {
        $result = mysqli_query($conn, "SELECT product.product_name, product.product_price, order_product.product_order_amount 
                                    FROM order_product
                                    JOIN product ON order_product.product_order_product_id = product.product_id
                                    WHERE order_product.product_order_order_id = $order_id");

        $total_price = 0;

        echo "<table>
                <thead>
                    <tr><th>Product - Price × Quantity</th></tr>
                </thead>
                <tbody>";

        while ($row = mysqli_fetch_array($result)) {
            $name = htmlspecialchars($row['product_name']);
            $price = $row['product_price'];
            $amount = $row['product_order_amount'];
            $item_total = $price * $amount;
            $total_price += $item_total;

            echo "<tr><td>$name - $price ₪ × $amount = $item_total ₪</td></tr>";
        }

        echo "<tr><td><strong>Total: $total_price ₪</strong></td></tr>";
        echo "</tbody></table>";
        
    }
    ?>

    <form method="POST" style="max-width: 700px; margin: 30px auto 60px auto;">
        <button name="submit_order" type="submit" style="margin-right:10px;">Submit Order</button>
        <button name="clean_order" type="submit" style="background-color:#f44336; box-shadow:none;">Clean Order</button>
    </form>

    <hr style="max-width:700px; margin: 0 auto 40px auto; border:1px solid #ffb84d;">

  <?php if ($user_is_worker === 'yes'): ?>
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
