<?php
/**
 * Simple Password Hash Generator
 * Use this to generate a hashed password for manual database updates
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #2c3e50; }
        .form-group { margin-bottom: 20px; }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover { background: #2980b9; }
        .result {
            margin-top: 20px;
            padding: 15px;
            background: #d4edda;
            border-radius: 5px;
            word-wrap: break-word;
        }
        .info {
            background: #d1ecf1;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 Password Hash Generator</h1>
        
        <div class="info">
            <strong>ℹ️ How to use:</strong>
            <ol>
                <li>Enter your desired password below</li>
                <li>Click "Generate Hash"</li>
                <li>Copy the generated hash</li>
                <li>Update it manually in phpMyAdmin (users table → password field)</li>
            </ol>
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="password">Enter New Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirm Password:</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <button type="submit">Generate Hash</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';
            
            if (empty($password)) {
                echo "<div style='margin-top: 20px; padding: 15px; background: #f8d7da; color: #721c24; border-radius: 5px;'>
                        ❌ Please enter a password!
                      </div>";
            } elseif ($password !== $password_confirm) {
                echo "<div style='margin-top: 20px; padding: 15px; background: #f8d7da; color: #721c24; border-radius: 5px;'>
                        ❌ Passwords do not match!
                      </div>";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                echo "<div class='result'>
                        <strong>✅ Password Hash Generated!</strong><br><br>
                        <strong>Your password:</strong> " . htmlspecialchars($password) . "<br><br>
                        <strong>Hashed password (copy this):</strong><br>
                        <textarea style='width: 100%; padding: 10px; margin-top: 10px; font-family: monospace; font-size: 12px;' 
                                  rows='3' readonly onclick='this.select()'>" . htmlspecialchars($hashed) . "</textarea>
                        <br><br>
                        <small>💡 Click the textarea to select all, then copy (Ctrl+C)</small>
                      </div>";
            }
        }
        ?>
    </div>
</body>
</html>
