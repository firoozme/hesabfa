<div class="p-6">
    <h1 class="text-2xl text-center">لیست محصولات '{{ $price_list->name }}'</h1>
    {{ $this->table }}
    <h3 class="text-left"><a href="{{route('filament.company.resources.price-lists.index')}}">بازگشت</a></h3>
</div>
@push('styles')
<link rel="stylesheet" href="http://127.0.0.1:8000/css/filament/filament/app.css?v=3.2.133.0">
<style>
    @font-face {
    font-family: "Yekan";
    font-style: normal;
    font-weight: 400;
    src: url("../../fonts/Yekan.woff") format("woff");
}

    body{
        direction: rtl;
        font-family: 'Yekan'
    }
</style>
@endpush
