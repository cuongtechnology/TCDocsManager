# 📁 File Manager Pro - One Page Application

Ứng dụng web quản lý files thông minh với PHP và SQLite3.

## ✨ Tính năng chính

### 🔍 Tự động nhận diện và quét files
- Tự động quét các thư mục được chỉ định
- Nhận diện và phân loại files tự động
- Lưu trữ metadata trong SQLite database

### 🏷️ Phân loại thông minh
- **Hình ảnh** (images): jpg, png, gif, svg, webp, etc.
- **Video** (videos): mp4, avi, mov, mkv, webm, etc.
- **Audio** (audio): mp3, wav, ogg, flac, m4a, etc.
- **Tài liệu** (documents): pdf, doc, docx, xls, txt, rtf, etc.
- **Code** (code): php, html, css, js, json, xml, py, java, etc.
- **Archives** (archives): zip, rar, 7z, tar, gz, etc.
- **Khác** (others): các loại file khác

### 📤 Upload và quản lý
- Upload nhiều files cùng lúc
- Drag & drop files (qua input file)
- Tự động scan sau khi upload

### 🗑️ Xoá hàng loạt
- Chọn nhiều files để xoá
- Checkbox selection
- Xác nhận trước khi xoá

### 👁️ Preview đa dạng
- **Hình ảnh**: Preview trực tiếp trong modal
- **Video**: HTML5 video player
- **Audio**: HTML5 audio player
- **Text/Code**: Syntax highlighting preview
- **Documents**: Text content preview

### ✏️ Online Editor
- Chỉnh sửa trực tuyến files text và code
- Syntax-aware editor
- Lưu trực tiếp vào file

### 📊 Thống kê
- Tổng số files theo từng loại
- Tổng dung lượng
- Real-time statistics

### 🔍 Tìm kiếm
- Tìm kiếm theo tên file
- Tìm kiếm theo tags
- Real-time search

## 🚀 Cài đặt và sử dụng

### Yêu cầu
- PHP 7.4+ với SQLite3 extension
- Web server (Apache/Nginx) hoặc PHP built-in server

### Khởi động

1. **Sử dụng PHP Built-in Server:**
```bash
php -S 0.0.0.0:8000
```

2. **Truy cập ứng dụng:**
```
http://localhost:8000
```

### Cấu hình thư mục quét

Chỉnh sửa trong `index.php`:

```php
define('SCAN_DIRS', [
    __DIR__ . '/uploads',
    __DIR__ . '/documents',
    __DIR__ . '/media',
    // Thêm các thư mục khác tại đây
]);
```

## 📖 Hướng dẫn sử dụng

### 1. Quét files
- Nhấn nút **"🔄 Quét Files"** để scan tất cả files trong các thư mục được cấu hình
- Database sẽ được cập nhật tự động

### 2. Upload files
- Nhấn nút **"📤 Tải lên"**
- Chọn một hoặc nhiều files
- Files sẽ được upload vào thư mục `uploads/`

### 3. Lọc theo loại
- Sử dụng các nút category để lọc files:
  - 📂 Tất cả
  - 🖼️ Hình ảnh
  - 🎬 Video
  - 🎵 Audio
  - 📄 Tài liệu
  - 💻 Code
  - 📦 Archives
  - 📎 Khác

### 4. Tìm kiếm
- Nhập từ khóa vào ô tìm kiếm
- Kết quả được filter real-time

### 5. Preview files
- Click vào thumbnail hoặc nút **👁️** để xem preview
- Preview modal hỗ trợ:
  - Ảnh: Hiển thị full size
  - Video: Player với controls
  - Audio: Player với controls
  - Text/Code: Hiển thị nội dung với formatting

### 6. Chỉnh sửa files
- Click nút **✏️** trên file card
- Editor modal mở ra cho files text/code
- Chỉnh sửa nội dung
- Click **💾 Lưu** để save

### 7. Tải về files
- Click nút **⬇️** để download file

### 8. Xoá files

**Xoá đơn lẻ:**
- Click nút **🗑️** trên file card
- Xác nhận xoá

**Xoá hàng loạt:**
- Check các checkbox trên file cards
- Nút **"🗑️ Xoá đã chọn"** sẽ xuất hiện
- Click để xoá tất cả files đã chọn

## 🎨 Giao diện

### One Page App Design
- Material Design inspired
- Gradient backgrounds
- Smooth animations
- Responsive grid layout
- Modal dialogs cho preview và edit

### Responsive
- Desktop: Multi-column grid
- Tablet: 2-3 columns
- Mobile: 1-2 columns
- Touch-friendly buttons

## 🗄️ Database Schema

```sql
CREATE TABLE files (
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
);
```

## 🔧 API Endpoints

### POST Actions
- `action=scan` - Quét và cập nhật database
- `action=upload` - Upload files
- `action=delete` - Xoá file đơn
- `action=delete_multiple` - Xoá nhiều files
- `action=update_tags` - Cập nhật tags
- `action=save_file` - Lưu nội dung file sau edit

### GET Actions
- `action=list` - Lấy danh sách files (có filter)
- `action=stats` - Lấy thống kê
- `action=get_file` - Lấy thông tin và nội dung file
- `action=preview` - Preview file
- `action=download` - Download file

## 🔒 Bảo mật

### Lưu ý
- Chỉ sử dụng trong môi trường trusted
- Thêm authentication nếu deploy public
- Validate file types khi upload
- Giới hạn file size upload
- Sanitize user input

### Cải thiện bảo mật (khuyến nghị)
```php
// Giới hạn upload size
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '50M');

// Validate file extensions
$allowed = ['jpg', 'png', 'pdf', 'txt'];
if (!in_array($ext, $allowed)) {
    die('File type not allowed');
}
```

## 📝 Tính năng nâng cao

### Có thể mở rộng
- [ ] Bulk rename
- [ ] File compression
- [ ] Thumbnail generation
- [ ] Full-text search
- [ ] User authentication
- [ ] Permission management
- [ ] Share links
- [ ] File versioning
- [ ] Trash/Recycle bin

## 🐛 Troubleshooting

### Database không tạo được
```bash
# Check permissions
chmod 755 /home/user/webapp
chmod 666 /home/user/webapp/filemanager.db
```

### Upload không hoạt động
```bash
# Check upload directory permissions
chmod 755 /home/user/webapp/uploads
```

### Preview không hiển thị
- Check MIME type support
- Check file permissions
- Check PHP memory limit

## 📄 License

MIT License - Free to use and modify

## 👨‍💻 Author

Created with ❤️ by Tạ Tiến Cường

---

**Version:** 1.0.0  
**Last Updated:** 2025-11-19
