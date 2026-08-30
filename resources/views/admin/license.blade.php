@extends('admin.layout')

@section('title', '授权许可 - 管理后台')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">授权许可管理</h4>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">当前授权状态</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th style="width:200px">授权密钥</th>
                            <td>{{ $license->license_key ?? '未授权' }}</td>
                        </tr>
                        <tr>
                            <th>授权类型</th>
                            <td>{{ $license->type ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>授权域名</th>
                            <td>{{ $license->domain ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>到期时间</th>
                            <td>{{ $license->expiration_date ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>状态</th>
                            <td>
                                @if($license && $license->is_active)
                                    <span class="badge bg-success">已授权</span>
                                @else
                                    <span class="badge bg-danger">未授权</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">更新授权</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.license.update') }}" method="POST">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">License Key</label>
                            <input type="text" name="license_key" class="form-control" value="{{ $license->license_key ?? '' }}">
                        </div>
                        <button type="submit" class="btn btn-primary">验证并更新</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
