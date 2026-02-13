<?php
require_once 'admin.inc.php';

if (!isset($thisstaff) || !$thisstaff->isAdmin()) {
    http_response_code(403);
    die('Access denied.');
}

$upload_dir = dirname(__DIR__) . '/images/';
$base_url = rtrim(ROOT_PATH, '/') . '/images/'; 
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$full_domain_url = $protocol . $_SERVER['HTTP_HOST'] . $base_url;

$message = '';

// --- 1. Handle Deletion (Post-Redirect-Get) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_delete'])) {
    $file_to_delete = basename($_POST['do_delete']);
    $target_delete = $upload_dir . $file_to_delete;
    if (is_file($target_delete) && unlink($target_delete)) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=deleted");
        exit;
    }
}

// --- 2. Handle Uploads ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $filename = preg_replace("/[^a-zA-Z0-9\._-]/", "_", basename($file['name'])); 
    if ($filename == 'blob' || !$filename) $filename = 'pasted_' . date('Ymd_His') . '.png';

    if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
    $target = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=uploaded");
        exit;
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'uploaded') $message = "<div class='alert alert-success'>File uploaded successfully.</div>";
    if ($_GET['msg'] == 'deleted') $message = "<div class='alert alert-success'>File deleted.</div>";
}

$assets = array();
if (is_dir($upload_dir)) {
    foreach (scandir($upload_dir) as $file) {
        if ($file === '.' || $file === '..' || $file === '.htaccess') continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $assets[] = ['name' => $file, 'is_image' => in_array($ext, ['jpg','jpeg','png','gif','webp','svg']), 'full_url' => $full_domain_url . rawurlencode($file)];
    }
}

require_once STAFFINC_DIR.'header.inc.php';
?>

