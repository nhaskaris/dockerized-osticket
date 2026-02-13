<?php
require_once 'admin.inc.php';

if (!isset($thisstaff) || !$thisstaff->isAdmin()) {
    http_response_code(403);
    die('Access denied.');
}

// 1. Setup Paths
$upload_dir = dirname(__DIR__) . '/images/';
$base_url = rtrim(ROOT_PATH, '/') . '/images/'; 

// 2. Construct Full URL (Protocol + Domain + Path)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$full_domain_url = $protocol . $_SERVER['HTTP_HOST'] . $base_url;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = preg_replace("/[^a-zA-Z0-9\._-]/", "_", basename($file['name'])); 
        $target = $upload_dir . $filename;
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $message = "<div class='alert alert-success'>Upload successful!</div>";
        } else {
            $message = "<div class='alert alert-error'>Failed to move file. Check /images/ permissions.</div>";
        }
    }
}

$assets = array();
if (is_dir($upload_dir)) {
    foreach (scandir($upload_dir) as $file) {
        if ($file === '.' || $file === '..' || $file === '.htaccess') continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $assets[] = [
            'name' => $file,
            'is_image' => in_array($ext, ['jpg','jpeg','png','gif','webp','svg']),
            'full_url' => $full_domain_url . rawurlencode($file)
        ];
    }
}

require_once STAFFINC_DIR.'header.inc.php';
?>

<div class="panel" style="max-width:550px;margin:30px auto;padding:25px;background:#fff;border-radius:8px;border:1px solid #ddd;box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <h2 style="margin-top:0;">Asset Manager</h2>
    <?php echo $message; ?>
    <form method="post" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:center;">
        <input type="file" name="file" required style="flex-grow:1;">
        <button type="submit" class="button">Upload</button>
    </form>
</div>

<?php if ($assets) { ?>
<div class="panel" style="max-width:750px;margin:20px auto;padding:20px;background:#fff;border-radius:8px;border:1px solid #ddd;">
    <table class="list" style="width:100%;">
        <thead>
            <tr>
                <th>Preview</th>
                <th>File Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($assets as $asset) { ?>
            <tr>
                <td style="width:60px; text-align:center;">
                    <?php if ($asset['is_image']) { ?>
                        <img src="<?php echo $asset['full_url']; ?>" style="max-height:30px; border-radius:3px; border:1px solid #eee;">
                    <?php } ?>
                </td>
                <td><code style="background:#f9f9f9; padding:2px 5px;"><?php echo htmlspecialchars($asset['name']); ?></code></td>
                <td>
                    <button type="button" class="button mini" 
                            onclick="copyText('<?php echo $asset['full_url']; ?>', this)">
                        Copy Full URL
                    </button>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<?php } ?>

<script>
function copyText(text, btn) {
    var dummy = document.createElement("textarea");
    document.body.appendChild(dummy);
    dummy.value = text;
    dummy.select();
    document.execCommand("copy");
    document.body.removeChild(dummy);
    
    var oldText = btn.innerText;
    btn.innerText = 'Copied!';
    setTimeout(function() { btn.innerText = oldText; }, 1500);
}
</script>

<?php require_once STAFFINC_DIR.'footer.inc.php'; ?>