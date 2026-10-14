# LionPC 2 — Giao diện và chức năng bổ sung

## Cập nhật từ bản trước trên Windows/XAMPP
1. Sao lưu thư mục D:\xampp\htdocs\lionpc.
2. Giải nén ZIP. Chép nội dung bên trong thư mục lionpc vào D:\xampp\htdocs\lionpc, chọn thay thế các file trùng tên. Giữ nguyên .env và vendor của bạn.
3. Mở XAMPP Control Panel và Start MySQL. Nếu dùng php artisan serve thì không bắt buộc bật Apache.
4. Mở CMD và chạy:

```bat
cd /d D:\xampp\htdocs\lionpc
php artisan optimize:clear
php artisan serve
```

5. Truy cập http://127.0.0.1:8000 rồi nhấn Ctrl+F5.

Bản nâng cấp không thay đổi cấu trúc CSDL; không cần chạy migrate:fresh hay xóa dữ liệu. Nếu cài mới, làm theo HUONG_DAN.md (composer install, .env, tạo database, key:generate, migrate --seed).
Nếu gặp SQLSTATE [HY000] [2002], kiểm tra MySQL đang chạy và DB_HOST/DB_PORT trong .env đúng với XAMPP.

## Những điểm mới
- Giao diện cửa hàng mới: banner, danh mục, khu vực khám phá, thẻ sản phẩm và bố cục điện thoại.
- Yêu thích: nhấn ♡ trên thẻ hoặc trang chi tiết; nhấn lại để bỏ. Tối đa 50 sản phẩm.
- So sánh: thêm tối đa 4 sản phẩm rồi mở So sánh trên thanh điều hướng; bảng đối chiếu giá, tồn kho và hợp nhất các thông số. Có thể bỏ từng sản phẩm.
- Đã xem: tự lưu 12 sản phẩm gần nhất, không lặp.
- Lọc hàng còn kho trong danh sách sản phẩm.
- Sản phẩm cùng danh mục tại trang chi tiết.
- Build PC: nhập tên để tìm trong các danh sách linh kiện; bật lọc mainboard theo socket CPU, RAM theo DDR của mainboard. Các lựa chọn đã chọn vẫn được giữ để máy chủ báo xung đột.
- Ngân sách: nhập số tiền để thấy số dư hoặc mức vượt ngân sách. Không tự chặn mua hàng khi vượt ngân sách.
- Thanh tiến độ 8 linh kiện.
- In/lưu PDF cấu hình: bấm nút trong Build PC, chọn Save as PDF trong hộp in của trình duyệt. Đây là dự toán, không phải hóa đơn.

## Phạm vi và giới hạn
Yêu thích, so sánh và đã xem lưu trong session của trình duyệt, không đồng bộ giữa thiết bị và có thể mất khi phiên hết hạn/đăng xuất. Không cần đăng nhập để sử dụng.
Bộ lọc tương thích phía trình duyệt hỗ trợ socket/DDR; kết quả kiểm tra cuối cùng vẫn do backend thực hiện theo các quy tắc BuildChecker. Hình sản phẩm là hình minh họa theo nhóm, dữ liệu và giá là mẫu cho đồ án.

## Nội dung bổ sung cho báo cáo
Bản Word trước mô tả phiên bản đầu. Khi hoàn thiện báo cáo bản 2, cập nhật ảnh trang chủ, trang sản phẩm và trang Build PC; thêm use case Quản lý yêu thích, So sánh sản phẩm, Xem lịch sử sản phẩm và In dự toán cấu hình. Các chức năng này không tạo bảng CSDL mới vì danh sách tạm được lưu theo phiên. Trong phần kiểm thử bổ sung kiểm tra giới hạn 4 sản phẩm, chống trùng lịch sử, sản phẩm ngừng bán và lọc tồn kho.

## Kết quả kiểm thử
PHPUnit: 14 bài, 101 assertions thành công (PHP 8.2.32, SQLite kiểm thử).
Trình duyệt Chromium: yêu thích, so sánh, lịch sử, lọc socket, ngân sách, nội dung in và responsive 390px. Không thay thế kiểm thử MySQL trên XAMPP của người dùng.
