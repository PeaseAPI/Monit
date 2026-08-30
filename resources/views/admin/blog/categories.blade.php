@extends('admin.layout')

@section('title', '博客分类管理 - 管理后台')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>博客分类</h4>
        <a href="{{ route('admin.blog-posts-categories.create') }}" class="btn btn-primary">创建分类</a>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>名称</th>
                        <th>URL</th>
                        <th>文章数</th>
                        <th>排序</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                    <tr>
                        <td>{{ $category->id }}</td>
                        <td>{{ $category->name }}</td>
                        <td>{{ $category->url }}</td>
                        <td>{{ $category->posts_count ?? 0 }}</td>
                        <td>{{ $category->order ?? 0 }}</td>
                        <td>
                            <a href="{{ route('admin.blog-posts-categories.edit', $category->id) }}" class="btn btn-sm btn-outline-primary">编辑</a>
                            <form action="{{ route('admin.blog-posts-categories.destroy', $category->id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定删除？')">删除</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $categories->links() }}
        </div>
    </div>
</div>
@endsection
