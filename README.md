# 🏥 Bảo Ngọc GROUP - Hệ thống Quản lý Tài liệu Nội bộ

**Hệ thống y tế chất lượng cao tại Thái Nguyên**

Ứng dụng web quản lý tài liệu văn phòng nội bộ với PHP và SQLite3, tự động quét và phân loại tài liệu.

## 🎯 Mục đích

Hệ thống được thiết kế đặc biệt cho nhân viên nội bộ Bảo Ngọc GROUP để:
- 📂 Xem và truy cập tài liệu văn phòng
- ⬇️ Tải về các file docs, pdf, excel, powerpoint
- 🔍 Tìm kiếm nhanh tài liệu cần thiết
- 📊 Quản lý tài liệu theo phòng ban/loại tài liệu

## ✨ Tính năng chính

### 🔄 Tự động quét tài liệu
- **Quét tự động** khi khởi động hệ thống lần đầu
- **Quét thủ công** bất kỳ lúc nào cần cập nhật
- Nhận diện và phân loại tự động theo loại file
- Lưu trữ metadata trong SQLite database

### 📁 Phân loại thông minh
- **📕 PDF**: Tài liệu PDF
- **📘 Word**: Documents (.doc, .docx, .odt, .rtf)
- **📗 Excel**: Spreadsheets (.xls, .xlsx, .csv)
- **📙 PowerPoint**: Presentations (.ppt, .pptx)
- **🖼️ Hình ảnh**: Images (jpg, png, gif, etc.)
- **📦 Files nén**: Archives (zip, rar, 7z, etc.)

### 📂 Quản lý theo thư mục
Hệ thống tự động quét các thư mục:
- 📋 **Tài liệu hành chính**: Văn bản quản lý, công văn, quyết định
- 🏥 **Tài liệu kỹ thuật**: Quy trình y tế, hướng dẫn chuyên môn
- 📜 **Quy định nội bộ**: Quy chế, nội quy, quy trình làm việc
- 📝 **Biểu mẫu**: Forms, templates, mẫu đơn
- 📊 **Báo cáo**: Reports hàng tháng, quý, năm
- 📤 **Uploads**: Tài liệu upload chung

### 👁️ Xem và Preview
- **PDF**: Xem trực tiếp trong browser
- **Hình ảnh**: Preview full size
- **Tài liệu khác**: Thông tin chi tiết + nút tải về

### ⬇️ Tải về tài liệu
- Download trực tiếp mọi loại tài liệu
- Giữ nguyên tên file gốc
- Hỗ trợ tất cả định dạng văn phòng

### 🔍 Tìm kiếm nhanh
- Tìm kiếm theo tên file
- Lọc theo loại tài liệu (PDF, Word, Excel, v.v.)
- Lọc theo thư mục/phòng ban
- Real-time search

### 📊 Thống kê
- Tổng số tài liệu trong hệ thống
- Tổng dung lượng lưu trữ
- Phân loại theo loại file
- Thống kê theo thư mục

## 🚀 Cài đặt và Triển khai

### Yêu cầu hệ thống
- PHP 7.4+ với SQLite3 extension
- Web server: Apache, Nginx hoặc PHP built-in server
- Không cần MySQL hay database server phức tạp

### Cài đặt nhanh

1. **Clone repository:**
```bash
git clone https://github.com/cuongtechnology/TCDocsManager.git
cd TCDocsManager
```

2. **Cấu hình thư mục quét:**

Chỉnh sửa file `index.php` tại dòng 14-20:

```php
define('SCAN_DIRS', [
    __DIR__ . '/tai-lieu-hanh-chinh',      // Tài liệu hành chính
    __DIR__ . '/tai-lieu-ky-thuat',        // Tài liệu kỹ thuật y tế
    __DIR__ . '/quy-dinh-noi-bo',          // Quy định nội bộ
    __DIR__ . '/bieu-mau',                  // Biểu mẫu
    __DIR__ . '/bao-cao',                   // Báo cáo
    __DIR__ . '/uploads',                   // Upload chung
]);
```

**Lưu ý**: Thư mục sẽ được tự động tạo nếu chưa tồn tại.

3. **Khởi động server:**

```bash
# Sử dụng PHP built-in server (development)
php -S 0.0.0.0:8000

# Hoặc trỏ Apache/Nginx document root về thư mục này
```

4. **Truy cập:**
```
http://localhost:8000
```

Hệ thống sẽ **tự động quét** tài liệu ngay lần đầu truy cập!

## 📖 Hướng dẫn sử dụng

### Cho nhân viên nội bộ

#### 1. Xem danh sách tài liệu
- Truy cập trang chủ
- Tài liệu được hiển thị dạng grid với icon loại file
- Mỗi card hiển thị: tên file, loại, dung lượng, thư mục

#### 2. Lọc tài liệu
**Lọc theo loại:**
- 📕 PDF
- 📘 Word  
- 📗 Excel
- 📙 PowerPoint
- 🖼️ Hình ảnh
- 📦 Files nén

**Lọc theo thư mục:**
- Chọn thư mục cụ thể từ danh sách
- Xem tài liệu của từng phòng ban/bộ phận

#### 3. Tìm kiếm
- Gõ từ khóa vào ô tìm kiếm
- Kết quả hiện ngay lập tức (real-time)
- Tìm theo tên file

#### 4. Xem tài liệu
- Click vào thumbnail hoặc nút **👁️ Xem**
- PDF sẽ mở trong browser
- Hình ảnh hiển thị full size
- Tài liệu khác: xem thông tin + nút tải về

#### 5. Tải về tài liệu
- Click nút **⬇️ Tải về**
- File được download về máy với tên gốc

