@use('Illuminate\Support\Js')
@extends('layouts.admin')
@section('title', __('admin.blog_posts_categories'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.blog_posts_categories') }}</h1></div>
    <a href="{{ route('admin.blog-posts.index') }}" class="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-700 hover:bg-zinc-50">&larr; {{ __('admin.blog_posts') }}</a>
</div>

<div class="mb-6 rounded-2xl border border-zinc-200 bg-white p-6">
    <h2 class="text-sm font-semibold text-zinc-900">{{ __('admin.category_new') }}</h2>
    <form method="POST" id="category-form" action="{{ route('admin.blog-posts-categories.store') }}" class="mt-4 grid gap-4 sm:grid-cols-4">
        @csrf
        <input type="hidden" name="_method" value="POST" id="form-method">
        <div>
            <label class="mb-1 block text-xs font-medium text-zinc-500">{{ __('admin.title') }}</label>
            <input type="text" name="title" id="f-title" required maxlength="64" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm" placeholder="{{ __('admin.title') }}">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-zinc-500">{{ __('admin.url') }}</label>
            <input type="text" name="url" id="f-url" required maxlength="256" pattern="[a-z0-9-]+" class="w-full rounded-xl border border-zinc-300 px-3 py-2 font-mono text-sm" placeholder="news">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-zinc-500">{{ __('admin.order') }}</label>
            <input type="number" name="order" id="f-order" min="0" max="9999" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm" placeholder="0">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('msg.save') }}</button>
            <button type="button" onclick="resetForm()" class="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-600 hover:bg-zinc-50">{{ __('common.cancel') }}</button>
        </div>
    </form>
</div>

<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr>
        <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.title') }}</th>
        <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.url') }}</th>
        <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.order') }}</th>
        <th class="px-6 py-3"></th>
    </tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($categories as $category)
        <tr>
            <td class="px-6 py-3 font-medium text-zinc-900">{{ $category->title }}</td>
            <td class="px-6 py-3 font-mono text-xs text-zinc-500">{{ $category->url }}</td>
            <td class="px-6 py-3 text-zinc-500">{{ $category->order ?? 0 }}</td>
            <td class="px-6 py-3 text-right whitespace-nowrap">
                <button onclick="editCategory({{ $category->category_id }}, {{ Js::from($category->title) }}, {{ Js::from($category->url) }}, {{ $category->order ?? 0 }})" class="mr-3 text-sm text-zinc-500 hover:text-brand-600">{{ __('common.edit') }}</button>
                <form method="POST" action="{{ route('admin.blog-posts-categories.destroy', $category->category_id) }}" class="inline">@csrf @method('DELETE')<button class="text-sm text-red-500 hover:text-red-700" onclick="return confirm('{{ __('common.confirm_delete') }}')">{{ __('common.delete') }}</button></form>
            </td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="4">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $categories->links() }}

<script>
function editCategory(id, title, url, order) {
    var form = document.getElementById('category-form');
    form.action = '{{ url('admin/blog-posts-categories') }}/' + id;
    document.getElementById('form-method').value = 'PUT';
    document.getElementById('f-title').value = title;
    document.getElementById('f-url').value = url;
    document.getElementById('f-order').value = order;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function resetForm() {
    var form = document.getElementById('category-form');
    form.action = '{{ route('admin.blog-posts-categories.store') }}';
    document.getElementById('form-method').value = 'POST';
    form.reset();
}
</script>
@endsection
