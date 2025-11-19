<?php
/**
 * Bảo Ngọc GROUP - Hệ thống Quản lý Tài liệu Nội bộ
 * Hệ thống y tế chất lượng cao tại Thái Nguyên
 * 
 * Tính năng: Tự động quét, phân loại và quản lý tài liệu văn phòng
 * 
 * Phân quyền:
 * - PUBLIC: Xem và tải tài liệu (không cần login)
 * - ADMIN: Upload, xoá, quản lý tài liệu (cần login)
 */

session_start();

// Configuration - Cấu hình thư mục cần quét
define('COMPANY_NAME', 'Bảo Ngọc GROUP');
define('COMPANY_DESC', 'Hệ thống y tế chất lượng cao tại Thái Nguyên');
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('DB_FILE', __DIR__ . '/documents.db');

// Admin credentials - Thay đổi username và password tại đây
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'baongoc2025'); // Nên đổi password này!

// Cấu hình các thư mục cần tự động quét
define('SCAN_DIRS', [
    __DIR__ . '/tai-lieu-hanh-chinh',      // Tài liệu hành chính
    __DIR__ . '/tai-lieu-ky-thuat',        // Tài liệu kỹ thuật y tế
    __DIR__ . '/quy-dinh-noi-bo',          // Quy định nội bộ
    __DIR__ . '/bieu-mau',                  // Biểu mẫu
    __DIR__ . '/bao-cao',                   // Báo cáo
    __DIR__ . '/uploads',                   // Upload chung
]);

// Authentication functions
function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin() {
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Cần đăng nhập quản trị viên']);
        exit;
    }
}

// Handle login/logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            $_SESSION['admin_logged_in'] = true;
            echo json_encode(['success' => true, 'message' => 'Đăng nhập thành công']);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'Sai tên đăng nhập hoặc mật khẩu']);
            exit;
        }
    }
    
    if ($_POST['action'] === 'logout') {
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Đã đăng xuất']);
        exit;
    }
}

