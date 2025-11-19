# 🔐 Hệ thống Xác thực và Phân quyền

## 📋 Tổng quan

Hệ thống sử dụng 2 chế độ hoạt động:

### 👥 **PUBLIC MODE** (Chế độ Công khai)
- ✅ Không cần đăng nhập
- ✅ Xem danh sách tài liệu
- ✅ Tìm kiếm và lọc
- ✅ Preview tài liệu
- ✅ Download tài liệu
- ❌ KHÔNG thể upload
- ❌ KHÔNG thể xoá
- ❌ KHÔNG thể quét files

### 🔐 **ADMIN MODE** (Chế độ Quản trị)
- ✅ Tất cả quyền của Public Mode
- ✅ Upload tài liệu mới
- ✅ Xoá tài liệu (đơn và hàng loạt)
- ✅ Quét lại thư mục
- ✅ Quản lý hệ thống

---

## 🔑 Thông tin Đăng nhập

### Default Admin Credentials

```
Username: admin
Password: baongoc2025
```

⚠️ **QUAN TRỌNG**: Thay đổi mật khẩu này sau khi triển khai!

### Cách thay đổi mật khẩu

Chỉnh sửa file `index.php` tại dòng 19-20:

```php
// Admin credentials - Thay đổi username và password tại đây
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'baongoc2025'); // Nên đổi password này!
```

**Ví dụ thay đổi**:

```php
define('ADMIN_USERNAME', 'cuongtc');
define('ADMIN_PASSWORD', 'MyStr0ngP@ssw0rd2025');
```

---

## 📖 Hướng dẫn Sử dụng

### Cho Người dùng Công khai

#### 1. Truy cập hệ thống
```
http://your-domain.com
```

#### 2. Xem tài liệu
- Duyệt danh sách tài liệu trên trang chủ
- Không cần đăng nhập

#### 3. Tìm kiếm
- Sử dụng ô tìm kiếm ở góc phải
- Lọc theo loại tài liệu
- Lọc theo thư mục

#### 4. Preview tài liệu
- Click vào thumbnail hoặc nút 👁️
- Xem nội dung trực tiếp (PDF, ảnh, text)

#### 5. Download
- Click nút ⬇️ để tải về
- File giữ nguyên tên gốc

---

### Cho Quản trị viên

#### 1. Đăng nhập

**Bước 1**: Truy cập trang chủ

**Bước 2**: Tìm panel login phía trên (màu xanh dương)

**Bước 3**: Nhập thông tin:
```
Tên đăng nhập: admin
Mật khẩu: baongoc2025
```

**Bước 4**: Click nút "🔐 Đăng nhập Quản trị"

**Bước 5**: Nếu đúng, sẽ thấy:
- Thông báo "✅ Đăng nhập thành công"
- Panel hiện "👤 Quản trị viên"
- Các nút admin xuất hiện

#### 2. Upload tài liệu

**Sau khi đăng nhập**:

1. Click nút "📤 Tải lên Tài liệu"
2. Chọn một hoặc nhiều files
3. Đợi upload hoàn tất
4. Tài liệu tự động được thêm vào hệ thống

**Lưu ý**:
- Upload vào thư mục `uploads/`
- Có thể upload nhiều files cùng lúc
- Hệ thống tự động phân loại

#### 3. Quét lại tài liệu

**Khi nào cần quét**:
- Thêm files trực tiếp vào thư mục (không qua upload)
- Xoá files thủ công
- Cập nhật files trong thư mục

**Cách quét**:
1. Click nút "🔄 Quét lại Tài liệu"
2. Xác nhận "OK"
3. Đợi quét hoàn tất
4. Kết quả hiển thị số files đã quét

#### 4. Xoá tài liệu

**Xoá đơn lẻ**:
1. Click nút 🗑️ trên file card
2. Xác nhận xoá
3. File bị xoá khỏi hệ thống

**Xoá hàng loạt**:
1. Tick checkbox trên các files cần xoá
2. Nút "🗑️ Xoá đã chọn" sẽ xuất hiện
3. Click nút đó
4. Xác nhận xoá
5. Tất cả files đã chọn bị xoá

#### 5. Đăng xuất

1. Click nút "🚪 Đăng xuất"
2. Xác nhận "OK"
3. Trở về chế độ Public

---

## 🔒 Bảo mật

### Các tính năng bảo mật đã triển khai

#### 1. Server-side Protection
```php
function requireAdmin() {
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Cần đăng nhập']);
        exit;
    }
}
```

- Mọi action admin đều kiểm tra quyền
- Return 403 Forbidden nếu không phải admin
- Không thể bypass từ client-side

#### 2. Session Management
- Sử dụng PHP Session
- Session expire khi đóng browser
- Secure session handling

#### 3. Protected Actions
Các action yêu cầu admin:
- `scan` - Quét files
- `upload` - Upload files
- `delete` - Xoá file đơn
- `delete_multiple` - Xoá hàng loạt
- `update_description` - Cập nhật mô tả

