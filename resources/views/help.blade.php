@extends('layouts.public')
@section('main_class', 'w-full')
@section('title', __('help.title'))
@section('content')
@include('help._center')

{{-- 搜索：按关键词过滤文章卡片，隐藏无命中的分类区块（数据来自 _center 的 JSON 块） --}}
<script>
    (function () {
        var input = document.querySelector('[data-help-search]');
        if (!input) return;
        var dataEl = document.querySelector('[data-help-data]');
        var items = [];
        try { items = JSON.parse(dataEl ? dataEl.textContent : '[]'); } catch (e) { items = []; }
        var emptyTip = document.querySelector('[data-help-empty]');

        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase();
            var total = 0;

            if (q === '') {
                document.querySelectorAll('[data-help-card]').forEach(function (c) { c.style.display = ''; });
                document.querySelectorAll('[data-help-region]').forEach(function (s) { s.style.display = ''; });
                if (emptyTip) emptyTip.classList.add('hidden');
                return;
            }

            var matched = {};
            items.forEach(function (item) {
                if ((item.title + ' ' + item.desc + ' ' + item.category).toLowerCase().indexOf(q) !== -1) {
                    matched[item.url] = true;
                }
            });
            document.querySelectorAll('[data-help-card]').forEach(function (card) {
                var show = !!matched[card.getAttribute('href')];
                card.style.display = show ? '' : 'none';
                if (show) total++;
            });
            document.querySelectorAll('[data-help-region]').forEach(function (s) {
                var anyVisible = Array.prototype.some.call(s.querySelectorAll('[data-help-card]'), function (c) { return c.style.display !== 'none'; });
                s.style.display = anyVisible ? '' : 'none';
            });
            if (emptyTip) emptyTip.classList.toggle('hidden', total > 0);
        });
    })();
</script>
@endsection