// Tự động tạo các thư mục nếu chưa tồn tại
foreach (SCAN_DIRS as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Initialize SQLite Database
class DocumentManager {
    private $db;
    
    public function __construct() {
        $this->db = new SQLite3(DB_FILE);
        $this->initDatabase();
    }
    
    private function initDatabase() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                filename TEXT NOT NULL,
                filepath TEXT UNIQUE NOT NULL,
                filesize INTEGER,
                filetype TEXT,
                category TEXT,
                mime_type TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                folder TEXT,
                tags TEXT,
                description TEXT
            )
        ");
        
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_category ON files(category)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_filetype ON files(filetype)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_folder ON files(folder)");
    }
    
    public function categorizeFile($filename, $mimeType) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Phân loại theo loại tài liệu văn phòng
        $categories = [
            'word' => ['doc', 'docx', 'odt', 'rtf'],
            'excel' => ['xls', 'xlsx', 'csv', 'ods'],
            'powerpoint' => ['ppt', 'pptx', 'odp'],
            'pdf' => ['pdf'],
            'text' => ['txt', 'text'],
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'],
            'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
            'other' => []
        ];
        
        foreach ($categories as $category => $extensions) {
            if (in_array($ext, $extensions)) {
                return $category;
            }
        }
        
        return 'other';
    }
    
    public function scanDirectories() {
        $scanned = 0;
        
        foreach (SCAN_DIRS as $dir) {
            if (!is_dir($dir)) continue;
            
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $filepath = $file->getRealPath();
                    $filename = $file->getFilename();
                    $filesize = $file->getSize();
                    $mimeType = mime_content_type($filepath);
                    $category = $this->categorizeFile($filename, $mimeType);
                    $folder = dirname(str_replace(__DIR__, '', $filepath));
                    $ext = strtolower($file->getExtension());
                    
                    $stmt = $this->db->prepare("
                        INSERT OR REPLACE INTO files 
                        (filename, filepath, filesize, filetype, category, mime_type, folder, updated_at)
                        VALUES (:filename, :filepath, :filesize, :filetype, :category, :mime_type, :folder, datetime('now'))
                    ");
                    
                    $stmt->bindValue(':filename', $filename, SQLITE3_TEXT);
                    $stmt->bindValue(':filepath', $filepath, SQLITE3_TEXT);
                    $stmt->bindValue(':filesize', $filesize, SQLITE3_INTEGER);
                    $stmt->bindValue(':filetype', $ext, SQLITE3_TEXT);
                    $stmt->bindValue(':category', $category, SQLITE3_TEXT);
                    $stmt->bindValue(':mime_type', $mimeType, SQLITE3_TEXT);
                    $stmt->bindValue(':folder', $folder, SQLITE3_TEXT);
                    
                    $stmt->execute();
                    $scanned++;
                }
            }
        }
        
        return $scanned;
    }
    
    public function getFiles($category = null, $folder = null, $search = null, $limit = 100, $offset = 0) {
        $sql = "SELECT * FROM files WHERE 1=1";
        
        if ($category && $category != 'all') {
            $sql .= " AND category = :category";
        }
        
        if ($folder && $folder != 'all') {
            $sql .= " AND folder = :folder";
        }
        
        if ($search) {
            $sql .= " AND (filename LIKE :search OR tags LIKE :search OR description LIKE :search)";
        }
        
        $sql .= " ORDER BY updated_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        if ($category && $category != 'all') {
            $stmt->bindValue(':category', $category, SQLITE3_TEXT);
        }
        
        if ($folder && $folder != 'all') {
            $stmt->bindValue(':folder', $folder, SQLITE3_TEXT);
        }
        
        if ($search) {
            $stmt->bindValue(':search', "%$search%", SQLITE3_TEXT);
        }
        
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        $files = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $files[] = $row;
        }
        
        return $files;
    }
    
    public function getFolders() {
        $result = $this->db->query("SELECT DISTINCT folder FROM files ORDER BY folder");
        $folders = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $folders[] = $row['folder'];
        }
        
        return $folders;
    }
    
    public function getFileById($id) {
        $stmt = $this->db->prepare("SELECT * FROM files WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }
    
    public function deleteFile($id) {
        $file = $this->getFileById($id);
        if ($file && file_exists($file['filepath'])) {
            unlink($file['filepath']);
        }
        
        $stmt = $this->db->prepare("DELETE FROM files WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        return $stmt->execute();
    }
    
    public function deleteMultiple($ids) {
        foreach ($ids as $id) {
            $this->deleteFile($id);
        }
        return true;
    }
    
    public function updateDescription($id, $description) {
        $stmt = $this->db->prepare("UPDATE files SET description = :description, updated_at = datetime('now') WHERE id = :id");
        $stmt->bindValue(':description', $description, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        return $stmt->execute();
    }
    
    public function getStats() {
        $result = $this->db->query("
            SELECT 
                category,
                COUNT(*) as count,
                SUM(filesize) as total_size
            FROM files
            GROUP BY category
        ");
        
        $stats = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $stats[] = $row;
        }
        
        return $stats;
    }
    
    public function getStatsByFolder() {
        $result = $this->db->query("
            SELECT 
                folder,
                COUNT(*) as count,
                SUM(filesize) as total_size
            FROM files
            GROUP BY folder
        ");
        
        $stats = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $stats[] = $row;
        }
        
        return $stats;
    }
}

$dm = new DocumentManager();

// Tự động quét files khi lần đầu truy cập (nếu database rỗng)
$result = $dm->getStats();
if (empty($result)) {
    $dm->scanDirectories();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'check_auth':
            echo json_encode(['success' => true, 'isAdmin' => isAdmin()]);
            break;
            
        case 'scan':
            requireAdmin(); // Chỉ admin mới được quét
            $count = $dm->scanDirectories();
            echo json_encode(['success' => true, 'count' => $count]);
            break;
            
        case 'upload':
            requireAdmin(); // Chỉ admin mới được upload
            if (isset($_FILES['files'])) {
                $uploaded = [];
                foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                    $filename = basename($_FILES['files']['name'][$key]);
                    $target = UPLOAD_DIR . '/' . $filename;
                    
                    if (move_uploaded_file($tmp_name, $target)) {
                        $uploaded[] = $filename;
                    }
                }
                $dm->scanDirectories();
                echo json_encode(['success' => true, 'files' => $uploaded]);
            }
            break;
            
        case 'delete':
            requireAdmin(); // Chỉ admin mới được xoá
            $id = intval($_POST['id']);
            $dm->deleteFile($id);
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_multiple':
            requireAdmin(); // Chỉ admin mới được xoá hàng loạt
            $ids = json_decode($_POST['ids'], true);
            $dm->deleteMultiple($ids);
            echo json_encode(['success' => true]);
            break;
            
        case 'update_description':
            requireAdmin(); // Chỉ admin mới được cập nhật
            $id = intval($_POST['id']);
            $description = $_POST['description'];
            $dm->updateDescription($id, $description);
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'list':
            $category = $_GET['category'] ?? null;
            $folder = $_GET['folder'] ?? null;
            $search = $_GET['search'] ?? null;
            $files = $dm->getFiles($category, $folder, $search);
            echo json_encode(['success' => true, 'files' => $files]);
            break;
            
        case 'folders':
            $folders = $dm->getFolders();
            echo json_encode(['success' => true, 'folders' => $folders]);
            break;
            
        case 'stats':
            $stats = $dm->getStats();
            $folderStats = $dm->getStatsByFolder();
            echo json_encode(['success' => true, 'stats' => $stats, 'folderStats' => $folderStats]);
            break;
            
        case 'get_file':
            $id = intval($_GET['id']);
            $file = $dm->getFileById($id);
            if ($file && file_exists($file['filepath'])) {
                // Chỉ đọc content cho text files
                $textCategories = ['text', 'word', 'excel', 'powerpoint'];
                $textExtensions = ['txt', 'html', 'css', 'js', 'json', 'xml', 'csv', 'md'];
                $ext = strtolower($file['filetype']);
                
                $content = null;
                if (in_array($file['category'], $textCategories) || in_array($ext, $textExtensions)) {
                    // Chỉ đọc file nhỏ hơn 1MB
                    if ($file['filesize'] < 1048576) {
                        $content = file_get_contents($file['filepath']);
                    }
                }
                
                echo json_encode(['success' => true, 'file' => $file, 'content' => $content]);
            } else {
                echo json_encode(['success' => false, 'error' => 'File not found']);
            }
            break;
            
        case 'preview':
            $id = intval($_GET['id']);
            $file = $dm->getFileById($id);
            if ($file && file_exists($file['filepath'])) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['filepath']);
                finfo_close($finfo);
                
                header('Content-Type: ' . $mime);
                header('Content-Length: ' . filesize($file['filepath']));
                readfile($file['filepath']);
            }
            break;
            
        case 'download':
            $id = intval($_GET['id']);
            $file = $dm->getFileById($id);
            if ($file && file_exists($file['filepath'])) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($file['filename']) . '"');
                header('Content-Length: ' . filesize($file['filepath']));
                readfile($file['filepath']);
            }
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= COMPANY_NAME ?> - Hệ thống Quản lý Tài liệu</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        header h1 {
            font-size: 2.2em;
            margin-bottom: 10px;
        }
        
        header p {
            font-size: 1.1em;
            opacity: 0.95;
        }
        
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            padding: 20px 30px;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            align-items: center;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #0066cc;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0052a3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 102, 204, 0.4);
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #0066cc;
        }
        
        .filters {
            padding: 20px 30px;
            background: white;
            border-bottom: 2px solid #e9ecef;
        }
        
        .filter-section {
            margin-bottom: 15px;
        }
        
        .filter-section h3 {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #e9ecef;
            border-radius: 20px;
            background: white;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .filter-btn.active {
            background: #0066cc;
            color: white;
            border-color: #0066cc;
        }
        
        .filter-btn:hover {
            border-color: #0066cc;
            transform: translateY(-2px);
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px 30px;
            background: #f8f9fa;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card .number {
            font-size: 2em;
            font-weight: bold;
            color: #0066cc;
        }
        
        .stat-card .label {
            color: #6c757d;
            margin-top: 5px;
            font-size: 13px;
        }
        
        .file-table-container {
            padding: 0 30px 30px 30px;
            overflow-x: auto;
        }
        
        .file-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .file-table thead {
            background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%);
            color: white;
        }
        
        .file-table th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 2px solid #0052a3;
        }
        
        .file-table th.center {
            text-align: center;
        }
        
        .file-table tbody tr {
            border-bottom: 1px solid #e9ecef;
            transition: all 0.2s;
        }
        
        .file-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .file-table tbody tr.selected {
            background: #e6f2ff;
        }
        
        .file-table td {
            padding: 12px;
            font-size: 14px;
        }
        
        .file-icon {
            font-size: 24px;
            text-align: center;
        }
        
        .file-name-cell {
            font-weight: 600;
            color: #212529;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .file-name-cell:hover {
            color: #0066cc;
            cursor: pointer;
        }
        
        .file-size {
            color: #6c757d;
            font-size: 13px;
        }
        
        .file-category-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-pdf { background: #ffe6e6; color: #cc0000; }
        .badge-word { background: #e6f2ff; color: #0066cc; }
        .badge-excel { background: #e6ffe6; color: #009900; }
        .badge-powerpoint { background: #fff4e6; color: #ff8800; }
        .badge-image { background: #f0e6ff; color: #6600cc; }
        .badge-text { background: #e6e6e6; color: #333333; }
        .badge-archive { background: #fff0e6; color: #cc6600; }
        .badge-other { background: #f8f9fa; color: #6c757d; }
        
        .file-folder-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 11px;
            background: #e9ecef;
            color: #495057;
        }
        
        .file-actions-cell {
            text-align: center;
            white-space: nowrap;
        }
        
        .file-actions-cell button {
            padding: 6px 10px;
            margin: 0 2px;
            border: none;
            background: #f8f9fa;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s;
        }
        
        .file-actions-cell button:hover {
            background: #e9ecef;
            transform: scale(1.1);
        }
        
        .checkbox-cell {
            text-align: center;
            width: 40px;
        }
        
        .checkbox-cell input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .checkbox {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 24px;
            height: 24px;
            cursor: pointer;
            z-index: 10;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 90%;
            max-height: 90%;
            overflow: auto;
            position: relative;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .close-modal {
            background: #ef4444;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            font-weight: bold;
        }
        
        .preview-container {
            max-width: 1000px;
            max-height: 80vh;
        }
        
        .preview-container img,
        .preview-container video {
            max-width: 100%;
            max-height: 70vh;
        }
        
        .preview-container iframe {
            width: 100%;
            height: 70vh;
            border: none;
        }
        
        .loading {
            text-align: center;
            padding: 50px;
            font-size: 18px;
            color: #6c757d;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #6c757d;
        }
        
        .empty-state-icon {
            font-size: 5em;
            margin-bottom: 20px;
        }
        
        input[type="file"] {
            display: none;
        }
        
        .file-upload-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #10b981;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .file-upload-label:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .auto-scan-notice {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 12px 20px;
            margin: 20px 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-panel {
            background: #e7f3ff;
            border: 2px solid #0066cc;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #004085;
            font-weight: 600;
        }
        
        .login-btn {
            background: #0066cc;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .login-btn:hover {
            background: #0052a3;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: #c82333;
        }
        
        .login-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .login-form input {
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .login-form input:focus {
            outline: none;
            border-color: #0066cc;
        }
        
        .hidden {
            display: none !important;
        }
        
        .admin-only {
            display: none;
        }
        
        .admin-mode .admin-only {
            display: inline-flex;
        }
        
        @media (max-width: 768px) {
            .file-table-container {
                padding: 0 15px 15px 15px;
            }
            
            .file-table th,
            .file-table td {
                padding: 8px 6px;
                font-size: 12px;
            }
            
            .file-name-cell {
                max-width: 150px;
            }
            
            .file-table th:nth-child(5),
            .file-table td:nth-child(5),
            .file-table th:nth-child(6),
            .file-table td:nth-child(6) {
                display: none; /* Ẩn size và folder trên mobile */
            }
            
            .toolbar {
                padding: 15px;
            }
            
            header h1 {
                font-size: 1.6em;
            }
            
            .file-actions-cell button {
                padding: 4px 8px;
                font-size: 14px;
                margin: 0 1px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🏥 <?= COMPANY_NAME ?></h1>
            <p><?= COMPANY_DESC ?></p>
            <p style="font-size: 0.9em; margin-top: 8px;">📋 Hệ thống Quản lý Tài liệu Nội bộ</p>
        </header>
        
        <!-- Admin Panel -->
        <div class="admin-panel" id="adminPanel">
            <div id="loginArea">
                <div class="login-form">
                    <input type="text" id="username" placeholder="Tên đăng nhập" />
                    <input type="password" id="password" placeholder="Mật khẩu" />
                    <button class="login-btn" onclick="login()">🔐 Đăng nhập Quản trị</button>
                </div>
            </div>
            <div id="loggedInArea" class="hidden">
                <div class="admin-info">
                    <span>👤 Quản trị viên</span>
                    <button class="logout-btn" onclick="logout()">🚪 Đăng xuất</button>
                </div>
            </div>
        </div>
        
        <div class="auto-scan-notice">
            <span style="font-size: 1.5em;">ℹ️</span>
            <span><strong>Quét tự động:</strong> Hệ thống đã tự động quét và cập nhật tài liệu từ các thư mục được cấu hình.</span>
        </div>
        
        <div class="toolbar" id="toolbar">
            <button class="btn btn-primary admin-only" onclick="scanFiles()">
                🔄 Quét lại Tài liệu
            </button>
            
            <label class="file-upload-label admin-only">
                📤 Tải lên Tài liệu
                <input type="file" id="fileInput" multiple onchange="uploadFiles()">
            </label>
            
            <button class="btn btn-danger admin-only" onclick="deleteSelected()" id="deleteBtn" style="display:none;">
                🗑️ Xoá đã chọn
            </button>
            
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Tìm kiếm tài liệu..." onkeyup="searchFiles()">
            </div>
        </div>
        
        <div class="filters">
            <div class="filter-section">
                <h3>📁 Loại tài liệu</h3>
                <div class="filter-buttons">
                    <button class="filter-btn active" data-category="all" onclick="filterCategory('all')">
                        📂 Tất cả
                    </button>
                    <button class="filter-btn" data-category="pdf" onclick="filterCategory('pdf')">
                        📕 PDF
                    </button>
                    <button class="filter-btn" data-category="word" onclick="filterCategory('word')">
                        📘 Word
                    </button>
                    <button class="filter-btn" data-category="excel" onclick="filterCategory('excel')">
                        📗 Excel
                    </button>
                    <button class="filter-btn" data-category="powerpoint" onclick="filterCategory('powerpoint')">
                        📙 PowerPoint
                    </button>
                    <button class="filter-btn" data-category="image" onclick="filterCategory('image')">
                        🖼️ Hình ảnh
                    </button>
                    <button class="filter-btn" data-category="archive" onclick="filterCategory('archive')">
                        📦 Nén
                    </button>
                </div>
            </div>
            
            <div class="filter-section" id="folderFilterSection">
                <h3>📂 Thư mục</h3>
                <div class="filter-buttons" id="folderFilters">
                    <button class="filter-btn active" data-folder="all" onclick="filterFolder('all')">
                        📁 Tất cả thư mục
                    </button>
                </div>
            </div>
        </div>
        
        <div class="stats" id="statsContainer"></div>
        
        <div class="file-table-container">
            <table class="file-table" id="fileTable">
                <thead>
                    <tr>
                        <th class="checkbox-cell admin-only">☑️</th>
                        <th class="center">Icon</th>
                        <th>Tên tài liệu</th>
                        <th>Loại</th>
                        <th>Dung lượng</th>
                        <th>Thư mục</th>
                        <th class="center">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="fileTableBody">
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 50px;">
                            <div class="loading">Đang tải tài liệu...</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Preview Modal -->
    <div class="modal" id="previewModal">
        <div class="modal-content preview-container">
            <div class="modal-header">
                <h2 id="previewTitle">Xem tài liệu</h2>
                <button class="close-modal" onclick="closeModal('previewModal')">×</button>
            </div>
            <div class="modal-body" id="previewBody"></div>
        </div>
    </div>
    
    <script>
        let currentCategory = 'all';
        let currentFolder = 'all';
        let currentSearch = '';
        let selectedFiles = new Set();
        let isAdminMode = false;
        
        // Icons for file types
        const fileIcons = {
            pdf: '📕',
            word: '📘',
            excel: '📗',
            powerpoint: '📙',
            text: '📝',
            image: '🖼️',
            archive: '📦',
            other: '📎'
        };
        
        // Load files on page load
        window.addEventListener('DOMContentLoaded', () => {
            checkAuth();
            loadFiles();
            loadStats();
            loadFolders();
        });
        
        // Check authentication status
        async function checkAuth() {
            try {
                const formData = new FormData();
                formData.append('action', 'check_auth');
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success && data.isAdmin) {
                    isAdminMode = true;
                    showAdminMode();
                } else {
                    isAdminMode = false;
                    showPublicMode();
                }
            } catch (error) {
                console.error('Auth check error:', error);
                showPublicMode();
            }
        }
        
        // Login function
        async function login() {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                alert('⚠️ Vui lòng nhập tên đăng nhập và mật khẩu!');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('username', username);
            formData.append('password', password);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ ' + data.message);
                    isAdminMode = true;
                    showAdminMode();
                    document.getElementById('username').value = '';
                    document.getElementById('password').value = '';
                } else {
                    alert('❌ ' + data.error);
                }
            } catch (error) {
                console.error('Login error:', error);
                alert('❌ Lỗi khi đăng nhập!');
            }
        }
        
        // Logout function
        async function logout() {
            if (!confirm('Bạn có chắc muốn đăng xuất?')) return;
            
            const formData = new FormData();
            formData.append('action', 'logout');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ ' + data.message);
                    isAdminMode = false;
                    showPublicMode();
                    selectedFiles.clear();
                    updateSelectedUI();
                }
            } catch (error) {
                console.error('Logout error:', error);
            }
        }
        
        // Show admin mode UI
        function showAdminMode() {
            document.getElementById('loginArea').classList.add('hidden');
            document.getElementById('loggedInArea').classList.remove('hidden');
            document.getElementById('toolbar').classList.add('admin-mode');
            
            // Hiện checkboxes
            document.querySelectorAll('.checkbox').forEach(cb => {
                cb.style.display = 'block';
            });
        }
        
        // Show public mode UI
        function showPublicMode() {
            document.getElementById('loginArea').classList.remove('hidden');
            document.getElementById('loggedInArea').classList.add('hidden');
            document.getElementById('toolbar').classList.remove('admin-mode');
            
            // Ẩn checkboxes
            document.querySelectorAll('.checkbox').forEach(cb => {
                cb.style.display = 'none';
            });
            
            // Ẩn nút delete
            document.getElementById('deleteBtn').style.display = 'none';
        }
        
        // Enter key for login
        document.addEventListener('DOMContentLoaded', () => {
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        login();
                    }
                });
            }
        });
        
        async function scanFiles() {
            if (!confirm('Bạn muốn quét lại tất cả tài liệu từ các thư mục?')) return;
            
            const formData = new FormData();
            formData.append('action', 'scan');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    alert(`✅ Đã quét ${data.count} tài liệu!`);
                    loadFiles();
                    loadStats();
                    loadFolders();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Lỗi khi quét tài liệu!');
            }
        }
        
        async function uploadFiles() {
            const fileInput = document.getElementById('fileInput');
            const files = fileInput.files;
            
            if (files.length === 0) return;
            
            const formData = new FormData();
            formData.append('action', 'upload');
            
            for (let i = 0; i < files.length; i++) {
                formData.append('files[]', files[i]);
            }
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    alert(`✅ Đã tải lên ${data.files.length} tài liệu!`);
                    loadFiles();
                    loadStats();
                    fileInput.value = '';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Lỗi khi tải lên tài liệu!');
            }
        }
        
        async function loadFiles() {
            const tableBody = document.getElementById('fileTableBody');
            tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 50px;"><div class="loading">Đang tải tài liệu...</div></td></tr>';
            
            try {
                const response = await fetch(`?action=list&category=${currentCategory}&folder=${encodeURIComponent(currentFolder)}&search=${encodeURIComponent(currentSearch)}`);
                const data = await response.json();
                
                if (data.success && data.files.length > 0) {
                    tableBody.innerHTML = data.files.map(file => createFileRow(file)).join('');
                } else {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 80px 20px;">
                                <div class="empty-state">
                                    <div class="empty-state-icon">📂</div>
                                    <h2>Không có tài liệu</h2>
                                    <p>Nhấn "Quét lại Tài liệu" để quét từ các thư mục hoặc tải lên tài liệu mới</p>
                                </div>
                            </td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 50px;">❌ Lỗi khi tải tài liệu!</td></tr>';
            }
        }
        
        function createFileRow(file) {
            const icon = fileIcons[file.category] || '📎';
            const size = formatFileSize(file.filesize);
            const folderName = file.folder.split('/').pop() || 'uploads';
            
            // Chỉ hiện checkbox và nút xoá cho admin
            const checkboxHtml = isAdminMode ? 
                `<td class="checkbox-cell"><input type="checkbox" onchange="toggleSelect(${file.id})" ${selectedFiles.has(file.id) ? 'checked' : ''}></td>` : 
                '<td class="checkbox-cell"></td>';
            
            const deleteButtonHtml = isAdminMode ? 
                `<button onclick="deleteFile(${file.id})" title="Xoá">🗑️</button>` : '';
            
            // Badge class theo category
            const badgeClass = `badge-${file.category}`;
            
            return `
                <tr class="${selectedFiles.has(file.id) ? 'selected' : ''}" data-id="${file.id}">
                    ${checkboxHtml}
                    <td class="file-icon">${icon}</td>
                    <td class="file-name-cell" onclick="previewFile(${file.id})" title="${file.filename}">
                        ${file.filename}
                    </td>
                    <td>
                        <span class="file-category-badge ${badgeClass}">${file.category}</span>
                    </td>
                    <td class="file-size">${size}</td>
                    <td>
                        <span class="file-folder-badge">${folderName}</span>
                    </td>
                    <td class="file-actions-cell">
                        <button onclick="previewFile(${file.id})" title="Xem">👁️</button>
                        <button onclick="downloadFile(${file.id})" title="Tải về">⬇️</button>
                        ${deleteButtonHtml}
                    </td>
                </tr>
            `;
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
        
        async function loadStats() {
            try {
                const response = await fetch('?action=stats');
                const data = await response.json();
                
                if (data.success) {
                    const statsContainer = document.getElementById('statsContainer');
                    const totalFiles = data.stats.reduce((sum, stat) => sum + stat.count, 0);
                    const totalSize = data.stats.reduce((sum, stat) => sum + stat.total_size, 0);
                    
                    let statsHtml = `
                        <div class="stat-card">
                            <div class="number">${totalFiles}</div>
                            <div class="label">Tổng số tài liệu</div>
                        </div>
                        <div class="stat-card">
                            <div class="number">${formatFileSize(totalSize)}</div>
                            <div class="label">Tổng dung lượng</div>
                        </div>
                    `;
                    
                    data.stats.forEach(stat => {
                        statsHtml += `
                            <div class="stat-card">
                                <div class="number">${stat.count}</div>
                                <div class="label">${fileIcons[stat.category]} ${stat.category.toUpperCase()}</div>
                            </div>
                        `;
                    });
                    
                    statsContainer.innerHTML = statsHtml;
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        async function loadFolders() {
            try {
                const response = await fetch('?action=folders');
                const data = await response.json();
                
                if (data.success && data.folders.length > 0) {
                    const folderFilters = document.getElementById('folderFilters');
                    let html = `
                        <button class="filter-btn active" data-folder="all" onclick="filterFolder('all')">
                            📁 Tất cả thư mục
                        </button>
                    `;
                    
                    data.folders.forEach(folder => {
                        const folderName = folder.split('/').pop() || 'root';
                        html += `
                            <button class="filter-btn" data-folder="${folder}" onclick="filterFolder('${folder}')">
                                📂 ${folderName}
                            </button>
                        `;
                    });
                    
                    folderFilters.innerHTML = html;
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        function filterCategory(category) {
            currentCategory = category;
            
            document.querySelectorAll('[data-category]').forEach(btn => {
                btn.classList.remove('active');
            });
            
            event.target.classList.add('active');
            loadFiles();
        }
        
        function filterFolder(folder) {
            currentFolder = folder;
            
            document.querySelectorAll('[data-folder]').forEach(btn => {
                btn.classList.remove('active');
            });
            
            event.target.classList.add('active');
            loadFiles();
        }
        
        function searchFiles() {
            currentSearch = document.getElementById('searchInput').value;
            loadFiles();
        }
        
        function toggleSelect(id) {
            if (selectedFiles.has(id)) {
                selectedFiles.delete(id);
            } else {
                selectedFiles.add(id);
            }
            
            updateSelectedUI();
        }
        
        function updateSelectedUI() {
            const deleteBtn = document.getElementById('deleteBtn');
            deleteBtn.style.display = selectedFiles.size > 0 ? 'inline-flex' : 'none';
            
            // Update table rows
            document.querySelectorAll('#fileTableBody tr[data-id]').forEach(row => {
                const id = parseInt(row.dataset.id);
                if (selectedFiles.has(id)) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            });
        }
        
        async function previewFile(id) {
            try {
                const response = await fetch(`?action=get_file&id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const modal = document.getElementById('previewModal');
                    const title = document.getElementById('previewTitle');
                    const body = document.getElementById('previewBody');
                    
                    title.textContent = data.file.filename;
                    
                    let previewHtml = '';
                    const file = data.file;
                    const ext = file.filetype.toLowerCase();
                    
                    // Preview cho hình ảnh
                    if (file.category === 'image' || ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(ext)) {
                        previewHtml = `
                            <div style="text-align: center; padding: 20px;">
                                <img src="?action=preview&id=${id}" alt="${file.filename}" style="max-width: 100%; max-height: 70vh; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                <p style="margin-top: 15px; color: #6c757d;">
                                    ${formatFileSize(file.filesize)} • ${ext.toUpperCase()}
                                </p>
                            </div>
                        `;
                    } 
                    // Preview cho PDF
                    else if (file.category === 'pdf' || ext === 'pdf') {
                        previewHtml = `
                            <div style="text-align: center;">
                                <iframe src="?action=preview&id=${id}#toolbar=1&navpanes=1&scrollbar=1" style="width: 100%; height: 70vh; border: 1px solid #e9ecef; border-radius: 8px;"></iframe>
                                <p style="margin-top: 15px;">
                                    <a href="?action=download&id=${id}" class="btn btn-primary" style="text-decoration: none;">⬇️ Tải về PDF</a>
                                </p>
                            </div>
                        `;
                    }
                    // Preview cho text files
                    else if (data.content && (file.category === 'text' || ['txt', 'html', 'css', 'js', 'json', 'xml', 'csv', 'md'].includes(ext))) {
                        const escapedContent = escapeHtml(data.content);
                        previewHtml = `
                            <div style="padding: 20px;">
                                <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; max-height: 60vh; overflow: auto;">
                                    <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word; font-family: 'Courier New', monospace; font-size: 14px;">${escapedContent}</pre>
                                </div>
                                <p style="margin-top: 15px; text-align: center;">
                                    <a href="?action=download&id=${id}" class="btn btn-primary" style="text-decoration: none;">⬇️ Tải về file</a>
                                </p>
                            </div>
                        `;
                    }
                    // Preview cho HTML
                    else if (ext === 'html' || ext === 'htm') {
                        previewHtml = `
                            <div style="padding: 20px;">
                                <iframe src="?action=preview&id=${id}" style="width: 100%; height: 60vh; border: 1px solid #e9ecef; border-radius: 8px;"></iframe>
                                <p style="margin-top: 15px; text-align: center;">
                                    <a href="?action=download&id=${id}" class="btn btn-primary" style="text-decoration: none;">⬇️ Tải về HTML</a>
                                </p>
                            </div>
                        `;
                    }
                    // Preview cho Word, Excel, PowerPoint (Office files)
                    else if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp'].includes(ext)) {
                        const icon = file.category === 'word' ? '📘' : file.category === 'excel' ? '📗' : '📙';
                        const typeName = file.category === 'word' ? 'Word' : file.category === 'excel' ? 'Excel' : 'PowerPoint';
                        
                        // Tạo URL public cho file
                        const fileUrl = encodeURIComponent(window.location.origin + window.location.pathname + '?action=preview&id=' + id);
                        
                        // Google Docs Viewer
                        const googleViewerUrl = `https://docs.google.com/viewer?url=${fileUrl}&embedded=true`;
                        
                        // Microsoft Office Online Viewer (backup)
                        const officeViewerUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${fileUrl}`;
                        
                        previewHtml = `
                            <div style="padding: 20px;">
                                <div style="margin-bottom: 15px; padding: 12px; background: #e7f3ff; border-radius: 8px; text-align: center;">
                                    <span style="color: #004085;">
                                        ${icon} <strong>${file.filename}</strong> • ${formatFileSize(file.filesize)}
                                    </span>
                                </div>
                                
                                <div style="border: 2px solid #e9ecef; border-radius: 8px; overflow: hidden; margin-bottom: 15px;">
                                    <iframe src="${googleViewerUrl}" style="width: 100%; height: 70vh; border: none;"></iframe>
                                </div>
                                
                                <div style="text-align: center; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                                    <button onclick="switchOfficeViewer(${id}, 'google')" class="btn btn-primary" style="font-size: 14px;">
                                        🔄 Google Viewer
                                    </button>
                                    <button onclick="switchOfficeViewer(${id}, 'microsoft')" class="btn btn-primary" style="font-size: 14px;">
                                        🔄 Microsoft Viewer
                                    </button>
                                    <a href="?action=download&id=${id}" class="btn btn-success" style="text-decoration: none; font-size: 14px;">
                                        ⬇️ Tải về ${typeName}
                                    </a>
                                </div>
                                
                                <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 8px; font-size: 13px; text-align: center; color: #856404;">
                                    💡 <strong>Lưu ý:</strong> Nếu không hiển thị, thử chuyển viewer hoặc tải về để xem
                                </div>
                            </div>
                        `;
                    }
                    // Preview cho Archive files
                    else if (file.category === 'archive' || ['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) {
                        previewHtml = `
                            <div style="text-align: center; padding: 40px;">
                                <div style="font-size: 5em; margin-bottom: 20px;">📦</div>
                                <h3>${file.filename}</h3>
                                <p style="color: #6c757d; margin: 15px 0;">
                                    File nén • Dung lượng: ${formatFileSize(file.filesize)}
                                </p>
                                <div style="margin: 20px 0; padding: 15px; background: #e7f3ff; border-radius: 8px; max-width: 500px; margin-left: auto; margin-right: auto;">
                                    <p style="margin: 0; color: #004085;">
                                        ℹ️ File nén cần giải nén để xem nội dung bên trong
                                    </p>
                                </div>
                                <a href="?action=download&id=${id}" class="btn btn-primary" style="text-decoration: none; font-size: 16px; padding: 12px 24px;">
                                    ⬇️ Tải về file nén
                                </a>
                            </div>
                        `;
                    }
                    // Default: Không preview được
                    else {
                        const icon = fileIcons[file.category] || '📎';
                        previewHtml = `
                            <div style="text-align: center; padding: 40px;">
                                <div style="font-size: 5em; margin-bottom: 20px;">${icon}</div>
                                <h3>${file.filename}</h3>
                                <p style="color: #6c757d; margin: 15px 0;">
                                    Loại: ${file.category.toUpperCase()} • Dung lượng: ${formatFileSize(file.filesize)}
                                </p>
                                <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; max-width: 500px; margin-left: auto; margin-right: auto;">
                                    <p style="margin: 0; color: #6c757d;">
                                        ℹ️ Loại file này không hỗ trợ preview trực tiếp
                                    </p>
                                </div>
                                <a href="?action=download&id=${id}" class="btn btn-primary" style="text-decoration: none; font-size: 16px; padding: 12px 24px;">
                                    ⬇️ Tải về tài liệu
                                </a>
                            </div>
                        `;
                    }
                    
                    body.innerHTML = previewHtml;
                    modal.classList.add('active');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Lỗi khi xem tài liệu!');
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Switch Office document viewer
        function switchOfficeViewer(id, viewer) {
            const fileUrl = encodeURIComponent(window.location.origin + window.location.pathname + '?action=preview&id=' + id);
            const iframe = document.querySelector('#previewBody iframe');
            
            if (iframe) {
                if (viewer === 'google') {
                    iframe.src = `https://docs.google.com/viewer?url=${fileUrl}&embedded=true`;
                } else if (viewer === 'microsoft') {
                    iframe.src = `https://view.officeapps.live.com/op/embed.aspx?src=${fileUrl}`;
                }
            }
        }
        
        async function deleteFile(id) {
            if (!confirm('Bạn có chắc muốn xoá tài liệu này?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Đã xoá tài liệu!');
                    loadFiles();
                    loadStats();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Lỗi khi xoá tài liệu!');
            }
        }
        
        async function deleteSelected() {
            if (selectedFiles.size === 0) return;
            
            if (!confirm(`Bạn có chắc muốn xoá ${selectedFiles.size} tài liệu đã chọn?`)) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_multiple');
            formData.append('ids', JSON.stringify([...selectedFiles]));
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    alert(`✅ Đã xoá ${selectedFiles.size} tài liệu!`);
                    selectedFiles.clear();
                    updateSelectedUI();
                    loadFiles();
                    loadStats();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Lỗi khi xoá tài liệu!');
            }
        }
        
        function downloadFile(id) {
            window.location.href = `?action=download&id=${id}`;
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // Close modal on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
