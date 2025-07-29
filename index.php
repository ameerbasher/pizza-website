<?php
session_start();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$conn = mysqli_connect("localhost", "root", "", "pizza");
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}

$errors = [];

if (isset($_POST["send"])) {
    $name      = mysqli_real_escape_string($conn, trim($_POST["name"]));
    $username  = mysqli_real_escape_string($conn, trim($_POST["Username"]));
    $password  = trim($_POST["Password"]);
    $address   = mysqli_real_escape_string($conn, trim($_POST["adress"]));
    $phone     = mysqli_real_escape_string($conn, trim($_POST["Phone"]));
   

    if (empty($name)) $errors[] = "Name field is required.";
    if (empty($username)) $errors[] = "Username field is required.";
    if (strlen($password) < 4 || strlen($password) > 8) $errors[] = "Password must be between 4 and 8 characters.";
    if (!preg_match('/^[0-9]{10,15}$/', $phone)) $errors[] = "Phone number must be between 10 and 15 digits.";
    if (empty($address)) $errors[] = "Address field is required.";
    

    if (empty($errors)) {
        $checkQuery = "SELECT user_id FROM user WHERE user_username = '$username' LIMIT 1";
        $checkResult = mysqli_query($conn, $checkQuery);
        if (mysqli_num_rows($checkResult) > 0) {
            $errors[] = "Username already exists. Please choose another one.";
        }
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query = "
            INSERT INTO user (user_name, user_username, user_password, user_address, user_phone, user_is_worker)
            VALUES ('$name', '$username', '$hashedPassword', '$address', '$phone', '$is_worker')
        ";

        if (mysqli_query($conn, $query)) {
            $_SESSION['is_worker'] = $is_worker;   
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Database error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Pizza Royal - Register</title>
    <style>
        html, body {
            height: 115%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma;
            background:
                linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)),
                url('') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
        }
        form {
            background: rgba(255,255,255,0.95);
            padding: 40px 50px;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.07);
            width: 420px;
            max-width: 90vw;
            display: flex;
            flex-direction: column;
            color: #333;
        }
        h1.main-title {
            text-align: center;
            color: #d84315;
            font-size: 3.2rem;
            font-weight: 900;
            margin-bottom: 20px;
            letter-spacing: 3px;
        }
        .error-box {
            background-color: #fdd;
            color: #b00020;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid #f99;
            font-weight: 600;
        }
        .error-box ul {
            margin: 0;
            padding-left: 20px;
        }
        label {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: #5a2e00;
        }
        input[type="text"],
        input[type="password"],
        input[type="tel"],
        select {
            padding: 14px 16px;
            margin-bottom: 28px;
            font-size: 1.1rem;
            border: 2px solid #d84315;
            border-radius: 12px;
            outline: none;
            color: #333;
        }
        input:focus, select:focus {
            border-color: #ff7043;
            box-shadow: 0 0 8px #ffab91;
        }
        .buttons {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 10px;
        }
        button {
            padding: 14px 50px;
            font-size: 1.2rem;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 700;
        }
        button[type="reset"] {
            background-color: #d84315;
            color: #fff;
        }
        button[type="submit"] {
            background-color: #ff7043;
            color: #fff;
        }
        button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <form method="post" autocomplete="off" novalidate>
        <h1 class="main-title">Pizza Royal</h1>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <strong>Fix the following errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <label for="name">Name :</label>
        <input type="text" name="name" id="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">

        <label for="Username">Username :</label>
        <input type="text" name="Username" id="Username" required value="<?= htmlspecialchars($_POST['Username'] ?? '') ?>">

        <label for="Password">Password :</label>
        <input type="password" name="Password" id="Password" required minlength="4" maxlength="8">

        <label for="Phone">Phone :</label>
        <input type="tel" name="Phone" id="Phone" pattern="[0-9]{10,15}" required value="<?= htmlspecialchars($_POST['Phone'] ?? '') ?>">

        <label for="adress">Address :</label>
        <input type="text" name="adress" id="adress" required value="<?= htmlspecialchars($_POST['adress'] ?? '') ?>">

      

        <div class="buttons">
            <button type="reset">Reset</button>
            <button type="submit" name="send">Submit</button>
        </div>
    </form>
</body>
</html>
