<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function index(Request $request, string $kind)
    {
        abort_unless(in_array($kind, ['favorites', 'compare', 'recent']), 404);
        $ids = $request->session()->get($kind, []);
        $products = Product::with('category')->where('active', true)->whereIn('id', $ids)->get()
            ->sortBy(fn ($p) => array_search($p->id, $ids));
        return view('shop.collection', compact('kind', 'products'));
    }

    public function toggle(Request $request, string $kind, Product $product)
    {
        abort_unless(in_array($kind, ['favorites', 'compare']), 404);
        abort_unless($product->active, 404);
        $ids = $request->session()->get($kind, []);
        if (in_array($product->id, $ids)) {
            $ids = array_values(array_diff($ids, [$product->id]));
            $message = 'Đã bỏ sản phẩm khỏi danh sách.';
        } else {
            if (count($ids) >= ($kind === 'compare' ? 4 : 50)) {
                return back()->withErrors(['collection' => $kind === 'compare' ? 'Chỉ so sánh tối đa 4 sản phẩm. Hãy bỏ một sản phẩm trước.' : 'Danh sách yêu thích đã đủ 50 sản phẩm.']);
            }
            $ids[] = $product->id;
            $message = $kind === 'compare' ? 'Đã thêm vào bảng so sánh.' : 'Đã thêm vào yêu thích.';
        }
        $request->session()->put($kind, $ids);
        return back()->with('success', $message);
    }
}
