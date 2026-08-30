@extends('admin.layout')

@section('title', '信用票据 - 管理后台')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>信用票据</h4>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>支付ID</th>
                        <th>用户</th>
                        <th>金额</th>
                        <th>货币</th>
                        <th>原因</th>
                        <th>创建时间</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($creditNotes as $creditNote)
                    <tr>
                        <td>{{ $creditNote->id }}</td>
                        <td>{{ $creditNote->payment_id }}</td>
                        <td>{{ $creditNote->user->name ?? '-' }}</td>
                        <td>{{ $creditNote->total_amount }}</td>
                        <td>{{ $creditNote->currency }}</td>
                        <td>{{ $creditNote->reason ?? '-' }}</td>
                        <td>{{ $creditNote->datetime }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $creditNotes->links() }}
        </div>
    </div>
</div>
@endsection
