<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShopController extends Controller
{
    public function home()
    {
        return view('shop.home', ['products' => Product::with('category')->where('active', true)->where('stock', '>', 0)->latest('id')->take(8)->get(), 'categories' => Category::all()]);
    }

    public function catalog(Request $r)
    {
        $data = $r->validate(['q' => 'nullable|string|max:100', 'category' => 'nullable|string|max:50', 'brand' => 'nullable|string|max:50', 'min' => 'nullable|integer|min:0', 'max' => 'nullable|integer|min:0', 'sort' => 'nullable|in:new,price_asc,price_desc', 'in_stock' => 'nullable|boolean']);
        $query = Product::with('category')->where('active', true);
        if ($r->filled('q')) {
            $query->where('name', 'like', '%'.$r->q.'%');
        }
        if ($r->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $r->category));
        }
        if ($r->filled('brand')) {
            $query->where('brand', $r->brand);
        }
        if ($r->filled('min')) {
            $query->where('price', '>=', $r->min);
        }
        if ($r->filled('max')) {
            $query->where('price', '<=', $r->max);
        }
        if ($r->boolean('in_stock')) { $query->where('stock', '>', 0); }
        match ($r->sort) {
            'price_asc' => $query->orderBy('price'),'price_desc' => $query->orderByDesc('price'),default => $query->latest('id')
        };

        return view('shop.catalog', ['products' => $query->paginate(12)->withQueryString(), 'categories' => Category::all(), 'brands' => Product::where('active', true)->distinct()->orderBy('brand')->pluck('brand')]);
    }

    public function product(Request $r, Product $product)
    {
        abort_unless($product->active, 404);
        $product->load('category');

        $recent = array_values(array_diff($r->session()->get('recent', []), [$product->id]));
        array_unshift($recent, $product->id);
        $r->session()->put('recent', array_slice($recent, 0, 12));
        $related = Product::with('category')->where('active', true)->where('category_id', $product->category_id)->where('id', '!=', $product->id)->take(4)->get();
        return view('shop.product', compact('product', 'related'));
    }

    public function contact()
    {
        return view('shop.contact');
    }

    public function cart(Request $r)
    {
        return view('shop.cart', $this->cartData($r));
    }

    private function cartData(Request $r): array
    {
        $cart = $r->session()->get('cart', []);
        $products = Product::with('category')->whereIn('id', array_keys($cart))->get();
        $total = $products->sum(fn ($p) => $p->price * $cart[$p->id]);

        return compact('cart', 'products', 'total');
    }

    public function add(Request $r, Product $product)
    {
        $r->validate(['quantity' => 'required|integer|min:1|max:99']);
        $cart = $r->session()->get('cart', []);
        $qty = ($cart[$product->id] ?? 0) + (int) $r->quantity;
        if (! $product->active || $qty > $product->stock) {
            throw ValidationException::withMessages(['cart' => 'Số lượng vượt tồn kho hoặc sản phẩm đã ngừng bán.']);
        }
        $cart[$product->id] = $qty;
        $r->session()->put('cart', $cart);

        return back()->with('success', 'Đã thêm sản phẩm vào giỏ.');
    }

    public function update(Request $r, Product $product)
    {
        $r->validate(['quantity' => 'required|integer|min:0|max:99']);
        $cart = $r->session()->get('cart', []);
        if ((int) $r->quantity === 0) {
            unset($cart[$product->id]);
        } else {
            if (! $product->active || $r->quantity > $product->stock) {
                throw ValidationException::withMessages(['cart' => 'Sản phẩm ngừng bán hoặc không đủ tồn kho.']);
            }
            $cart[$product->id] = (int) $r->quantity;
        }
        $r->session()->put('cart', $cart);

        return back()->with('success', 'Đã cập nhật giỏ hàng.');
    }

    public function checkout(Request $r)
    {
        $data = $this->cartData($r);
        if ($data['products']->isEmpty()) {
            return redirect()->route('cart');
        }
        if (! $r->session()->has('checkout_token')) {
            $r->session()->put('checkout_token', Str::random(40));
        }

        return view('shop.checkout', $data);
    }

    public function place(Request $r)
    {
        $d = $r->validate(['recipient' => 'required|string|max:100', 'phone' => ['required', 'regex:/^[0-9+ ()-]{9,20}$/'], 'address' => 'required|string|min:10|max:500', 'note' => 'nullable|string|max:1000', 'checkout_token' => 'required|string']);
        // Token duy nhất chống gửi lại đơn khi nhấp hai lần.
        $old = Order::where('checkout_token', $d['checkout_token'])->where('user_id', $r->user()->id)->first();
        if ($old) {
            return redirect()->route('orders.show', $old);
        }
        abort_unless(hash_equals((string) $r->session()->get('checkout_token'), $d['checkout_token']), 419);
        $cart = $r->session()->get('cart', []);
        if (! $cart) {
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng trống.']);
        }
        $order = DB::transaction(function () use ($r, $d, $cart) {
            $products = Product::whereIn('id', array_keys($cart))->orderBy('id')->lockForUpdate()->get();
            if ($products->count() !== count($cart)) {
                throw ValidationException::withMessages(['cart' => 'Sản phẩm không còn tồn tại.']);
            }
            $total = 0;
            foreach ($products as $p) {
                if (! $p->active || $p->stock < $cart[$p->id]) {
                    throw ValidationException::withMessages(['cart' => "$p->name không đủ hàng."]);
                }$total += $p->price * $cart[$p->id];
            }
            $o = Order::create([...$d, 'user_id' => $r->user()->id, 'total' => $total, 'status' => 'pending']);
            foreach ($products as $p) {
                $o->items()->create(['product_id' => $p->id, 'product_name' => $p->name, 'price' => $p->price, 'quantity' => $cart[$p->id]]);
                $p->decrement('stock', $cart[$p->id]);
            }

            return $o;
        });
        $r->session()->forget(['cart', 'checkout_token']);

        return redirect()->route('orders.show', $order)->with('success', 'Đặt hàng thành công. Thanh toán khi nhận hàng (COD).');
    }

    public function orders(Request $r)
    {
        return view('shop.orders', ['orders' => Order::where('user_id', $r->user()->id)->latest()->paginate(10)]);
    }

    public function order(Request $r, Order $order)
    {
        abort_unless($order->user_id === $r->user()->id, 403);
        $order->load('items');

        return view('shop.order', compact('order'));
    }

    public function cancel(Request $r, Order $order)
    {
        abort_unless($order->user_id === $r->user()->id, 403);
        DB::transaction(function () use ($order) {
            $o = Order::lockForUpdate()->findOrFail($order->id);
            if ($o->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Chỉ hủy được đơn đang chờ xác nhận.']);
            }
            foreach ($o->items()->orderBy('product_id')->get() as $item) {
                Product::whereKey($item->product_id)->increment('stock',$item->quantity);
            }$o->update(['status' => 'cancelled']);
        });

        return back()->with('success','Đã hủy đơn và hoàn lại tồn kho.');
    }
}
