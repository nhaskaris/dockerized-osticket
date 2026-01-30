<?php
// Simple admin-only file uploader for osTicket custom assets
require_once 'scp/admin.inc.php';
// Only allow access for logged-in admins
if (!isset($thisstaff) || !$thisstaff->isAdmin()) {
    http_response_code(403);
    die('Access denied. Admins only.');
}

$upload_dir = __DIR__ . '/assets/custom/';
$upload_url = 'assets/custom/';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = basename($file['name']);
        $target = $upload_dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $message = "<span style='color:green'>Upload successful: <a href='$upload_url$filename' target='_blank'>$filename</a></span>";
        } else {
            $message = "<span style='color:red'>Failed to move uploaded file.</span>";
        }
    } else {
        $message = "<span style='color:red'>Upload error: " . $file['error'] . "</span>";
    }
}
?>
<!DOCTYPE html>
<html><head><title>Upload Custom Asset</title></head><body>
<h2>Upload Custom Asset (Admin Only)</h2>
<?php if ($message) echo $message; ?>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="file" required>
    <button type="submit">Upload</button>
</form>
<p>Files will be uploaded to: <code>osTicket/upload/assets/custom/</code></p>
</body></html>
