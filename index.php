<?php
session_start();
require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $email = $_POST['email'];
    $name = $_POST['name'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $error = 'Email is already registered.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (email, name, password) VALUES (:email, :name, :password)');
            if ($stmt->execute(['email' => $email, 'name' => $name, 'password' => $hashedPassword])) {
                $success = 'Registration successful. <a href="login.php">Log in here</a>.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
        $uploadError = 'You must be logged in to upload images.';
    } else {
        if (isset($_FILES['imageUpload']) && $_FILES['imageUpload']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'images/';
            $uploadFile = $uploadDir . basename($_FILES['imageUpload']['name']);

            $stmt = $pdo->prepare('SELECT image_path FROM users WHERE id = :id');
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Delete old image if it exists
            $oldImage = $user['image_path'] ?? null;
            if ($oldImage && file_exists($oldImage)) {
                unlink($oldImage);
            }

            if (move_uploaded_file($_FILES['imageUpload']['tmp_name'], $uploadFile)) {
                // Update image path in database
                $stmt = $pdo->prepare('UPDATE users SET image_path = :image_path WHERE id = :id');
                $stmt->execute(['image_path' => $uploadFile, 'id' => $_SESSION['user_id']]);
                $uploadSuccess = 'Image uploaded successfully.';
            } else {
                $uploadError = 'Image upload failed.';
            }
        } else {
            $uploadError = 'No image uploaded or an error occurred.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteImage'])) {
    if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
        $deleteError = 'You must be logged in to delete images.';
    } else {
        $stmt = $pdo->prepare('SELECT image_path FROM users WHERE id = :id');
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $imagePath = $user['image_path'] ?? null;
        if ($imagePath && file_exists($imagePath)) {
            if (unlink($imagePath)) {
                $stmt = $pdo->prepare('UPDATE users SET image_path = NULL WHERE id = :id');
                $stmt->execute(['id' => $_SESSION['user_id']]);
                $deleteSuccess = 'Image deleted successfully.';
            } else {
                $deleteError = 'Failed to delete the image.';
            }
        } else {
            $deleteError = 'No image to delete.';
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Fetch user details
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $imagePath = $user['image_path'] ?? '';
} else {
    $imagePath = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        main {
            padding: 20px;
            max-width: 600px;
            margin: auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            margin: 0 0 20px 0;
        }
        form.register {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        label {
            font-weight: bold;
            margin-top: 30px;
        }
        input[type="text"], input[type="email"], input[type="password"], input[type="file"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 100%;
        }
        input[type="submit"] {
            padding: 10px;
            background-color: #28a745;
            border: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #218838;
        }
        .error, .success {
            padding: 10px;
            border-radius: 4px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <?php if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']): ?>
            <!-- Signup Form -->
            <h2>Sign Up</h2>
            <form action="index.php" method="post">
                <input type="hidden" name="register" value="true">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required><br>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required><br>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required><br>

                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required><br>

                <input type="submit" value="Sign Up">
            </form>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php elseif (isset($success)): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>

        <?php else: ?>
            <h2>Welcome </h2>

            <h3>Upload Image</h3>
            <form action="index.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="upload" value="true">
                <label for="imageUpload">Choose an image to upload:</label>
                <input type="file" id="imageUpload" name="imageUpload" required><br>
                <input type="submit" value="Upload Image">
            </form>
            <?php if (isset($uploadError)): ?>
                <p class="error"><?php echo $uploadError; ?></p>
            <?php elseif (isset($uploadSuccess)): ?>
                <p class="success"><?php echo $uploadSuccess; ?></p>
            <?php endif; ?>

            <?php if ($imagePath): ?>
                <h3>Your Uploaded Image</h3>
                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="User Image">
                <!-- Image Deletion Form -->
                <form action="index.php" method="post">
                    <input type="hidden" name="deleteImage" value="true">
                    <input type="submit" value="Delete Image">
                </form>
                <?php if (isset($deleteError)): ?>
                    <p class="error"><?php echo $deleteError; ?></p>
                <?php elseif (isset($deleteSuccess)): ?>
                    <p class="success"><?php echo $deleteSuccess; ?></p>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>
