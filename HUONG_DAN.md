# Cập nhật mới nhất

Đang dùng giao diện bản 3. Xem `NANG_CAP_V3.md` để cập nhật dự án đang có.

# LionPC — Website bán máy tính và build PC

Laravel 12, PHP 8.2+, Blade, CSS, JavaScript; cơ sở dữ liệu mục tiêu MySQL của XAMPP.
Có 9 danh mục, 27 sản phẩm minh họa, trang khách hàng và trang quản trị.

## Cài vào dự án đã có của bạn

1. Dừng `php artisan serve` bằng Ctrl+C. Sao lưu thư mục `D:\xampp\htdocs\lionpc` và CSDL nếu đã có dữ liệu cần giữ.
2. Giải nén ZIP. Chép nội dung bên trong thư mục `lionpc` vào `D:\xampp\htdocs\lionpc`, chọn ghi đè mã nguồn. Giữ tệp `.env` và thư mục `vendor` đang có trên máy.
3. Mở XAMPP Control Panel, bật **MySQL**. Bật thêm Apache khi cần dùng phpMyAdmin để tạo CSDL `lionpc` với utf8mb4. Không cần Apache để phục vụ trang nếu dùng `php artisan serve`.
4. Mở CMD và chạy:

```bat
set "PATH=D:\xampp\php;%PATH%"
cd /d D:\xampp\htdocs\lionpc
composer install
```

5. Nếu chưa có `.env`, chạy `copy .env.example .env`. Đảm bảo cấu hình:

