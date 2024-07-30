<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f4f4f4;
        }
        header, footer {
            background-color: #333;
            color: white;
            width: 100%;
            text-align: center;

        }
        header {
            top: 0;
            padding: 10px 0;
        }
        footer {
            position: absolute;
            bottom: 0;
        }
        nav a {
            color: white;
            margin: 0 10px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <header>
        <h1>Newar Gang</h1>
        <nav>
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                <a href="index.php">Home</a>
                <a href="index.php?logout=true">Logout</a>
            <?php else: ?>
                <a href="index.php">Register</a>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </nav>
    </header>
