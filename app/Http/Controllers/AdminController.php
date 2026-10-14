<?php

namespace App\Http\Controllers;

use App\Models\BuildItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\BuildChecker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    const STATUS = ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'shipping' => 'Đang giao', 'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy'];

    public function dashboard()
    {
        return view('admin.dashboard', ['revenue' => Order::where('status', 'completed')->sum('total'), 'count' => Order::count(), 'pending' => Order::where('status', 'pending')->count(), 'products' => Product::count(), 'orders' => Order::with('user')->latest()->take(8)->get(), 'low' => Product::where('active', true)->where('stock', '<', 5)->get()]);
    }

    public function products(Request $r)
    {
        return view('admin.products', ['products' => Product::with('category')->when($r->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$r->q.'%'))->latest('id')->paginate(15)->withQueryString()]);
    }

    public function create()
    {
        return view('admin.product-form', ['product' => new Product(['active' => true, 'stock' => 10]), 'categories' => Category::all()]);
    }

    public function edit(Product $product)
    {
        return view('admin.product-form', ['product' => $product, 'categories' => Category::all()]);
    }

    private function data(Request $r): array
    {
        $d = $r->validate(['name' => 'required|string|max:180', 'brand' => 'required|string|max:50', 'description' => 'required|string|max:5000', 'category_id' => 'required|exists:categories,id', 'price' => 'required|integer|min:1000|max:1000000000', 'stock' => 'required|integer|min:0|max:100000', 'active' => 'nullable|boolean', 'specs' => 'nullable|array', 'specs.*' => 'nullable|string|max:200']);
        $slug = Category::findOrFail($d['category_id'])->slug;
        $required = match ($slug) {
            'cpu' => ['socket', 'power'],'mainboard' => ['socket', 'ram_type', 'form_factor'],'ram' => ['ram_type'],'gpu' => ['power'],'psu' => ['wattage'],'case' => ['supported_forms'],'cooler' => ['supported_sockets'],default => []
        };
        $s = $d['specs'] ?? [];
        foreach ($required as $key) {
            if (trim($s[$key] ?? '') === '') {
                throw ValidationException::withMessages(["specs.$key" => "Thiếu thông số bắt buộc: $key."]);
            }
        }
        foreach (['power', 'wattage'] as $key) {
            if (! empty($s[$key]) && (! ctype_digit($s[$key]) || (int) $s[$key] < 1 || (int) $s[$key] > 5000)) {
                throw ValidationException::withMessages(["specs.$key" => 'Công suất phải là số nguyên từ 1 đến 5000 W.']);
            }
        }
        if (in_array($slug, ['ram', 'mainboard']) && ! in_array($s['ram_type'], ['DDR4', 'DDR5'])) {
            throw ValidationException::withMessages(['specs.ram_type' => 'Chỉ hỗ trợ DDR4 hoặc DDR5.']);
        }
        if ($slug === 'mainboard' && ! in_array($s['form_factor'], ['ATX', 'mATX', 'ITX'])) {
            throw ValidationException::withMessages(['specs.form_factor' => 'Chuẩn mainboard phải là ATX, mATX hoặc ITX.']);
        }
        $d['specs'] = array_filter($s, fn ($v) => $v !== null && $v !== '');
        $d['active'] = $r->boolean('active');

        return $d;
    }

    public function store(Request $r)
    {
        Product::create($this->data($r));

        return redirect()->route('admin.products')->with('success', 'Đã thêm sản phẩm.');
    }

    public function update(Request $r, Product $product)
    {
        $d = $this->data($r);
        if ($d['category_id'] != $product->category_id && BuildItem::where('product_id', $product->id)->exists()) {
            throw ValidationException::withMessages(['category_id' => 'Sản phẩm đã được lưu trong cấu hình; không thể đổi danh mục.']);
        }
        $product->update($d);

        return redirect()->route('admin.products')->with('success', 'Đã cập nhật sản phẩm.');
    }

    public function destroy(Product $product)
    {
        if (OrderItem::where('product_id', $product->id)->exists() || BuildItem::where('product_id', $product->id)->exists()) {
            $product->update(['active' => false]);

            return back()->with('success', 'Sản phẩm đã có lịch sử: đã chuyển sang ngừng bán để giữ dữ liệu.');
        }
        $product->delete();

        return back()->with('success', 'Đã xóa sản phẩm.');
    }

    public function categories()
    {
        return view('admin.categories', ['categories' => Category::withCount('products')->get()]);
    }

    public function categorySave(Request $r, ?Category $category = null)
    {
        $d = $r->validate(['name' => 'required|string|max:80', 'slug' => ['required', 'regex:/^[a-z0-9-]+$/', 'max:50', Rule::unique('categories')->ignore($category?->id)]]);
        if ($category?->exists && $category->slug !== $d['slug'] && ($category->products()->exists() || array_key_exists($category->slug, BuildChecker::SLOTS))) {
            throw ValidationException::withMessages(['slug' => 'Không đổi mã danh mục linh kiện hoặc danh mục đã có sản phẩm.']);
        }
        if ($category?->exists) {
            $category->update($d);
        } else {
            Category::create($d);
        }

return back()->with('success', 'Đã lưu danh mục.');
    }

    public function categoryDelete(Category $category)
    {
        if ($category->products()->exists() || array_key_exists($category->slug, BuildChecker::SLOTS)) {
            throw ValidationException::withMessages(['category' => 'Không xóa danh mục build PC hoặc danh mục đang có sản phẩm.']);
        }
        $category->delete();

        return back()->with('success', 'Đã xóa danh mục.');
    }

    public function orders(Request $r)
    {
        $r->validate(['status' => 'nullable|in:pending,confirmed,shipping,completed,cancelled']);

        return view('admin.orders', ['orders' => Order::with('user')->when($r->filled('status'), fn ($q) => $q->where('status', $r->status))->latest()->paginate(15)->withQueryString()]);
    }

    public function order(Order $order)
    {
        $order->load('items', 'user');

        return view('admin.order', compact('order'));
    }

    public function status(Request $r, Order $order)
    {
        $r->validate(['status' => ['required', Rule::in(array_keys(self::STATUS))]]);
        DB::transaction(function () use ($r, $order) {
            $o = Order::lockForUpdate()->findOrFail($order->id);
            $allowed = ['pending' => ['confirmed', 'cancelled'], 'confirmed' => ['shipping', 'cancelled'], 'shipping' => ['completed'], 'completed' => [], 'cancelled' => []];
            if (! in_array($r->status, $allowed[$o->status])) {
                throw ValidationException::withMessages(['status' => 'Chuyển trạng thái không hợp lệ.']);
            }
            if ($r->status === 'cancelled') {
                foreach ($o->items()->orderBy('product_id')->get() as $item) {
                    Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                }
            }
            $o->update(['status' => $r->status]);
        });

        return back()->with('success','Đã cập nhật trạng thái đơn.');
    }
}
