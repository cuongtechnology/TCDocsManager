# 📄 Hướng dẫn Preview Tài liệu Office

## 🎯 Tổng quan

Hệ thống giờ hỗ trợ preview trực tiếp các file Office (Word, Excel, PowerPoint) trong browser mà không cần tải về!

---

## ✨ Loại File được hỗ trợ

### 📘 Word Documents
- ✅ `.doc` - Microsoft Word 97-2003
- ✅ `.docx` - Microsoft Word 2007+
- ✅ `.odt` - OpenDocument Text

### 📗 Excel Spreadsheets  
- ✅ `.xls` - Microsoft Excel 97-2003
- ✅ `.xlsx` - Microsoft Excel 2007+
- ✅ `.ods` - OpenDocument Spreadsheet
- ✅ `.csv` - Comma-Separated Values

### 📙 PowerPoint Presentations
- ✅ `.ppt` - Microsoft PowerPoint 97-2003
- ✅ `.pptx` - Microsoft PowerPoint 2007+
- ✅ `.odp` - OpenDocument Presentation

---

## 🔍 Cách Preview hoạt động

### Bước 1: Click vào file
```
[Table Row] → Click tên file hoặc nút 👁️
```

### Bước 2: Modal mở ra
```
┌─────────────────────────────────────────┐
│ Filename.docx • 2.5MB                  ×│
├─────────────────────────────────────────┤
│                                         │
│  [Document Preview in Iframe]          │
│                                         │
├─────────────────────────────────────────┤
│ [🔄 Google] [🔄 Microsoft] [⬇️ Download]│
│ 💡 Lưu ý: Nếu không hiển thị, thử...  │
└─────────────────────────────────────────┘
```

### Bước 3: Xem tài liệu
- Document hiển thị trong iframe
- Có thể scroll, zoom (tuỳ viewer)
- Full content preview

---

## 🔄 Hai Viewer Options

### 1️⃣ Google Docs Viewer (Mặc định)

**URL**: `https://docs.google.com/viewer`

**Ưu điểm**:
- ✅ Nhanh, ổn định
- ✅ Hỗ trợ nhiều format
- ✅ Render tốt cho hầu hết files
- ✅ Không cần tài khoản Microsoft

**Nhược điểm**:
- ⚠️ Cần file accessible public
- ⚠️ Đôi khi load chậm với file lớn
- ⚠️ Một số format phức tạp có thể bị lỗi

**Khi nào dùng**: 
- File standard Office format
- File size < 25MB
- Không có macro/script phức tạp

---

### 2️⃣ Microsoft Office Online Viewer (Backup)

**URL**: `https://view.officeapps.live.com`

**Ưu điểm**:
- ✅ Official Microsoft viewer
- ✅ Render chính xác format
- ✅ Hỗ trợ Office features đầy đủ
- ✅ Tốt cho file phức tạp

**Nhược điểm**:
- ⚠️ Đôi khi load chậm hơn
- ⚠️ Có thể bị limit rate
- ⚠️ Requires proper headers

**Khi nào dùng**:
- Google Viewer không load được
- File có formatting phức tạp
- Cần xem chính xác 100%

---

## 🎮 Cách sử dụng

### Preview Office File

**1. Mở file**:
```
Click tên file trong table → Modal mở
```

**2. Xem preview**:
```
Document tự động load trong iframe
Đợi vài giây để render
```

**3. Nếu không hiển thị**:
```
Click [🔄 Google Viewer] - Thử lại với Google
Click [🔄 Microsoft Viewer] - Chuyển sang Microsoft
Click [⬇️ Download] - Tải về xem offline
```

**4. Đóng preview**:
```
Click nút X ở góc
Hoặc click outside modal
```

---

## 📋 Preview các loại file cụ thể

### Word Documents (.docx)

**Hiển thị**:
- ✅ Text formatting (bold, italic, underline)
- ✅ Headings và styles
- ✅ Tables
- ✅ Images (embedded)
- ✅ Lists (bullet, numbered)
- ⚠️ Comments (limited)
- ⚠️ Track changes (limited)

**Best practices**:
- Giữ format đơn giản
- Tránh macro phức tạp
- Embed images (không link)

---

### Excel Spreadsheets (.xlsx)

**Hiển thị**:
- ✅ Cell values và formulas
- ✅ Formatting (colors, borders)
- ✅ Multiple sheets
- ✅ Charts (basic)
- ⚠️ Pivot tables (limited)
- ⚠️ Macros (không chạy)

**Best practices**:
- Data-only sheets preview tốt nhất
- Charts đơn giản
- Tránh macro/VBA

**CSV Files**:
- Preview as plain text
- Shows comma-separated data
- Can be opened in Excel after download

---

### PowerPoint Presentations (.pptx)

**Hiển thị**:
- ✅ Slides layout
- ✅ Text content
- ✅ Images
- ✅ Basic animations (limited)
- ⚠️ Videos (không play)
- ⚠️ Complex transitions (limited)

**Best practices**:
- Static slides preview tốt nhất
- Embed all media
- Simple layouts

---

## ⚠️ Giới hạn và Lưu ý

### Kích thước file

**Google Docs Viewer**:
- Max: ~25MB
- Recommended: < 10MB
- Timeout: 30 seconds

**Microsoft Office Viewer**:
- Max: ~50MB  
- Recommended: < 20MB
- Timeout: 60 seconds

