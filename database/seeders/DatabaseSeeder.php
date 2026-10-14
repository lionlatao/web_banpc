<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Chỉ tạo tài khoản mẫu khi chưa tồn tại; không đổi mật khẩu khi chạy lại.
        foreach ([['Quản trị LionPC', 'admin@lionpc.test', true], ['Khách hàng mẫu', 'khach@lionpc.test', false]] as [$name,$email,$admin]) {
            if (! User::where('email', $email)->exists()) {
                $u = new User;
                $u->name = $name;
                $u->email = $email;
                $u->password = Hash::make('LionPC@123');
                $u->is_admin = $admin;
                $u->save();
            }
        }
        $categories = ['cpu' => 'CPU', 'mainboard' => 'Mainboard', 'ram' => 'RAM', 'gpu' => 'Card đồ họa', 'ssd' => 'SSD', 'psu' => 'Nguồn', 'case' => 'Vỏ máy', 'cooler' => 'Tản nhiệt', 'pc' => 'PC lắp sẵn'];
        foreach ($categories as $slug => $name) {
            Category::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }
        $data = json_decode(file_get_contents(database_path('seeders/products.json')), true);
        foreach ($data as $row) {
            Product::firstOrCreate(['name' => $row['name']], [
                'category_id' => Category::where('slug', $row['slot'])->value('id'), 'brand' => 'LionLab',
                'description' => 'Sản phẩm và giá minh họa phục vụ đồ án LionPC. Thông số được rút gọn để thử nghiệm chức năng; không dùng làm tư vấn mua hàng thực tế.',
                'price' => $row['price'], 'stock' => $row['stock'], 'specs' => $row['specs'], 'active' => true,
            ]);
        }
    }
}
