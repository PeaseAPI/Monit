@extends('admin.layout')

@section('title', '编辑页面分类 - 管理后台')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">编辑页面分类</h4>
            <form action="{{ route('admin.pages-categories.update', $category->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label">分类名称</label>
                    <input type="text" name="name" class="form-control" value="{{ $category->name }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">URL 标识</label>
                    <input type="text" name="url" class="form-control" value="{{ $category->url }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">描述</label>
                    <textarea name="description" class="form-control" rows="3">{{ $category->description }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">排序</label>
                    <input type="number" name="order" class="form-control" value="{{ $category->order ?? 0 }}">
                </div>
                <button type="submit" class="btn btn-primary">更新</button>
                <a href="{{ route('admin.pages-categories.index') }}" class="btn btn-secondary">取消</a>
            </form>
        </div>
    </div>
</div>
@endsection