#### 4. Client-side UI Protection
- Ẩn nút admin khi chưa login
- Không hiện checkbox
- Ẩn nút xoá
- Clean interface cho public users

### Khuyến nghị Bảo mật

#### Khi triển khai Production

1. **Thay đổi mật khẩu**:
```php
define('ADMIN_PASSWORD', 'YOUR_STRONG_PASSWORD_HERE');
```

2. **Sử dụng HTTPS**:
- Bắt buộc cho production
- Bảo vệ credentials khi login

3. **Giới hạn IP** (tuỳ chọn):
```php
// Chỉ cho phép admin từ IP nội bộ
$allowed_ips = ['192.168.1.100', '10.0.0.50'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    // Block access
}
```

4. **File permissions**:
```bash
chmod 755 /path/to/webapp
chmod 644 index.php
chmod 666 documents.db
```

5. **Backup thường xuyên**:
```bash
# Backup database
cp documents.db documents.db.backup

# Backup toàn bộ
tar -czf backup-$(date +%Y%m%d).tar.gz /path/to/webapp
```

---

## 🐛 Troubleshooting

### Không đăng nhập được

**Nguyên nhân 1**: Sai username/password
```
✅ Giải pháp: Kiểm tra lại thông tin đăng nhập
✅ Xem lại config trong index.php
```

**Nguyên nhân 2**: Session không hoạt động
```bash
# Check PHP session
php -i | grep session.save_path

# Ensure directory exists and writable
ls -la /var/lib/php/sessions/
```

**Nguyên nhân 3**: Browser block cookies
```
✅ Enable cookies trong browser
✅ Clear browser cache
✅ Thử browser khác
```

### Đã login nhưng không thấy nút admin

**Kiểm tra**:
1. Refresh page (F5)
2. Clear cache và reload (Ctrl+Shift+R)
3. Check console log (F12)
4. Logout và login lại

### Upload không hoạt động sau khi login

**Nguyên nhân**: Permission issues
```bash
# Check upload directory
ls -la uploads/

# Fix permissions
chmod 755 uploads/
chown www-data:www-data uploads/
```

### Session bị mất khi chuyển trang

**Nguyên nhân**: Session config
```php
// Add to top of index.php if needed
ini_set('session.cookie_lifetime', 86400); // 24 hours
ini_set('session.gc_maxlifetime', 86400);
```

---

## 📊 Session Flow

```
┌─────────────────┐
│  User Access    │
│  Website        │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Check Session  │
│  isAdmin()?     │
└────┬──────┬─────┘
     │      │
   No│      │Yes
     │      │
     ▼      ▼
┌─────────┐ ┌──────────┐
│ PUBLIC  │ │  ADMIN   │
│  MODE   │ │   MODE   │
└─────────┘ └──────────┘
│           │
│ - View   │ │ - All Public │
│ - Search │ │ - Upload     │
│ - Download│ │ - Delete    │
│           │ │ - Manage    │
└───────────┘ └─────────────┘
```

---

## 🔄 Login Process

```
1. User enters credentials
   ↓
2. AJAX POST to server
   ↓
3. Server validates username/password
   ↓
4. If valid:
   - Set $_SESSION['admin_logged_in'] = true
   - Return success
   ↓
5. Client receives success
   ↓
6. Update UI to admin mode
   - Show admin buttons
   - Show checkboxes
   - Update panel
```

---

## 📝 Best Practices

### Cho Quản trị viên

1. **Không chia sẻ mật khẩu**
   - Chỉ admin mới biết credentials
   - Không gửi qua email/chat

2. **Đăng xuất sau khi dùng**
   - Đặc biệt trên máy chung
   - Bảo vệ session

3. **Backup định kỳ**
   - Weekly backup database
   - Monthly full backup

4. **Kiểm tra logs**
   - Monitor upload activity
   - Check delete operations

### Cho Người triển khai

1. **Môi trường Production**
   - Sử dụng HTTPS
   - Strong password
   - Regular updates

2. **Monitoring**
   - Server logs
   - Access logs
   - Error logs

3. **Disaster Recovery**
   - Backup strategy
   - Restore procedure
   - Contact plan

---

## 🎯 Security Checklist

Trước khi deploy:

- [ ] Đã thay đổi password mặc định
- [ ] Đã test login/logout
- [ ] Đã test permissions (public không xoá được)
- [ ] HTTPS đã được enable
- [ ] File permissions đã đúng
- [ ] Backup đã được thiết lập
- [ ] Session timeout hợp lý
- [ ] Error messages không leak info
- [ ] SQL injection protected (SQLite3 prepared statements)
- [ ] File upload validated

---

## 📞 Support

Nếu có vấn đề về authentication:

1. Check server logs
2. Check browser console
3. Verify credentials
4. Test session functionality
5. Contact IT support

---

**Version**: 3.0.0  
**Last Updated**: 2025-11-19  
**Security Level**: Production Ready