<style>
    .upload-hero {
        border: 2px dashed #cbd5e0;
        border-radius: 12px;
        padding: 50px 20px;
        text-align: center;
        background: #f8fafc;
        transition: all 0.3s ease;
        cursor: pointer;
        margin-bottom: 30px;
    }
    .upload-hero:hover { border-color: #4299e1; background: #ebf8ff; }
    .upload-hero i { font-size: 50px; color: #a0aec0; margin-bottom: 10px; display: block; }
    .upload-hero b { font-size: 18px; color: #2d3748; display: block; }

    /* Modal Styling */
    #custom-modal-overlay { 
        display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
        background:rgba(0,0,0,0.6); z-index:999999; align-items:center; justify-content:center; 
    }
    .custom-modal { background:#fff; padding:30px; border-radius:12px; width:90%; max-width:450px; }
    
    /* Filename Confirmation Box */
    .confirm-box {
        background: #edf2f7;
        padding: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin: 15px 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .confirm-box code { font-weight: bold; color: #2d3748; word-break: break-all; }
    .copy-mini { 
        cursor: pointer; color: #4299e1; padding: 5px; 
        border-radius: 4px; transition: 0.2s; 
    }
    .copy-mini:hover { background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

    .btn-confirm { 
        background: #cbd5e0 !important; color: #fff !important; border: none; 
        padding: 10px 24px; border-radius: 6px; cursor: not-allowed; font-weight: 600; 
        pointer-events: none;
    }
    .btn-confirm.enabled { background: #e53e3e !important; cursor: pointer !important; pointer-events: auto !important; }
    .btn-cancel { background:#edf2f7; color:#4a5568 !important; border:none; padding:10px 24px; border-radius:6px; cursor:pointer; }
    .confirm-input { width: 100%; padding: 12px; margin-top: 10px; border: 2px solid #edf2f7; border-radius: 6px; outline: none; }
    .confirm-input:focus { border-color: #4299e1; }
</style>

<div class="panel" style="max-width:700px; margin:20px auto; padding:0 15px; background:transparent; border:none;">
    <h2 style="margin-bottom:10px;"><i class="icon-picture faded"></i> Asset Manager</h2>
    <?php echo $message; ?>

    <div class="upload-hero" onclick="document.getElementById('file-input').click()">
        <i class="icon-cloud-upload"></i>
        <b id="upload-status">Click to Upload</b>
        <span style="color:#718096;">or Paste (Ctrl+V) from your clipboard</span>
        <form method="post" enctype="multipart/form-data" id="manual-form" style="display:none;">
            <?php csrf_token(); ?>
            <input type="file" name="file" id="file-input" onchange="document.getElementById('manual-form').submit()">
        </form>
    </div>
</div>

<div id="asset-list-container">
    <?php if ($assets) { ?>
    <div class="panel" style="max-width:950px; margin:20px auto; padding:20px; background:#fff; border-radius:12px; border:1px solid #e2e8f0;">
        <table class="list" style="width:100%;">
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>File Name</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach (array_reverse($assets) as $asset) { ?>
                <tr>
                    <td style="width:100px; text-align:center;">
                        <?php if ($asset['is_image']) { ?>
                            <img src="<?php echo $asset['full_url']; ?>" style="max-height:45px; border-radius:6px;">
                        <?php } ?>
                    </td>
                    <td><code><?php echo htmlspecialchars($asset['name']); ?></code></td>
                    <td style="text-align:right;">
                        <button class="button mini" onclick="copyText('<?php echo $asset['full_url']; ?>', this)">Copy URL</button>
                        <button class="button mini" style="color:#e53e3e; border:none; background:none;" onclick="openDeleteModal('<?php echo addslashes($asset['name']); ?>')"><i class="icon-trash"></i></button>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } ?>
</div>

<div id="custom-modal-overlay">
    <div class="custom-modal">
        <h3 style="color:#e53e3e; margin-top:0;">Delete Permanently?</h3>
        <p style="color:#718096; font-size:14px;">Copy and paste the filename below to confirm:</p>
        
        <div class="confirm-box">
            <code id="target-name-display"></code>
            <i class="icon-copy copy-mini" onclick="copyTargetName()" title="Copy Filename"></i>
        </div>
        
        <input type="text" id="delete-confirm-field" class="confirm-input" placeholder="Paste filename here..." autocomplete="off">
        
        <form method="post">
            <?php csrf_token(); ?>
            <input type="hidden" name="do_delete" id="delete-input-hidden">
            <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:25px;">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" id="delete-submit-btn" class="btn-confirm">Delete File</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentDeleteTarget = "";
const confirmInput = document.getElementById('delete-confirm-field');
const deleteBtn = document.getElementById('delete-submit-btn');

// Paste Logic
window.addEventListener('paste', e => {
    const items = e.clipboardData.items;
    for (let i of items) {
        if (i.kind === 'file') {
            const formData = new FormData();
            formData.append('file', i.getAsFile());
            formData.append('__CSRFToken__', document.querySelector('input[name="__CSRFToken__"]').value);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = () => { if (xhr.status === 200) window.location.href = window.location.pathname + "?msg=uploaded"; };
            xhr.send(formData);
        }
    }
});

// Modal Actions
function openDeleteModal(name) {
    currentDeleteTarget = name;
    document.getElementById('target-name-display').innerText = name;
    document.getElementById('delete-input-hidden').value = name;
    confirmInput.value = "";
    deleteBtn.classList.remove('enabled');
    document.getElementById('custom-modal-overlay').style.display = 'flex';
    setTimeout(() => confirmInput.focus(), 100);
}

function copyTargetName() {
    navigator.clipboard.writeText(currentDeleteTarget);
    const icon = document.querySelector('.copy-mini');
    icon.classList.remove('icon-copy');
    icon.classList.add('icon-ok');
    setTimeout(() => {
        icon.classList.remove('icon-ok');
        icon.classList.add('icon-copy');
    }, 1000);
}

confirmInput.addEventListener('input', function() {
    deleteBtn.classList.toggle('enabled', this.value.trim() === currentDeleteTarget);
});

function closeModal() { document.getElementById('custom-modal-overlay').style.display = 'none'; }

function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const old = btn.innerText; btn.innerText = 'Copied!'; setTimeout(() => btn.innerText = old, 1200);
    });
}
</script>

<?php require_once STAFFINC_DIR.'footer.inc.php'; ?>