<?php

namespace App\Http\Controllers;

use App\Models\PcBuild;
use App\Models\Product;
use App\Services\BuildChecker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BuildController extends Controller
{
    public function index(Request $r, BuildChecker $checker)
    {
        $selected = [];
        if ($r->filled('load')) {
            $build = PcBuild::with('items')->findOrFail($r->integer('load'));
            abort_unless($r->user() && $build->user_id === $r->user()->id, 403);
            $selected = $build->items->pluck('product_id', 'slot')->all();
        }

        return view('shop.builder', ['parts' => Product::with('category')->where('active', true)->where('stock', '>', 0)->get()->groupBy(fn ($p) => $p->category->slug), 'slots' => BuildChecker::SLOTS, 'selected' => $selected]);
    }

    private function selection(Request $r, BuildChecker $checker): array
    {
        $r->validate(['parts' => 'required|array', 'parts.*' => 'nullable|integer']);

        return $checker->resolve($r->input('parts', []));
    }

    public function check(Request $r, BuildChecker $checker)
    {
        return response()->json($checker->check($this->selection($r, $checker)));
    }

    public function save(Request $r, BuildChecker $checker)
    {
        $r->validate(['name' => 'required|string|max:100']);
        $parts = $this->selection($r, $checker);
        $checker->requireValid($parts);
        DB::transaction(function () use ($r, $parts) {
            $build = PcBuild::create(['user_id' => $r->user()->id, 'name' => $r->name]);
            foreach ($parts as $slot => $p) {
                $build->items()->create(['slot' => $slot, 'product_id' => $p->id]);
            }
        });

        return redirect()->route('builds')->with('success', 'Đã lưu cấu hình.');
    }

    public function cart(Request $r, BuildChecker $checker)
    {
        $parts = $this->selection($r, $checker);
        $checker->requireValid($parts);
        $cart = $r->session()->get('cart', []);
        foreach ($parts as $p) {
            $qty = ($cart[$p->id] ?? 0) + 1;
            if ($qty > $p->stock) {
                throw ValidationException::withMessages(['build' => "$p->name không đủ hàng khi cộng với giỏ hiện tại."]);
            }$cart[$p->id] = $qty;
        }
        $r->session()->put('cart', $cart);

        return redirect()->route('cart')->with('success', 'Đã thêm các linh kiện vào giỏ hàng.');
    }

    public function list(Request $r)
    {
        return view('shop.builds', ['builds' => PcBuild::with('items.product')->where('user_id', $r->user()->id)->latest()->get()]);
    }

    public function destroy(Request $r, PcBuild $build)
    {
        abort_unless($build->user_id === $r->user()->id, 403);
        $build->delete();

        return back()->with('success','Đã xóa cấu hình.');
    }
}