```dotenv
APP_NAME=LionPC
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lionpc
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Tài khoản/mật khẩu/cổng phải khớp XAMPP của bạn. Nếu APP_KEY còn trống, chạy `php artisan key:generate`. Giữ khóa cũ nếu dự án đã có.

6. Chạy:

```bat
php artisan optimize:clear
php artisan migrate --seed
php artisan serve
```

7. Mở **http://127.0.0.1:8000**. Trang quản trị: **http://127.0.0.1:8000/admin**.

Không cần `npm install` hoặc `npm run dev`. Tài nguyên nằm trong `public/css`, `public/js`, `public/images`.
Lệnh `migrate --seed` thêm các bảng còn thiếu và dữ liệu mẫu; seeder không đổi mật khẩu tài khoản đã tồn tại, không đặt lại tồn kho đã thay đổi. Không dùng `migrate:fresh` trên CSDL có dữ liệu cần giữ.

## Tài khoản mẫu

| Vai trò | Email | Mật khẩu |
| --- | --- | --- |
| Quản trị viên | admin@lionpc.test | LionPC@123 |
| Khách hàng | khach@lionpc.test | LionPC@123 |

Các tài khoản này được tạo bởi seeder cho bản demo cục bộ.

## Kịch bản trình bày khoảng 7 phút

1. Trang chủ → tìm kiếm → lọc danh mục CPU → mở chi tiết sản phẩm.
2. Đăng nhập tài khoản khách. Mở Build PC, chọn:
   - CPU LionLab C4 5600; mainboard LionBoard B550 M; RAM LionMemory D4 16.
   - GPU LionGraphics G60 8GB; SSD LionDrive NVMe 500 GB.
   - Nguồn LionPower 550W; vỏ LionCase Air M; tản LionCool Tower 4.
   - Tổng dữ liệu mẫu: **14.820.000 đồng**, nguồn đề xuất **350 W**.
3. Đổi CPU sang C5 7600 để thấy lỗi socket; đổi về C4 5600. Lưu cấu hình rồi mở lại từ Cấu hình đã lưu.
4. Thêm cấu hình vào giỏ, điền người nhận và đặt hàng COD. Xem đơn; hủy đơn chờ để minh họa hoàn tồn kho.
5. Đăng xuất, đăng nhập admin. Thêm/sửa sản phẩm; thêm danh mục phụ rồi xóa khi chưa có sản phẩm.
6. Tạo đơn khác và chuyển lần lượt Chờ xác nhận → Đã xác nhận → Đang giao → Hoàn thành. Dashboard chỉ cộng doanh thu đơn hoàn thành.
7. Giới thiệu sơ đồ dữ liệu và lớp `BuildChecker` trong báo cáo.

## Chức năng và giới hạn

- Khách: đăng ký/đăng nhập, tìm/lọc/sắp xếp, giỏ hàng, đặt COD, xem/hủy đơn chờ của mình.
- Build PC: kiểm tra socket, DDR, kích thước mainboard/vỏ, socket tản và nguồn ước tính; lưu/xóa cấu hình, mở lại và thêm giỏ.
- Cho lưu cấu hình chưa đủ linh kiện nếu không có xung đột; hiển thị cảnh báo thiếu. Mở lại rồi lưu sẽ tạo bản mới.
- Quản trị: thống kê, CRUD sản phẩm/danh mục, cập nhật trạng thái đơn. Sản phẩm có lịch sử chuyển sang ngừng bán khi xóa.
- Sản phẩm, thương hiệu và giá chỉ minh họa. Ảnh là hình SVG theo nhóm. Chưa tích hợp thanh toán, vận chuyển, email, khôi phục mật khẩu, upload ảnh riêng.
- Tương thích mới là kiểm tra cơ bản, chưa bao gồm BIOS, QVL, khe SSD, kích thước GPU/tản, đầu cấp điện.
- Đã chạy 14 kiểm thử với 101 assertions trên PHP 8.2.32/SQLite và thao tác trình duyệt. Chưa kiểm thử MySQL/Windows hoặc tải đồng thời; cần chạy lại trên XAMPP trước nộp.

## Kiểm thử

```bat
php artisan test
```

PHPUnit dùng SQLite `:memory:` tách biệt CSDL demo; cần bật extension `pdo_sqlite` trong PHP. Để chạy ứng dụng MySQL cần `pdo_mysql`.

## Cấu trúc nên nắm khi bảo vệ

- `routes/web.php`: URL và middleware.
- `app/Http/Controllers`: xác thực, bán hàng, build, quản trị.
- `app/Services/BuildChecker.php`: quy tắc tương thích, tổng giá, công suất đề xuất.
- `database/migrations`: cấu trúc bảng và quan hệ.
- `database/seeders`: dữ liệu demo.
- `resources/views`: giao diện Blade.
- `tests/Feature/ShopTest.php`: các ca kiểm thử chức năng.

## Sửa lỗi thường gặp

- `php is not recognized`: chạy lại lệnh set PATH trong đúng cửa sổ CMD.
- `Connection refused` / SQLSTATE: bật MySQL và kiểm tra cổng, DB_DATABASE, tài khoản; chạy `php artisan optimize:clear`.
- `Unknown database lionpc`: tạo CSDL trong phpMyAdmin rồi chạy migration.
- `No application encryption key`: chạy `php artisan key:generate` khi APP_KEY chưa được tạo.
- `vendor/autoload.php` không tồn tại: chạy `composer install` trong thư mục chứa artisan.
- `could not find driver`: bật extension PDO tương ứng trong `D:\xampp\php\php.ini` rồi mở lại CMD.
- Lỗi 419: tải lại trang để tạo token phiên mới, đăng nhập lại nếu cần.
- Cổng 8000 bận: dùng `php artisan serve --port=8001`, mở đúng địa chỉ 8001.
- Không mở `localhost/lionpc` như một dự án PHP thuần; ưu tiên địa chỉ Artisan ở trên. Nếu cấu hình Apache riêng, DocumentRoot phải trỏ đến thư mục `public`.

## Báo cáo

Điền tên, mã sinh viên, lớp và giảng viên trên bìa Word. Nội dung 3 chương khớp chức năng hiện có. Đọc lại thuật toán, quan hệ dữ liệu và chạy kịch bản trước khi trình bày. Nếu sửa nội dung làm đổi số trang, cập nhật mục lục tương ứng.
