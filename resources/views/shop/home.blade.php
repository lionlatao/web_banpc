@extends('layouts.app')
@section('content')
<section class="hero taste-hero">
<div class="hero-copy"><div class="hero-kicker">LionPC / Dành cho góc máy của bạn</div>
<h1>Góc máy mới.<br><em>Cảm hứng mới.</em></h1>
<p>Chọn linh kiện cho học tập, làm việc và chơi game. Tự build một chiếc PC vừa nhu cầu, vừa ngân sách.</p>
<div class="actions"><a class="btn primary" href="{{ route('builder') }}">Bắt đầu build PC <span aria-hidden="true">↗</span></a><a class="hero-text-link" href="{{ route('catalog') }}">Khám phá sản phẩm →</a></div>
<div class="hero-note"><span class="note-line"></span>8 nhóm linh kiện. Một cấu hình của riêng bạn.</div></div>
<div class="hero-photo"><img src="{{ asset('images/workspace.jpg') }}" alt="Góc bàn máy tính sáng với màn hình, bàn phím và tay cầm chơi game" width="1400" height="788" fetchpriority="high"><div class="photo-caption"><span>Không gian để bắt đầu</span><a href="{{ route('catalog',['category'=>'pc']) }}" aria-label="Khám phá PC lắp sẵn">↗</a></div></div>
</section>
<div class="benefits"><span><b>01</b> Chọn theo nhu cầu</span><span><b>02</b> Đối chiếu thông số</span><span><b>03</b> Kiểm tra tương thích</span><span><b>04</b> Theo dõi đơn hàng</span></div>
<section><div class="section-heading"><div><div class="eyebrow">Chọn từng mảnh ghép</div><h2>Bạn đang tìm gì?</h2></div><a href="{{ route('catalog') }}">Xem tất cả ↗</a></div><div class="categories">@foreach($categories->take(9) as $c)<a href="{{ route('catalog',['category'=>$c->slug]) }}"><img src="{{ asset('images/'.$c->slug.'.svg') }}" alt="" onerror="this.onerror=null;this.src='{{ asset('images/pc.svg') }}'"><strong>{{ $c->name }}</strong></a>@endforeach</div></section>
<section class="discovery-grid"><a href="{{ route('catalog',['category'=>'pc','sort'=>'price_asc']) }}"><span class="eyebrow">01 / PC lắp sẵn</span><h2>Chọn PC lắp sẵn.</h2><p>Khám phá các bộ máy, sắp xếp theo ngân sách.</p><strong>Xem cấu hình →</strong><img src="{{ asset('images/pc.svg') }}" alt=""></a><a href="{{ route('builder') }}"><span class="eyebrow">02 / Build PC</span><h2>Build theo cách bạn.</h2><p>Chọn linh kiện. Kiểm tra. Lưu cấu hình.</p><strong>Mở trình build PC ↗</strong><img src="{{ asset('images/mainboard.svg') }}" alt=""></a></section>
<section><div class="section-heading"><div><div class="eyebrow">Linh kiện & máy tính</div><h2>Khám phá sản phẩm</h2></div><a href="{{ route('catalog') }}">Tất cả sản phẩm ↗</a></div><div class="product-grid">@foreach($products as $p)@include('shop.card')@endforeach</div></section>
<section class="build-banner"><div><div class="eyebrow">Chọn có cơ sở</div><h2>Đặt cạnh nhau. Chọn dễ hơn.</h2><p>So sánh giá, tồn kho và thông số của tối đa 4 sản phẩm.</p></div><a class="btn dark" href="{{ route('collection','compare') }}">Bảng so sánh ↗</a></section>
@endsection
