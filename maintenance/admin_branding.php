<?php
session_start();
// Basic security check (optional, can be refined)
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    // header("Location: login.php"); // Uncomment if you want to restrict access
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["logo"])) {
    $extension = strtolower(pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION));
    $target_file = ($extension === 'png') ? "logo.png" : "logo.jpg";

    // Clear old logo to avoid confusion
    if ($extension === 'png' && file_exists("logo.jpg"))
        unlink("logo.jpg");
    if (($extension === 'jpg' || $extension === 'jpeg') && file_exists("logo.png"))
        unlink("logo.png");

    if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
        $message = "<div style='color: green; padding: 10px; border: 1px solid green; border-radius: 4px; background: #e6ffed; margin-bottom: 20px;'>
                        <b>Logo Updated Successfully!</b> You can now go to the <a href='login.php'>Login Page</a> to see it.
                    </div>";
    } else {
        $message = "<div style='color: red; padding: 10px; border: 1px solid red; border-radius: 4px; background: #fff5f5; margin-bottom: 20px;'>
                        <b>Error:</b> Failed to upload logo. Please check folder permissions.
                    </div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JIL- VMS Branding Utility</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .branding-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            max-width: 500px;
            margin: 100px auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
    </style>
</head>

<body style="background: #f0f4f9; font-family: 'Inter', sans-serif;">
    <div class="branding-card">
        <h2 style="color: #0047b3; margin-bottom: 10px;">Branding Utility</h2>
        <p style="color: #64748b; margin-bottom: 30px;">Upload your company logo (JPG format) to fix the broken image
            issue.</p>

        <?php echo $message; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <div style="margin-bottom: 25px;">
                <input type="file" name="logo" accept=".jpg,.jpeg" required
                    style="padding: 15px; border: 2px dashed #cbd5e1; border-radius: 8px; width: 100%; cursor: pointer;">
            </div>
            <button type="submit" class="btn-primary"
                style="width: 100%; padding: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Apply
                New Logo</button>
        </form>

        <p style="margin-top: 20px;"><a href="index.php"
                style="color: #64748b; text-decoration: none; font-size: 0.9rem;">&larr; Back to Portal</a></p>
    </div>
</body>

</html>