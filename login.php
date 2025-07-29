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

if (isset($_POST["login"])) {
    $username = mysqli_real_escape_string($conn, trim($_POST["Username"]));
    $password = trim($_POST["Password"]);

    if (empty($username)) $errors[] = "Username field is required.";
    if (empty($password)) $errors[] = "Password field is required.";

    if (empty($errors)) {
        $result = mysqli_query($conn, "
            SELECT user_id, user_name, user_username, user_password, user_is_worker, role
            FROM user 
            WHERE user_username = '$username'
        ");

        if ($result->num_rows == 0) {
            $errors[] = "The username does not exist.";
        } else {
            $row = mysqli_fetch_assoc($result);

            if (password_verify($password, $row['user_password'])) {
                $_SESSION["user_id"] = $row["user_id"];
                $_SESSION["is_worker"] = $row["user_is_worker"];
                $_SESSION["name"] = $row["user_name"];
                $_SESSION["username"] = $row["user_username"];
              $_SESSION['role'] = $row['role'];



        header("Location: menu.php");
                exit();
            } else {
                $errors[] = "The password is incorrect.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Pizza Royal - Login</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #fefefe 0%, #ffd2a6 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            color: #333;
        }

        .login-container {
            background: #fff;
            padding: 40px 50px;
            border-radius: 20px;
            box-shadow: 0 18px 40px rgba(255, 130, 0, 0.3);
            width: 380px;
            max-width: 90vw;
            text-align: center;
        }

        .login-container h1.title {
            font-size: 3rem;
            color: #ff6f00;
            font-weight: 900;
            margin-bottom: 10px;
            letter-spacing: 3px;
        }

        .login-container h2.subtitle {
            font-size: 1.7rem;
            color: #555;
            margin-bottom: 30px;
            font-weight: 600;
            letter-spacing: 1.5px;
        }

        .error-box {
            background-color: #fdd;
            color: #b00020;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid #f99;
            font-weight: 600;
            text-align: left;
        }

        .error-box ul {
            margin: 0;
            padding-left: 20px;
        }

        form { text-align: left; }

        label {
            font-weight: 600;
            font-size: 1.1rem;
            color: #444;
            display: block;
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 25px;
            font-size: 1.1rem;
            border: 2px solid #ffb84d;
            border-radius: 12px;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #ff6f00;
            box-shadow: 0 0 8px #ffb84d;
        }

        button {
            width: 100%;
            padding: 15px;
            background-color: #ff6f00;
            color: white;
            font-weight: 700;
            font-size: 1.3rem;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover { background-color: #e65c00; }

        .register-btn {
            margin-top: 15px;
            background-color: #ffb84d;
            color: #333;
            font-size: 1.1rem;
        }

        .register-btn:hover { background-color: #ffa726; }

        @media (max-width: 480px) {
            .login-container { width: 90vw; padding: 30px 25px; }
            .login-container h1.title { font-size: 2.4rem; }
            .login-container h2.subtitle { font-size: 1.3rem; }
            button { font-size: 1.1rem; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="title">Pizza Royal</h1>
        <h2 class="subtitle">Sign In</h2>

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

        <form method="post" autocomplete="off" novalidate>
            <label for="Username">Username:</label>
            <input type="text" id="Username" name="Username" placeholder="Enter your username" value="<?= htmlspecialchars($_POST['Username'] ?? '') ?>" required>

            <label for="Password">Password:</label>
            <input type="password" id="Password" name="Password" placeholder="Enter your password" required minlength="4" maxlength="8">

            <button type="submit" name="login">Login</button>
        </form>

        <a href="index.php" style="display: block; text-decoration: none;">
            <button type="button" class="register-btn">Register</button>
        </a>
    </div>
</body>
</html>
