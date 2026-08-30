@extends('admin.layout')

@section('title', '编辑博客文章 - 管理后台')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">编辑博客文章</h4>
            <form action="{{ route('admin.blog-posts.update', $post->id) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label">标题</label>
                    <input type="text" name="title" class="form-control" value="{{ $post->title }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">URL 标识</label>
                    <input type="text" name="url" class="form-control" value="{{ $post->url }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">分类</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- 选择分类 --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $post->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">内容</label>
                    <textarea name="content" class="form-control" rows="15" required>{{ $post->content }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">封面图片</label>
                    <input type="file" name="image" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">描述</label>
                    <textarea name="description" class="form-control" rows="2">{{ $post->description }}</textarea>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="is_published" class="form-check-input" id="is_published" {{ $post->is_published ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_published">发布</label>
                </div>
                <button type="submit" class="btn btn-primary">更新</button>
                <a href="{{ route('admin.blog-posts.index') }}" class="btn btn-secondary">取消</a>
            </form>
        </div>
    </div>
</div>
@endsection