**Nếu file quá lớn**:
```
→ Preview sẽ không load
→ Sử dụng nút Download
→ Xem offline với Office/LibreOffice
```

---

### Yêu cầu kỹ thuật

**Server**:
- ✅ File phải accessible via HTTP/HTTPS
- ✅ Proper MIME types
- ✅ CORS headers (nếu cần)

**Client**:
- ✅ Modern browser (Chrome, Firefox, Edge, Safari)
- ✅ JavaScript enabled
- ✅ Cookies enabled
- ✅ Internet connection

---

### Các trường hợp không preview được

**1. File bị corrupt**:
```
Error: Cannot display document
→ Download và check file integrity
```

**2. Format không supported**:
```
Error: Unsupported file type
→ Convert to standard format
→ Hoặc download để mở
```

**3. Viewer timeout**:
```
Error: Loading timeout
→ Try switching viewer
→ Hoặc download file
```

**4. File quá phức tạp**:
```
Error: Rendering failed
→ Download for full fidelity
→ Mở bằng Office app
```

---

## 🛠️ Troubleshooting

### Preview không hiển thị gì

**Nguyên nhân**: Viewer chưa load xong

**Giải pháp**:
1. Đợi 10-15 giây
2. Refresh modal (đóng mở lại)
3. Try viewer khác
4. Download file

---

### Preview bị lỗi format

**Nguyên nhân**: File có format phức tạp

**Giải pháp**:
1. Switch sang Microsoft Viewer
2. Download file
3. Mở bằng Office/LibreOffice
4. Simplify document formatting

---

### Preview rất chậm

**Nguyên nhân**: File lớn hoặc mạng chậm

**Giải pháp**:
1. Check file size (nên < 10MB)
2. Check internet connection
3. Compress file nếu có thể
4. Download để xem offline

---

### Viewer bị block

**Nguyên nhân**: Firewall/proxy

**Giải pháp**:
1. Check network settings
2. Allow Google/Microsoft domains:
   - `docs.google.com`
   - `view.officeapps.live.com`
3. Contact IT support
4. Use download option

---

## 💡 Tips & Best Practices

### Cho Admin - Upload files

**1. Optimize files trước khi upload**:
```bash
# Giảm file size
# Loại bỏ track changes
# Embed images properly
# Remove unused styles
```

**2. Naming convention**:
```
Good: Quy-trinh-kham-benh-v2.docx
Bad: QTính!!@#$%^&*().doc
```

**3. File organization**:
```
/tai-lieu-ky-thuat/
  ├── huong-dan/
  ├── quy-trinh/
  └── bieu-mau/
```

---

### Cho Users - Xem files

**1. Preview trước khi download**:
```
Preview nhanh → Xác nhận đúng file → Download
```

**2. Nếu preview lỗi**:
```
Try: Google → Microsoft → Download
```

**3. Với file quan trọng**:
```
Always download để backup
Không rely 100% vào preview
```

---

## 🔐 Bảo mật

### Data Privacy

**Google Docs Viewer**:
- ⚠️ File URL sent to Google servers
- ⚠️ Document rendered on Google side
- ✅ Temporary (không lưu permanent)
- ✅ HTTPS encrypted

**Microsoft Office Viewer**:
- ⚠️ File URL sent to Microsoft servers
- ⚠️ Document rendered on Microsoft side
- ✅ Temporary processing
- ✅ HTTPS encrypted

**Recommendations**:
- 🔒 Không dùng preview cho tài liệu mật
- 🔒 Sensitive files nên download và mở offline
- 🔒 Internal-only documents: cân nhắc kỹ

---

## 📊 Supported vs Not Supported

### ✅ Supported Features

**Word**:
- Text, headings, paragraphs
- Bold, italic, underline
- Tables, lists
- Images (embedded)
- Page layout (basic)

**Excel**:
- Cell data
- Formulas (displayed, not calculated)
- Formatting
- Multiple sheets
- Basic charts

**PowerPoint**:
- Slide content
- Images
- Text boxes
- Layouts
- Basic shapes

---

### ❌ Not Supported / Limited

**All formats**:
- Macros/VBA (không chạy)
- Active content (scripts)
- External links (có thể broken)
- Fonts (fallback nếu không có)

**Word specific**:
- Track changes (limited)
- Comments (limited)
- Complex mail merge

**Excel specific**:
- Pivot tables (limited)
- Complex formulas (không calc)
- Add-ins

**PowerPoint specific**:
- Animations (limited)
- Videos (không play)
- Audio (không play)
- Transitions (limited)

---

## 📈 Future Improvements

Planned features:
- [ ] LibreOffice Online integration
- [ ] OnlyOffice viewer option
- [ ] Local preview without external services
- [ ] PDF conversion for Office files
- [ ] Thumbnail generation
- [ ] Full-text search in documents

---

## 🔗 External Resources

**Google Docs Viewer**:
- Docs: https://support.google.com/drive/answer/2881970

**Microsoft Office Viewer**:
- Docs: https://docs.microsoft.com/office/viewer

**Alternative tools**:
- LibreOffice Online: https://www.libreoffice.org/
- OnlyOffice: https://www.onlyoffice.com/

---

## 📞 Support

Nếu gặp vấn đề với Office preview:

1. Check file format và size
2. Try both viewers
3. Download file as backup
4. Contact IT support với:
   - File name
   - File size
   - Error message
   - Browser version

---

**Version**: 3.1.0  
**Last Updated**: 2025-11-19  
**Feature**: Office Files Preview with External Viewers
