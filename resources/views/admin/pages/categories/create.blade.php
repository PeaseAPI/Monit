@extends('admin.layout')

@section('title', '页面分类创建 - 管理后台')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">创建页面分类</h4>
            <form action="{{ route('admin.pages-categories.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">分类名称</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">URL 标识</label>
                    <input type="text" name="url" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">描述</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">排序</label>
                    <input type="number" name="order" class="form-control" value="0">
                </div>
                <button type="submit" class="btn btn-primary">保存</button>
                <a href="{{ route('admin.pages-categories.index') }}" class="btn btn-secondary">取消</a>
            </form>
        </div>
    </div>
</div>
@endsection
