@extends('backend.app')

@section('title', 'Customer Details')

@section('content')
<div class="app-content content">
    <div class="card">
        
        <div class="card-header">
            <h3 class="card-title">Customer Details</h3>
            <a href="{{ route('admin.customer.index') }}" class="btn btn-primary px-4">Back to List</a>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mt-2">
                    <p><strong>Name:</strong> {{ $user->name }}</p>
                    <p><strong>Email:</strong> {{ $user->email }}</p>
                    <p><strong>Phone:</strong> {{ $user->phone }}</p>
                    <p><strong>User Type:</strong> {{ ucfirst($user->user_type) }}</p>
                    <p><strong>Status:</strong> 
                        <span class="badge {{ $user->status == 'active' ? 'bg-success' : 'bg-warning' }}">
                            {{ ucfirst($user->status) }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
