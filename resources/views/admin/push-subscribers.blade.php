@extends('admin.layout')

@section('title', '推送订阅者 - 管理后台')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>推送通知订阅者</h4>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>网站</th>
                        <th>端点</th>
                        <th>浏览器</th>
                        <th>设备类型</th>
                        <th>国家</th>
                        <th>订阅时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subscribers as $subscriber)
                    <tr>
                        <td>{{ $subscriber->id }}</td>
                        <td>{{ $subscriber->website->name ?? '-' }}</td>
                        <td><small>{{ \Illuminate\Support\Str::limit($subscriber->endpoint, 50) }}</small></td>
                        <td>{{ $subscriber->browser_name ?? '-' }}</td>
                        <td>{{ $subscriber->device_type ?? '-' }}</td>
                        <td>{{ $subscriber->country_code ?? '-' }}</td>
                        <td>{{ $subscriber->datetime }}</td>
                        <td>
                            <form action="{{ route('admin.push-subscribers.destroy', $subscriber->id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定删除？')">删除</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $subscribers->links() }}
        </div>
    </div>
</div>
@endsection
