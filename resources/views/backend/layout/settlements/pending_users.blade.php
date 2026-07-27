@extends('backend.app')
@section('title', 'Pending Settlements')
@section('content')
    <div class="app-content content ">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Users with Pending Balances</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Pending Balance</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($wallets as $wallet)
                                <tr>
                                    <td>{{ $wallet->user->name }}</td>
                                    <td>{{ $wallet->user->user_type }}</td>
                                    <td>${{ number_format($wallet->pending_balance, 2) }}</td>
                                    <td>
                                        <form action="{{ route('admin.settlements.create') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $wallet->user_id }}">
                                            <input type="hidden" name="amount" value="{{ $wallet->pending_balance }}">
                                            <button type="submit" class="btn btn-sm btn-success">Create Settlement Request</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            @if($wallets->isEmpty())
                                <tr>
                                    <td colspan="4" class="text-center">No pending balances found.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
