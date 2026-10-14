# LionPC 3 — Giao diện theo Taste Skill

## Cập nhật trên máy đang có LionPC

1. Dừng cửa sổ chạy website bằng Ctrl+C và sao lưu thư mục `D:\xampp\htdocs\lionpc`.
2. Giải nén ZIP, mở thư mục `lionpc` bên trong. Chép nội dung vào `D:\xampp\htdocs\lionpc`, chọn thay thế file trùng tên. Giữ `.env` và `vendor` hiện có.
3. Start MySQL trong XAMPP. Mở CMD, chạy:

```bat
cd /d D:\xampp\htdocs\lionpc
set "PATH=D:\xampp\php;%PATH%"
php artisan optimize:clear
php artisan serve
```

4. Mở http://127.0.0.1:8000 và nhấn Ctrl+F5.

Bản 3 chỉ sửa giao diện, không thay đổi bảng dữ liệu. Không chạy `migrate:fresh`. Cài mới từ đầu: xem `HUONG_DAN.md`.

## Thiết kế và tương tác

Áp dụng quy trình Scan → Diagnose → Fix của `redesign-existing-projects`:
https://github.com/Leonxlnx/taste-skill/tree/main/skills/redesign-skill

- Giữ Laravel, Blade, CSS và JavaScript hiện có.
- Font Be Vietnam Pro tải sẵn trong dự án, hỗ trợ dấu tiếng Việt, không phụ thuộc dịch vụ font bên ngoài khi chạy.
- Nền sáng, màu xanh rêu, hệ thống khoảng cách và chữ thống nhất; số tiền dùng chữ số có độ rộng cố định.
- Trang chủ dùng ảnh góc máy; thẻ sản phẩm có giá/nút mua thẳng hàng.
- Menu điện thoại mở/đóng, đóng bằng Escape và trả focus về nút Menu. Điều hướng đánh dấu trang hiện tại.
- Bộ lọc sản phẩm có thể thu gọn trên điện thoại.
- Build PC đánh dấu các linh kiện đã chọn; có trạng thái chờ kiểm tra; thu gọn phần giới hạn tương thích; liên kết đi tới tổng dự toán trên điện thoại.
- Bổ sung favicon, nhãn tìm kiếm, skip link, focus bàn phím và trang 404.
- Tôn trọng cài đặt giảm chuyển động của thiết bị.

## Kiểm chứng

- PHP 8.2.32 / SQLite: 14 bài kiểm thử, 101 assertions thành công.
- Chromium: yêu thích, so sánh, lịch sử, bộ lọc socket, cảnh báo ngân sách và in dự toán.
- Kiểm tra 6 trang tại 320/390/768 px không tràn ngang; kiểm tra menu, Escape/focus, thu gọn bộ lọc, trạng thái chọn/xóa linh kiện, trang 404 và ảnh banner.
- Xem trực tiếp ảnh chụp trang chủ, danh sách sản phẩm, chi tiết sản phẩm, Build PC và menu điện thoại.
- Chưa kiểm chứng trên Windows/MySQL của người dùng; cần chạy trên XAMPP trước khi nộp.

## Nguồn tài nguyên

- Be Vietnam Pro: https://github.com/google/fonts/tree/main/ofl/bevietnampro — giấy phép OFL đi kèm ở `public/fonts/OFL.txt`.
- Ảnh góc máy: Unsplash, https://images.unsplash.com/photo-1587831990711-23ca6441447b (bản tải về lưu tại `public/images/workspace.jpg`). Ảnh trang trí, không đại diện cho bộ máy đang bán.
- Ảnh linh kiện giữ dạng minh họa theo nhóm; sản phẩm và giá là dữ liệu demo của đồ án.

## Các file chính được sửa

- `public/css/taste.css`: lớp giao diện của bản 3.
- `public/js/shop.js`: tương tác và trạng thái.
- `resources/views/layouts/app.blade.php`: khung trang, điều hướng và tìm kiếm.
- `resources/views/shop/home.blade.php`, `catalog.blade.php`, `builder.blade.php`.
- `resources/views/errors/404.blade.php`.

Báo cáo Word đã gửi trước đó chứa ảnh giao diện bản 2; nếu dùng bản 3 để nộp, cần cập nhật ảnh giao diện tương ứng.
