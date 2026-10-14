<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\PcBuild;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function buyer()
    {
        return User::where('email', 'khach@lionpc.test')->first();
    }

    private function admin()
    {
        return User::where('email', 'admin@lionpc.test')->first();
    }

    private function part($slug, $offset = 0)
    {
        return Product::whereHas('category', fn ($q) => $q->where('slug', $slug))->orderBy('id')->skip($offset)->first();
    }

    private function payload()
    {
        return ['recipient' => 'Khách mẫu', 'phone' => '0900000000', 'address' => 'Địa chỉ thử nghiệm tại Hà Nội', 'checkout_token' => 'test-token'];
    }

    private function orderOne()
    {
        $p = $this->part('cpu');
        $this->actingAs($this->buyer())->withSession(['cart' => [$p->id => 2], 'checkout_token' => 'test-token'])->post('/thanh-toan', $this->payload())->assertRedirect();

        return Order::firstOrFail();
    }

    public function test_public_and_admin_pages_render()
    {
        foreach (['/', '/san-pham', '/san-pham?category=cpu&sort=price_asc', '/build-pc', '/gio-hang', '/dang-nhap', '/dang-ky', '/lien-he', '/san-pham/1'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->actingAs($this->admin());
        foreach (['/admin', '/admin/products', '/admin/products/create', '/admin/products/1/edit', '/admin/categories', '/admin/orders', '/cau-hinh', '/don-hang'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_register_cannot_grant_admin()
    {
        $this->post('/dang-ky', ['name' => 'Test', 'email' => 'new@lionpc.test', 'password' => 'Password@123', 'password_confirmation' => 'Password@123', 'is_admin' => 1])->assertRedirect();
        $this->assertFalse((bool) User::where('email', 'new@lionpc.test')->first()->is_admin);
    }

    public function test_admin_routes_are_protected()
    {
        $this->get('/admin')->assertRedirect('/dang-nhap');
        $this->actingAs($this->buyer())->get('/admin')->assertForbidden();
        $this->post('/admin/products', [])->assertForbidden();
    }

    public function test_checkout_uses_database_price_and_decrements_stock_once()
    {
        $p = $this->part('cpu');
        $o = $this->orderOne();
        $this->assertEquals($p->price * 2, $o->total);
        $this->assertEquals(18, $p->fresh()->stock);
        $this->post('/thanh-toan', [...$this->payload(), 'total' => 1])->assertRedirect('/don-hang/'.$o->id);
        $this->assertEquals(1, Order::count());
        $this->assertEquals(18, $p->fresh()->stock);
        $this->get('/don-hang/'.$o->id)->assertOk();
    }

    public function test_insufficient_stock_rolls_back_entire_order()
    {
        $p = $this->part('cpu');
        $this->actingAs($this->buyer())->withSession(['cart' => [$p->id => 21], 'checkout_token' => 'test-token'])->post('/thanh-toan', $this->payload())->assertSessionHasErrors('cart');
        $this->assertEquals(0, Order::count());
        $this->assertEquals(20, $p->fresh()->stock);
    }

    public function test_cancel_restores_stock_only_once()
    {
        $o = $this->orderOne();
        $this->post('/don-hang/'.$o->id.'/huy')->assertRedirect();
        $this->assertEquals(20, $this->part('cpu')->stock);
        $this->post('/don-hang/'.$o->id.'/huy')->assertSessionHasErrors('order');
        $this->assertEquals(20, $this->part('cpu')->stock);
    }

    public function test_cannot_view_or_cancel_another_users_order()
    {
        $o = $this->orderOne();
        $this->actingAs($this->admin())->get('/don-hang/'.$o->id)->assertForbidden();
        $this->post('/don-hang/'.$o->id.'/huy')->assertForbidden();
    }

    public function test_valid_and_invalid_builds()
    {
        $parts = [];
        foreach (['cpu', 'mainboard', 'ram', 'gpu', 'ssd', 'case', 'cooler'] as $s) {
            $parts[$s] = $this->part($s)->id;
        }$parts['psu'] = $this->part('psu', 1)->id;
        $this->postJson('/build-pc/check', ['parts' => $parts])->assertOk()->assertJsonPath('errors', [])->assertJsonPath('warnings', []);
        $parts['cpu'] = $this->part('cpu', 1)->id;
        $parts['ram'] = $this->part('ram', 2)->id;
        $r = $this->postJson('/build-pc/check', ['parts' => $parts])->assertOk();
        $this->assertCount(2, $r->json('errors'));
        $this->post('/build-pc/cart', ['parts' => $parts])->assertSessionHasErrors('build');
        $this->postJson('/build-pc/check', ['parts' => ['cpu' => $this->part('ram')->id]])->assertUnprocessable();
    }

    public function test_build_rejects_small_case_cooler_and_power()
    {
        $r = $this->postJson('/build-pc/check', ['parts' => ['cpu' => $this->part('cpu', 1)->id, 'mainboard' => $this->part('mainboard', 1)->id, 'case' => $this->part('case', 2)->id, 'cooler' => $this->part('cooler', 2)->id, 'gpu' => $this->part('gpu', 2)->id, 'psu' => $this->part('psu')->id]])->assertOk();
        $this->assertCount(3, $r->json('errors'));
    }

    public function test_build_save_load_delete_and_ownership()
    {
        $this->actingAs($this->buyer())->post('/cau-hinh', ['name' => 'PC học tập', 'parts' => ['cpu' => $this->part('cpu')->id]])->assertRedirect('/cau-hinh');
        $b = PcBuild::firstOrFail();
        $this->get('/build-pc?load='.$b->id)->assertOk();
        $this->get('/cau-hinh')->assertSee('PC học tập');
        $this->actingAs($this->admin())->get('/build-pc?load='.$b->id)->assertForbidden();
        $this->delete('/cau-hinh/'.$b->id)->assertForbidden();
        $this->actingAs($this->buyer())->delete('/cau-hinh/'.$b->id)->assertRedirect();
        $this->assertDatabaseCount('build_items', 0);
    }

    public function test_admin_product_category_crud_and_validation()
    {
        $this->actingAs($this->admin())->post('/admin/categories', ['name' => 'Phụ kiện', 'slug' => 'phu-kien'])->assertRedirect();
        $c = Category::where('slug', 'phu-kien')->firstOrFail();
        $data = ['name' => 'Bàn phím mẫu', 'brand' => 'Lion', 'category_id' => $c->id, 'price' => 300000, 'stock' => 10, 'description' => 'Mô tả mẫu', 'active' => 1];
        $this->post('/admin/products', $data)->assertRedirect();
        $p = Product::where('name', $data['name'])->firstOrFail();
        $this->put('/admin/products/'.$p->id, [...$data, 'price' => 350000])->assertRedirect();
        $this->assertEquals(350000, $p->fresh()->price);
        $this->delete('/admin/categories/'.$c->id)->assertSessionHasErrors('category');
        $this->delete('/admin/products/'.$p->id)->assertRedirect();
        $this->delete('/admin/categories/'.$c->id)->assertRedirect();
        $this->assertNull($c->fresh());
        $this->post('/admin/products', [...$data, 'category_id' => Category::where('slug', 'cpu')->value('id')])->assertSessionHasErrors('specs.socket');
    }

    public function test_order_transitions_and_historical_product_preservation()
    {
        $o = $this->orderOne();
        $this->actingAs($this->admin());
        $this->get('/admin/orders/'.$o->id)->assertOk();
        $this->patch('/admin/orders/'.$o->id, ['status' => 'completed'])->assertSessionHasErrors('status');
        foreach (['confirmed', 'shipping', 'completed'] as $s) {
            $this->patch('/admin/orders/'.$o->id, ['status' => $s])->assertRedirect();
        }$this->patch('/admin/orders/'.$o->id, ['status' => 'cancelled'])->assertSessionHasErrors('status');
        $this->delete('/admin/products/'.$this->part('cpu')->id)->assertRedirect();
        $this->assertFalse($this->part('cpu')->active);
    }
    public function test_comparison_limit_toggle_and_inactive_products()
    {
        $this->withSession(['compare' => [1, 2, 3, 4]])->post('/danh-sach/compare/5')->assertSessionHasErrors('collection');
        $this->assertCount(4, session('compare'));
        $this->post('/danh-sach/compare/1')->assertRedirect();
        $this->assertEquals([2, 3, 4], session('compare'));
        $this->post('/danh-sach/compare/5')->assertRedirect();
        $this->get('/danh-sach/compare')->assertOk();
        Product::find(5)->update(['active' => false]);
        $this->post('/danh-sach/favorites/5')->assertNotFound();
        $this->post('/danh-sach/invalid/1')->assertNotFound();
    }

    public function test_recent_history_and_stock_filter()
    {
        $this->get('/san-pham/1')->assertOk();
        $this->get('/san-pham/2')->assertOk();
        $this->get('/san-pham/1')->assertOk();
        $this->assertEquals([1, 2], session('recent'));
        $p = Product::find(1);
        $p->update(['stock' => 0]);
        $this->get('/san-pham?in_stock=1')->assertOk()->assertDontSee($p->name);
        $this->get('/danh-sach/recent')->assertOk()->assertSee($p->name);
    }
}