### Cho quản trị viên

#### 1. Thêm tài liệu vào hệ thống

**Cách 1: Copy trực tiếp vào thư mục**
```bash
# Copy files vào thư mục tương ứng
cp tai-lieu.pdf /path/to/app/tai-lieu-hanh-chinh/
cp bieu-mau.docx /path/to/app/bieu-mau/
```
Sau đó click **"🔄 Quét lại Tài liệu"** trong giao diện web.

**Cách 2: Upload qua giao diện web**
- Click nút **"📤 Tải lên Tài liệu"**
- Chọn file từ máy tính
- File sẽ tự động được thêm vào hệ thống

#### 2. Quét lại tài liệu
- Click nút **"🔄 Quét lại Tài liệu"**
- Hệ thống quét tất cả thư mục được cấu hình
- Cập nhật database với files mới/thay đổi

#### 3. Xoá tài liệu
**Xoá đơn:**
- Click nút **🗑️** trên card tài liệu
- Xác nhận xoá

**Xoá hàng loạt:**
- Tick checkbox trên các tài liệu cần xoá
- Click nút **"🗑️ Xoá đã chọn"**
- Xác nhận xoá

#### 4. Tổ chức thư mục

Tạo cấu trúc thư mục phù hợp với công ty:

```
/tai-lieu-hanh-chinh/
  ├── cong-van/
  ├── quyet-dinh/
  └── thong-bao/

/tai-lieu-ky-thuat/
  ├── quy-trinh-kham/
  ├── huong-dan-dieu-tri/
  └── tieu-chuan-ky-thuat/

/quy-dinh-noi-bo/
  ├── noi-quy/
  ├── quy-che/
  └── quy-trinh-lam-viec/

/bieu-mau/
  ├── don-tu/
  ├── phieu-ghi/
  └── templates/

/bao-cao/
  ├── thang/
  ├── quy/
  └── nam/
```

## ⚙️ Cấu hình nâng cao

### Tùy chỉnh thông tin công ty

Trong file `index.php`, dòng 8-9:

```php
define('COMPANY_NAME', 'Bảo Ngọc GROUP');
define('COMPANY_DESC', 'Hệ thống y tế chất lượng cao tại Thái Nguyên');
```

### Thêm/Bớt thư mục quét

Chỉnh sửa mảng `SCAN_DIRS` trong `index.php`:

```php
define('SCAN_DIRS', [
    __DIR__ . '/thu-muc-cua-ban',
    __DIR__ . '/thu-muc-khac',
    // Thêm thư mục mới tại đây
]);
```

### Giới hạn upload size

Thêm vào đầu file `index.php`:

```php
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');
ini_set('max_execution_time', '300');
```

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
    tags TEXT,
    description TEXT
);

-- Indexes for performance
CREATE INDEX idx_category ON files(category);
CREATE INDEX idx_filetype ON files(filetype);
CREATE INDEX idx_folder ON files(folder);
```

## 🔒 Bảo mật

### Quan trọng
- ⚠️ Hệ thống thiết kế cho **mạng nội bộ**
- 🔐 Nên thêm authentication nếu expose ra internet
- 🛡️ Cấu hình firewall chặn truy cập từ bên ngoài
- 📁 Phân quyền folder: `chmod 755` cho thư mục, `644` cho files

### Khuyến nghị triển khai

**Mạng nội bộ (LAN):**
- ✅ Chạy trên server trong mạng công ty
- ✅ Chỉ nhân viên trong mạng truy cập được
- ✅ Không cần authentication phức tạp

**Nếu expose ra internet:**
```php
// Thêm basic authentication
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Bao Ngoc Docs"');
    header('HTTP/1.0 401 Unauthorized');
    exit('Unauthorized');
}
```

## 📱 Responsive Design

- 💻 **Desktop**: Grid nhiều cột, đầy đủ tính năng
- 📱 **Tablet**: Grid 2-3 cột, touch-friendly
- 📲 **Mobile**: Grid 1-2 cột, UI tối ưu cho màn hình nhỏ

## 🐛 Troubleshooting

### Database không tạo được
```bash
# Check permissions
chmod 755 /path/to/app
chmod 666 /path/to/app/documents.db
```

### Upload không hoạt động
```bash
# Check upload directory
chmod 755 /path/to/app/uploads
```

### Quét không thấy files
- Kiểm tra đường dẫn thư mục trong `SCAN_DIRS`
- Đảm bảo thư mục tồn tại và có files
- Check permissions: `ls -la /path/to/folder`

### PDF không preview được
- Check PHP extensions: `php -m | grep fileinfo`
- Đảm bảo browser hỗ trợ PDF viewer

## 📈 Roadmap - Tính năng tương lai

- [ ] 👥 Quản lý users và phân quyền
- [ ] 📝 Thêm mô tả cho tài liệu
- [ ] 🏷️ Tags và metadata
- [ ] 📅 Lọc theo ngày tháng
- [ ] 🔔 Thông báo tài liệu mới
- [ ] 📊 Thống kê truy cập
- [ ] 🗂️ Version control cho tài liệu
- [ ] 💬 Comments và discussions
- [ ] 🔗 Share links
- [ ] 📦 Bulk export/import

## 📄 License

MIT License - Free to use and modify

## 👨‍💻 Developer

**Created with ❤️ by Tạ Tiến Cường**

Developed for Bảo Ngọc GROUP - Healthcare System

---

## 📞 Hỗ trợ kỹ thuật

Nếu có vấn đề kỹ thuật, vui lòng liên hệ IT department.

**Version:** 2.0.0  
**Last Updated:** 2025-11-19  
**Platform:** PHP 8.2 + SQLite3
