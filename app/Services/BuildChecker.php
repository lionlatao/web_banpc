<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class BuildChecker
{
    const SLOTS = ['cpu' => 'CPU', 'mainboard' => 'Mainboard', 'ram' => 'RAM', 'gpu' => 'Card đồ họa', 'ssd' => 'SSD', 'psu' => 'Nguồn', 'case' => 'Vỏ máy', 'cooler' => 'Tản nhiệt'];

    public function resolve(array $ids): array
    {
        $selected = [];
        foreach (self::SLOTS as $slot => $label) {
            if (empty($ids[$slot])) {
                continue;
            }
            $p = Product::with('category')->find($ids[$slot]);
            if (! $p || ! $p->active || $p->stock < 1 || $p->category->slug !== $slot) {
                throw ValidationException::withMessages(['build' => "Linh kiện $label không hợp lệ, hết hàng hoặc đã ngừng bán."]);
            }
            $selected[$slot] = $p;
        }

        return $selected;
    }

    public function check(array $p): array
    {
        $errors = [];
        $warnings = [];
        $spec = fn ($slot, $key) => $p[$slot]->specs[$key] ?? null;
        if (isset($p['cpu'],$p['mainboard']) && $spec('cpu', 'socket') !== $spec('mainboard', 'socket')) {
            $errors[] = 'Socket CPU và mainboard không khớp.';
        }
        if (isset($p['ram'],$p['mainboard']) && $spec('ram', 'ram_type') !== $spec('mainboard', 'ram_type')) {
            $errors[] = 'RAM và mainboard khác chuẩn DDR.';
        }
        if (isset($p['case'],$p['mainboard']) && ! in_array($spec('mainboard', 'form_factor'), array_map('trim', explode(',', $spec('case', 'supported_forms') ?? '')))) {
            $errors[] = 'Vỏ máy không hỗ trợ kích thước mainboard.';
        }
        if (isset($p['cooler'],$p['cpu']) && ! in_array($spec('cpu', 'socket'), array_map('trim', explode(',', $spec('cooler', 'supported_sockets') ?? '')))) {
            $errors[] = 'Tản nhiệt không hỗ trợ socket CPU.';
        }
        $watts = (int) $spec('cpu', 'power') + (int) $spec('gpu', 'power') + 80;
        $recommended = (int) (ceil($watts * 1.3 / 50) * 50);
        if (isset($p['psu']) && (int) $spec('psu', 'wattage') < $recommended) {
            $errors[] = "Nguồn thấp hơn mức đề xuất $recommended W.";
        }
        $missing = array_diff(array_keys(self::SLOTS), array_keys($p));
        if ($missing) {
            $warnings[] = 'Chưa chọn: '.implode(', ', array_map(fn ($s) => self::SLOTS[$s], $missing)).'.';
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'recommended' => $recommended, 'total' => array_sum(array_map(fn ($x) => $x->price, $p))];
    }

    public function requireValid(array $p): array
    {
        $r = $this->check($p);
        if (! $p) {
            throw ValidationException::withMessages(['build' => 'Hãy chọn ít nhất một linh kiện.']);
        }
        if ($r['errors']) {
            throw ValidationException::withMessages(['build' => implode(' ',$r['errors'])]);
        }

        return $r;
    }
}
