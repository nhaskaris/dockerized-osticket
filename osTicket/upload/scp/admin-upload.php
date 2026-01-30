<?php
require_once 'admin.inc.php';
// Only allow access for logged-in admins
if (!isset($thisstaff) || !$thisstaff->isAdmin()) {
    http_response_code(403);
    die('Access denied. Admins only.');
}

$upload_dir = dirname(__DIR__) . '/images/';
$upload_url = '/images/';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = basename($file['name']);
        $target = $upload_dir . $filename;
        // Try to create the directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $message = "<div class='alert alert-success'><i class='icon-check'></i> Upload successful: <a href='$upload_url$filename' target='_blank'>$filename</a></div>";
        } else {
            $message = "<div class='alert alert-error'><i class='icon-warning-sign'></i> Failed to move uploaded file. Check directory permissions.</div>";
        }
    } else {
        $message = "<div class='alert alert-error'><i class='icon-warning-sign'></i> Upload error: " . $file['error'] . "</div>";
    }
}

// List all files in the images directory
$assets = array();
if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $upload_dir . $file;
        if (is_file($path)) {
            // Try to set correct mime type for images
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $is_image = in_array($ext, ['jpg','jpeg','png','gif','webp','bmp','svg']);
            $assets[] = [
                'name' => $file,
                'is_image' => $is_image
            ];
        }
    }
}

require_once STAFFINC_DIR.'header.inc.php';
?>
<div class="panel" style="max-width:520px;margin:40px auto 0 auto;padding:32px 32px 24px 32px;box-shadow:0 2px 12px rgba(0,0,0,0.08);background:#fff;border-radius:10px;">
    <h2 style="margin-top:0;font-size:1.5em;font-weight:600;"><i class="icon-upload"></i> <?php echo __('Upload Custom Asset (Admin Only)'); ?></h2>
    <?php if ($message) echo $message; ?>
    <form method="post" enctype="multipart/form-data" style="margin-bottom:20px;display:flex;flex-wrap:wrap;align-items:center;gap:12px;">
        <label style="font-weight:500;display:block;margin-bottom:0;flex:1 0 160px;">Select file to upload:</label>
        <input type="file" name="file" required style="margin-bottom:0;flex:2 1 220px;">
        <button type="submit" class="button" style="min-width:120px;margin-top:0;"><i class="icon-upload"></i> <?php echo __('Upload'); ?></button>
    </form>
    <div class="info" style="margin-top:18px;color:#666;font-size:0.97em;">
        <i class="icon-info-sign"></i> <?php echo __('Files will be uploaded to:'); ?> <code>osTicket/upload/images/</code>
    </div>
</div>

<?php if (count($assets)) { ?>
<div class="panel" style="max-width:700px;margin:32px auto 0 auto;padding:24px 24px 18px 24px;box-shadow:0 2px 12px rgba(0,0,0,0.06);background:#fff;border-radius:10px;">
    <h3 style="margin-top:0;font-size:1.15em;font-weight:600;"><i class="icon-list"></i> <?php echo __('Uploaded Assets'); ?></h3>
    <table class="list" style="width:100%;margin-top:10px;">
        <thead>
            <tr><th style="text-align:left;">Filename</th><th style="text-align:left;">Link</th></tr>
        </thead>
        <tbody>
        <?php foreach ($assets as $asset) { ?>
            <tr>
                <td style="padding:6px 8px;vertical-align:middle;"><code id="asset-path-<?php echo md5($asset['name']); ?>"><?php echo htmlspecialchars($asset['name']); ?></code></td>
                <td style="padding:6px 8px;vertical-align:middle;">
                    <?php if ($asset['is_image']) { ?>
                        <img src="<?php echo $upload_url . rawurlencode($asset['name']); ?>" alt="preview" style="max-height:32px;vertical-align:middle;margin-right:8px;border-radius:3px;box-shadow:0 1px 4px rgba(0,0,0,0.08);" />
                    <?php } ?>
                    <button type="button" class="button mini" style="margin-left:10px;" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($upload_url . rawurlencode($asset['name']), ENT_QUOTES); ?>');this.innerText='Copied!';setTimeout(()=>this.innerText='Copy Path',1200);">Copy Path</button>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<?php } ?>
<?php require_once STAFFINC_DIR.'footer.inc.php'; ?>
