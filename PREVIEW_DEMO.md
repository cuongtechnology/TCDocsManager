# 🎬 Demo Preview Features

Hệ thống đã được nâng cấp với khả năng preview đầy đủ cho nhiều loại files!

## ✅ Các loại file được hỗ trợ preview

### 🖼️ Hình ảnh (Images)
**Hỗ trợ**: JPG, JPEG, PNG, GIF, BMP, WEBP, SVG

**Preview**: Hiển thị full size với styling đẹp mắt

**Files demo có sẵn**:
- `tai-lieu-hanh-chinh/logo-test.png` - Logo test
- `bao-cao/chart.png` - Chart demo

**Cách xem**:
1. Click vào thumbnail của ảnh
2. Hoặc click nút 👁️ "Xem"
3. Ảnh hiển thị full size trong modal

---

### 📕 PDF Files
**Hỗ trợ**: PDF documents

**Preview**: Embedded PDF viewer trong browser với toolbar

**Cách test**:
1. Upload file PDF vào bất kỳ thư mục nào
2. Click nút 👁️ để xem
3. PDF sẽ mở trong viewer với zoom, scroll, navigation

**Features**:
- Zoom in/out
- Navigate pages
- Download button

---

### 📝 Text Files
**Hỗ trợ**: TXT, HTML, CSS, JS, JSON, XML, CSV, MD

**Preview**: Hiển thị với formatting và syntax highlighting

**Files demo có sẵn**:
- `tai-lieu-hanh-chinh/cong-van-mau.txt` - Text file
- `bieu-mau/huong-dan.html` - HTML file
- `quy-dinh-noi-bo/config.json` - JSON file
- `tai-lieu-ky-thuat/quy-trinh-kham-benh.txt` - Text file

**Cách xem**:
1. Click nút 👁️ trên file card
2. Nội dung hiển thị trong code block với monospace font
3. Xuống dòng tự động, dễ đọc

---

### 📘 Word Documents
**Hỗ trợ**: DOC, DOCX, ODT, RTF

**Preview**: Hiển thị thông tin + hướng dẫn download

**Features**:
- Icon Word document
- Tên file và dung lượng
- Thông báo cần download để xem đầy đủ
- Nút download lớn, dễ thấy

---

### 📗 Excel Spreadsheets
**Hỗ trợ**: XLS, XLSX, CSV, ODS

**Preview**: Hiển thị thông tin + hướng dẫn download

**Features**:
- Icon Excel document
- Metadata file
- Download instructions
- Direct download button

---

### 📙 PowerPoint Presentations
**Hỗ trợ**: PPT, PPTX, ODP

**Preview**: Hiển thị thông tin + hướng dẫn download

**Features**:
- Icon PowerPoint document
- File information
- Download instructions

---

### 📦 Archive Files
**Hỗ trợ**: ZIP, RAR, 7Z, TAR, GZ

**Preview**: Hiển thị thông tin + hướng dẫn

**Features**:
- Archive icon
- File size display
- Extraction instructions
- Download button

---

### 📎 Other Files
**Hỗ trợ**: Các file khác

**Preview**: Hiển thị thông tin cơ bản

**Features**:
- Generic file icon
- File metadata
- Message về file type
- Download button

---

## 🎨 Preview Modal Features

### Design
- ✅ Full-screen modal với overlay
- ✅ Responsive cho mobile & tablet
- ✅ Close button (X) màu đỏ dễ thấy
- ✅ Click outside để đóng
- ✅ Smooth animations

### UX Improvements
- ✅ Loading states
- ✅ Error handling
- ✅ File size display
- ✅ Category badges
- ✅ Download buttons với icons

---

## 📖 Hướng dẫn Test

### Test Preview Images
```bash
# Truy cập web app
http://localhost:8000

# Click vào category "🖼️ Hình ảnh"
# Click vào file "logo-test.png" hoặc "chart.png"
# Ảnh sẽ hiển thị full size
```

### Test Preview Text Files
```bash
# Click vào file .txt, .html, hoặc .json
# Nội dung file hiển thị với formatting
# Có thể scroll nếu file dài
```

### Test Upload & Preview
```bash
# 1. Prepare test files
# - Ảnh: any .jpg, .png file
# - PDF: any .pdf document
# - Text: any .txt, .html, .json file

# 2. Upload via web interface
# Click "📤 Tải lên Tài liệu"
# Select files
# Wait for upload complete

# 3. Preview uploaded files
# Click 👁️ on any uploaded file
# Verify preview works correctly
```

---

## 🐛 Troubleshooting

### Ảnh không hiển thị
**Nguyên nhân**: 
- File path không đúng
- Permission issues
- File bị corrupt

**Giải pháp**:
```bash
# Check file permissions
chmod 644 tai-lieu-hanh-chinh/*.png

# Verify file exists
ls -lh tai-lieu-hanh-chinh/logo-test.png

# Check MIME type
file --mime-type tai-lieu-hanh-chinh/logo-test.png
```

### PDF không preview được
**Nguyên nhân**:
- Browser không hỗ trợ
- PDF bị lỗi
- File quá lớn

**Giải pháp**:
- Thử browser khác (Chrome, Firefox)
- Download file và mở bằng PDF reader
- Check file integrity

### Text file hiển thị lỗi encoding
**Nguyên nhân**:
- File không phải UTF-8
- Special characters

**Giải pháp**:
```bash
# Convert to UTF-8
iconv -f ISO-8859-1 -t UTF-8 file.txt > file_utf8.txt
```

---

## 🚀 Performance Tips

### Large Files
- Files > 1MB: Chỉ hiển thị info, không load content
- Images: Tự động scale xuống
- PDF: Browser native viewer (efficient)

### Optimization
- Lazy loading thumbnails
- Content loaded on demand
- Modal reuse (không recreate mỗi lần)

---

## 📊 Supported MIME Types

```
Images:
- image/jpeg
- image/png
- image/gif
- image/bmp
- image/webp
- image/svg+xml

Documents:
- application/pdf
- text/plain
- text/html
- text/css
- text/javascript
- application/json
- application/xml
- text/csv
- text/markdown

Office:
- application/msword
- application/vnd.openxmlformats-officedocument.wordprocessingml.document
- application/vnd.ms-excel
- application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
- application/vnd.ms-powerpoint
- application/vnd.openxmlformats-officedocument.presentationml.presentation

Archives:
- application/zip
- application/x-rar-compressed
- application/x-7z-compressed
- application/x-tar
- application/gzip
```

---

## ✨ Future Enhancements

- [ ] Video preview (MP4, WebM)
- [ ] Audio preview (MP3, WAV)
- [ ] Office 365 online viewer integration
- [ ] Syntax highlighting cho code files
- [ ] Markdown rendered preview
- [ ] Multi-page PDF navigation
- [ ] Image gallery view
- [ ] Fullscreen mode
- [ ] Zoom controls for images
- [ ] Compare documents side-by-side

---

**Version**: 2.1.0  
**Last Updated**: 2025-11-19  
**Tested on**: Chrome, Firefox, Safari, Edge
