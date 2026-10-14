@extends('layouts.app')
@section('title','Danh sách của bạn | LionPC')
@section('content')
<div class="page-heading"><span class="eyebrow">LIONPC / KHÔNG GIAN CỦA BẠN</span><h1>{{ ['favorites'=>'Sản phẩm yêu thích','compare'=>'So sánh sản phẩm','recent'=>'Sản phẩm vừa xem'][$kind] }}</h1><p>{{ $products->count() }} sản phẩm · Lưu theo phiên trình duyệt, không đồng bộ giữa các thiết bị.</p></div>
@if($products->isEmpty())<div class="empty"><h2>{{ $kind==='compare'?'Chọn sản phẩm để đặt cạnh nhau':'Danh sách còn trống' }}</h2><p>Khám phá cửa hàng và chọn những linh kiện phù hợp với bạn.</p><a class="btn primary" href="{{ route('catalog') }}">Khám phá sản phẩm →</a></div>
@elseif($kind==='compare')
<div class="notice">So sánh tối đa 4 sản phẩm. Chọn cùng danh mục để dễ đối chiếu thông số.</div>
<div class="panel table-wrap"><table class="compare-table"><thead><tr><th>Thông tin</th>@foreach($products as $p)<th><img src="{{ asset('images/'.$p->category->slug.'.svg') }}" alt=""><a href="{{ route('product',$p) }}">{{ $p->name }}</a></th>@endforeach</tr></thead><tbody><tr><th>Danh mục</th>@foreach($products as $p)<td>{{ $p->category->name }}</td>@endforeach</tr><tr><th>Giá bán</th>@foreach($products as $p)<td class="price">{{ number_format($p->price,0,',','.') }} ₫</td>@endforeach</tr><tr><th>Tồn kho</th>@foreach($products as $p)<td>{{ $p->stock }} sản phẩm</td>@endforeach</tr>@foreach($products->flatMap(fn($p)=>array_keys($p->specs??[]))->unique() as $key)<tr><th>{{ config('shop.spec_labels.'.$key,$key) }}</th>@foreach($products as $p)<td>{{ $p->specs[$key]??'—' }}</td>@endforeach</tr>@endforeach<tr><th>Thao tác</th>@foreach($products as $p)<td><form method="post" action="{{ route('collection.toggle',['compare',$p]) }}">@csrf<button class="btn ghost">Bỏ so sánh</button></form></td>@endforeach</tr></tbody></table></div>
@else<div class="product-grid">@foreach($products as $p)@include('shop.card')@endforeach</div>@endif
@endsection
