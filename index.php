<?php
/**
 * File Manager Web App - One Page Application
 * Features: Auto file scanning, batch operations, auto categorization, preview, online edit
 */

// Configuration
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('DB_FILE', __DIR__ . '/filemanager.db');
define('SCAN_DIRS', [
    __DIR__ . '/uploads',
    __DIR__ . '/documents',
    __DIR__ . '/media'
]);

// Create necessary directories
foreach (SCAN_DIRS as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Initialize SQLite Database
class FileManager {
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
                tags TEXT
            )
        ");
        
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_category ON files(category)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_filetype ON files(filetype)");
    }
    
    public function categorizeFile($filename, $mimeType) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $categories = [
            'images' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico'],
            'videos' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'],
            'audio' => ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac'],
            'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'odt'],
            'archives' => ['zip', 'rar', '7z', 'tar', 'gz', 'bz2'],
            'code' => ['php', 'html', 'css', 'js', 'json', 'xml', 'py', 'java', 'c', 'cpp', 'sql'],
            'others' => []
        ];
        
        foreach ($categories as $category => $extensions) {
            if (in_array($ext, $extensions)) {
                return $category;
            }
        }
        
        return 'others';
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
    
    public function getFiles($category = null, $search = null, $limit = 100, $offset = 0) {
        $sql = "SELECT * FROM files WHERE 1=1";
        
        if ($category && $category != 'all') {
            $sql .= " AND category = :category";
        }
        
        if ($search) {
            $sql .= " AND (filename LIKE :search OR tags LIKE :search)";
        }
        
        $sql .= " ORDER BY updated_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        if ($category && $category != 'all') {
            $stmt->bindValue(':category', $category, SQLITE3_TEXT);
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
    
    public function updateTags($id, $tags) {
        $stmt = $this->db->prepare("UPDATE files SET tags = :tags, updated_at = datetime('now') WHERE id = :id");
        $stmt->bindValue(':tags', $tags, SQLITE3_TEXT);
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
}

$fm = new FileManager();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'scan':
            $count = $fm->scanDirectories();
            echo json_encode(['success' => true, 'count' => $count]);
            break;
            
        case 'upload':
            if (isset($_FILES['files'])) {
                $uploaded = [];
                foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                    $filename = basename($_FILES['files']['name'][$key]);
                    $target = UPLOAD_DIR . '/' . $filename;
                    
                    if (move_uploaded_file($tmp_name, $target)) {
                        $uploaded[] = $filename;
                    }
                }
                $fm->scanDirectories();
                echo json_encode(['success' => true, 'files' => $uploaded]);
            }
            break;
            
        case 'delete':
            $id = intval($_POST['id']);
            $fm->deleteFile($id);
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_multiple':
            $ids = json_decode($_POST['ids'], true);
            $fm->deleteMultiple($ids);
            echo json_encode(['success' => true]);
            break;
            
        case 'update_tags':
            $id = intval($_POST['id']);
            $tags = $_POST['tags'];
            $fm->updateTags($id, $tags);
            echo json_encode(['success' => true]);
            break;
            
        case 'save_file':
            $id = intval($_POST['id']);
            $content = $_POST['content'];
            $file = $fm->getFileById($id);
            if ($file && file_exists($file['filepath'])) {
                file_put_contents($file['filepath'], $content);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'File not found']);
            }
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
            $search = $_GET['search'] ?? null;
            $files = $fm->getFiles($category, $search);
            echo json_encode(['success' => true, 'files' => $files]);
            break;
            
        case 'stats':
            $stats = $fm->getStats();
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        case 'get_file':
            $id = intval($_GET['id']);
            $file = $fm->getFileById($id);
            if ($file && file_exists($file['filepath'])) {
                $content = file_get_contents($file['filepath']);
                echo json_encode(['success' => true, 'file' => $file, 'content' => $content]);
            } else {
                echo json_encode(['success' => false, 'error' => 'File not found']);
            }
            break;
            
        case 'preview':
            $id = intval($_GET['id']);
            $file = $fm->getFileById($id);
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
            $file = $fm->getFileById($id);
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
    <title>File Manager Pro - One Page App</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
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
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
            border-color: #667eea;
        }
        
        .categories {
            display: flex;
            gap: 10px;
            padding: 20px 30px;
            background: white;
            border-bottom: 2px solid #e9ecef;
            overflow-x: auto;
        }
        
        .category-btn {
            padding: 10px 20px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            background: white;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .category-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .category-btn:hover {
            border-color: #667eea;
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
            color: #667eea;
        }
        
        .stat-card .label {
            color: #6c757d;
            margin-top: 5px;
        }
        
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            padding: 30px;
        }
        
        .file-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .file-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .file-card.selected {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        
        .file-preview {
            height: 150px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3em;
            color: #667eea;
        }
        
        .file-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .file-info {
            padding: 15px;
        }
        
        .file-name {
            font-weight: 600;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .file-meta {
            font-size: 12px;
            color: #6c757d;
        }
        
        .file-actions {
            display: flex;
            gap: 5px;
            padding: 10px;
            border-top: 1px solid #e9ecef;
        }
        
        .file-actions button {
            flex: 1;
            padding: 8px;
            border: none;
            background: #f8f9fa;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .file-actions button:hover {
            background: #e9ecef;
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
        
        .editor-container {
            width: 800px;
            max-width: 100%;
        }
        
        .editor-textarea {
            width: 100%;
            height: 500px;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            resize: vertical;
        }
        
        .preview-container {
            max-width: 1000px;
            max-height: 80vh;
        }
        
        .preview-container img,
        .preview-container video,
        .preview-container audio {
            max-width: 100%;
            max-height: 70vh;
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
        
        @media (max-width: 768px) {
            .file-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
                padding: 15px;
            }
            
            .toolbar {
                padding: 15px;
            }
            
            header h1 {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📁 File Manager Pro</h1>
            <p>Quản lý files thông minh với tự động phân loại và preview</p>
        </header>
        
        <div class="toolbar">
            <button class="btn btn-primary" onclick="scanFiles()">
                🔄 Quét Files
            </button>
            
            <label class="file-upload-label">
                📤 Tải lên
                <input type="file" id="fileInput" multiple onchange="uploadFiles()">
            </label>
            
            <button class="btn btn-danger" onclick="deleteSelected()" id="deleteBtn" style="display:none;">
                🗑️ Xoá đã chọn
            </button>
            
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Tìm kiếm files..." onkeyup="searchFiles()">
            </div>
        </div>
        
        <div class="categories">
            <button class="category-btn active" data-category="all" onclick="filterCategory('all')">
                📂 Tất cả
            </button>
            <button class="category-btn" data-category="images" onclick="filterCategory('images')">
                🖼️ Hình ảnh
            </button>
            <button class="category-btn" data-category="videos" onclick="filterCategory('videos')">
                🎬 Video
            </button>
            <button class="category-btn" data-category="audio" onclick="filterCategory('audio')">
                🎵 Audio
            </button>
            <button class="category-btn" data-category="documents" onclick="filterCategory('documents')">
                📄 Tài liệu
            </button>
            <button class="category-btn" data-category="code" onclick="filterCategory('code')">
                💻 Code
            </button>
            <button class="category-btn" data-category="archives" onclick="filterCategory('archives')">
                📦 Archives
            </button>
            <button class="category-btn" data-category="others" onclick="filterCategory('others')">
                📎 Khác
            </button>
        </div>
        
        <div class="stats" id="statsContainer"></div>
        
        <div class="file-grid" id="fileGrid">
            <div class="loading">Đang tải...</div>
        </div>
    </div>
    
    <!-- Preview Modal -->
    <div class="modal" id="previewModal">
        <div class="modal-content preview-container">
            <div class="modal-header">
                <h2 id="previewTitle">Preview</h2>
                <button class="close-modal" onclick="closeModal('previewModal')">×</button>
            </div>
            <div class="modal-body" id="previewBody"></div>
        </div>
    </div>
    
    <!-- Editor Modal -->
    <div class="modal" id="editorModal">
        <div class="modal-content editor-container">
            <div class="modal-header">
                <h2 id="editorTitle">Chỉnh sửa File</h2>
                <div>
                    <button class="btn btn-success" onclick="saveFile()">💾 Lưu</button>
                    <button class="close-modal" onclick="closeModal('editorModal')">×</button>
                </div>
            </div>
            <div class="modal-body">
                <textarea class="editor-textarea" id="editorTextarea"></textarea>
            </div>
        </div>
    </div>
    
    <script>
        let currentCategory = 'all';
        let currentSearch = '';
        let selectedFiles = new Set();
        let currentEditFileId = null;
        
        // Icons for file types
        const fileIcons = {
            images: '🖼️',
            videos: '🎬',
            audio: '🎵',
            documents: '📄',
            code: '💻',
            archives: '📦',
            others: '📎'
        };
        
        // Load files on page load
        window.addEventListener('DOMContentLoaded', () => {
            loadFiles();
            loadStats();
        });
        
        async function scanFiles() {
            const formData = new FormData();
            formData.append('action', 'scan');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    alert(`✅ Đã quét ${data.count} files!`);
                    loadFiles();
                    loadStats();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Lỗi khi quét files!');
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
                    alert(`✅ Đã tải lên ${data.files.length} files!`);
                    loadFiles();
                    loadStats();
                    fileInput.value = '';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Lỗi khi tải lên files!');
            }
        }
        
        async function loadFiles() {
            const fileGrid = document.getElementById('fileGrid');
            fileGrid.innerHTML = '<div class="loading">Đang tải...</div>';
            
            try {
                const response = await fetch(`?action=list&category=${currentCategory}&search=${encodeURIComponent(currentSearch)}`);
                const data = await response.json();
                
                if (data.success && data.files.length > 0) {
                    fileGrid.innerHTML = data.files.map(file => createFileCard(file)).join('');
                } else {
                    fileGrid.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">📂</div>
                            <h2>Không có files</h2>
                            <p>Nhấn "Quét Files" để tìm files hoặc tải lên files mới</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                fileGrid.innerHTML = '<div class="loading">❌ Lỗi khi tải files!</div>';
            }
        }
        
        function createFileCard(file) {
            const icon = fileIcons[file.category] || '📎';
            const previewHtml = (file.category === 'images') 
                ? `<img src="?action=preview&id=${file.id}" alt="${file.filename}">`
                : `<div>${icon}</div>`;
            
            const size = formatFileSize(file.filesize);
            
            return `
                <div class="file-card ${selectedFiles.has(file.id) ? 'selected' : ''}" data-id="${file.id}">
                    <input type="checkbox" class="checkbox" onchange="toggleSelect(${file.id})" ${selectedFiles.has(file.id) ? 'checked' : ''}>
                    <div class="file-preview" onclick="previewFile(${file.id})">
                        ${previewHtml}
                    </div>
                    <div class="file-info">
                        <div class="file-name" title="${file.filename}">${file.filename}</div>
                        <div class="file-meta">
                            ${size} • ${file.category}
                        </div>
                    </div>
                    <div class="file-actions">
                        <button onclick="previewFile(${file.id})" title="Xem">👁️</button>
                        <button onclick="editFile(${file.id})" title="Sửa">✏️</button>
                        <button onclick="downloadFile(${file.id})" title="Tải về">⬇️</button>
                        <button onclick="deleteFile(${file.id})" title="Xoá">🗑️</button>
                    </div>
                </div>
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
                    
                    statsContainer.innerHTML = `
                        <div class="stat-card">
                            <div class="number">${totalFiles}</div>
                            <div class="label">Tổng số files</div>
                        </div>
                        <div class="stat-card">
                            <div class="number">${formatFileSize(totalSize)}</div>
                            <div class="label">Tổng dung lượng</div>
                        </div>
                        ${data.stats.map(stat => `
                            <div class="stat-card">
                                <div class="number">${stat.count}</div>
                                <div class="label">${fileIcons[stat.category]} ${stat.category}</div>
                            </div>
                        `).join('')}
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        function filterCategory(category) {
            currentCategory = category;
            
            document.querySelectorAll('.category-btn').forEach(btn => {
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
            
            document.querySelectorAll('.file-card').forEach(card => {
                const id = parseInt(card.dataset.id);
                if (selectedFiles.has(id)) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
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
                    
                    if (data.file.category === 'images') {
                        previewHtml = `<img src="?action=preview&id=${id}" alt="${data.file.filename}">`;
                    } else if (data.file.category === 'videos') {
                        previewHtml = `<video controls><source src="?action=preview&id=${id}"></video>`;
                    } else if (data.file.category === 'audio') {
                        previewHtml = `<audio controls><source src="?action=preview&id=${id}"></audio>`;
                    } else if (data.file.category === 'documents' || data.file.category === 'code') {
                        previewHtml = `<pre style="white-space: pre-wrap; background: #f8f9fa; padding: 20px; border-radius: 8px; max-height: 500px; overflow: auto;">${escapeHtml(data.content)}</pre>`;
                    } else {
                        previewHtml = `<p>Không thể preview loại file này. <a href="?action=download&id=${id}">Tải về</a></p>`;
                    }
                    
                    body.innerHTML = previewHtml;
                    modal.classList.add('active');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Lỗi khi xem file!');
            }
        }
        
        async function editFile(id) {
            try {
                const response = await fetch(`?action=get_file&id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    if (data.file.category === 'documents' || data.file.category === 'code') {
                        const modal = document.getElementById('editorModal');
                        const title = document.getElementById('editorTitle');
                        const textarea = document.getElementById('editorTextarea');
                        
                        title.textContent = `Chỉnh sửa: ${data.file.filename}`;
                        textarea.value = data.content;
                        currentEditFileId = id;
                        
                        modal.classList.add('active');
                    } else {
                        alert('⚠️ Chỉ có thể chỉnh sửa files văn bản và code!');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Lỗi khi mở file!');
            }
        }
        
        async function saveFile() {
            if (!currentEditFileId) return;
            
            const content = document.getElementById('editorTextarea').value;
            const formData = new FormData();
            formData.append('action', 'save_file');
            formData.append('id', currentEditFileId);
            formData.append('content', content);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Đã lưu file!');
                    closeModal('editorModal');
                    loadFiles();
                } else {
                    alert('❌ Lỗi khi lưu file!');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Lỗi khi lưu file!');
            }
        }
        
        async function deleteFile(id) {
            if (!confirm('Bạn có chắc muốn xoá file này?')) return;
            
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
                    alert('✅ Đã xoá file!');
                    loadFiles();
                    loadStats();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Lỗi khi xoá file!');
            }
        }
        
        async function deleteSelected() {
            if (selectedFiles.size === 0) return;
            
            if (!confirm(`Bạn có chắc muốn xoá ${selectedFiles.size} files đã chọn?`)) return;
            
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
                    alert(`✅ Đã xoá ${selectedFiles.size} files!`);
                    selectedFiles.clear();
                    updateSelectedUI();
                    loadFiles();
                    loadStats();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Lỗi khi xoá files!');
            }
        }
        
        function downloadFile(id) {
            window.location.href = `?action=download&id=${id}`;
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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
