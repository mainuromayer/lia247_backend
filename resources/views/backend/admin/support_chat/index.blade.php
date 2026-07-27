@extends('backend.app')

@section('title', 'Support Chat')

@section('content')
    <div class="app-content content">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Support Chat</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="chat-user-list-wrapper list-group list-group-flush overflow-auto flex-grow-1">
                            @forelse($customers as $customer)
                                <a href="{{ route('admin.support_chat.show', $customer->id) }}"
                                    class="list-group-item list-group-item-action border-0 px-2 py-1 d-flex align-items-center">
                                    <div class="avatar m-0 mr-1 position-relative">
                                        <img src="{{ $customer->avatar ? asset($customer->avatar) : asset('backend/assets/images/default-avatar.png') }}"
                                            height="45" width="45" class="rounded-circle border" alt="avatar">
                                        @if($customer->unread_count > 0)
                                            <span class="badge badge-up badge-danger badge-pill badge-sm"
                                                style="right: -2px; top: -2px;">{{ $customer->unread_count }}</span>
                                        @endif
                                    </div>
                                    <div class="user-chat-info w-100 overflow-hidden">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0 font-weight-bold truncate" style="max-width: 140px;">
                                                {{ $customer->name }}</h6>
                                            <small
                                                class="text-muted">{{ $customer->supportChats->first()?->created_at->format('h:i A') }}</small>
                                        </div>
                                        <small
                                            class="text-muted truncate d-block font-size-small">{{ $customer->supportChats->first()?->message ?? 'Sent attachment' }}</small>
                                    </div>
                                </a>
                                <div class="divider m-0 px-2">
                                    <hr class="my-0">
                                </div>
                            @empty
                                <div class="p-3 text-center text-muted">
                                    <i class="fa fa-frown-o mb-1 d-block font-large-1"></i>
                                    No active conversations.
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card chat-wrapper shadow border-0 bg-white" style="height: calc(100vh - 200px);">
                            <div class="card-content h-100">
                                <div class="row no-gutters h-100">
                                    <!-- Sidebar -->
                                    <div class="col-md-4 col-12 chat-sidebar border-right d-flex flex-column h-100">
                                        <div class="chat-search p-1 border-bottom bg-light">
                                            <h5 class="mb-0 font-weight-bold">Recent Chats</h5>
                                        </div>
                                        <div
                                            class="chat-user-list-wrapper list-group list-group-flush overflow-auto flex-grow-1">
                                            @forelse($customers as $customer)
                                                <a href="{{ route('admin.support_chat.show', $customer->id) }}"
                                                    class="list-group-item list-group-item-action border-0 px-2 py-1 d-flex align-items-center">
                                                    <div class="avatar m-0 mr-1 position-relative">
                                                        <img src="{{ $customer->avatar ? asset($customer->avatar) : asset('backend/assets/images/default-avatar.png') }}"
                                                            height="45" width="45" class="rounded-circle border" alt="avatar">
                                                        @if($customer->unread_count > 0)
                                                            <span class="badge badge-up badge-danger badge-pill badge-sm"
                                                                style="right: -2px; top: -2px;">{{ $customer->unread_count }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="user-chat-info w-100 overflow-hidden">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <h6 class="mb-0 font-weight-bold truncate"
                                                                style="max-width: 140px;">{{ $customer->name }}</h6>
                                                            <small
                                                                class="text-muted">{{ $customer->supportChats->first()?->created_at->format('h:i A') }}</small>
                                                        </div>
                                                        <small
                                                            class="text-muted truncate d-block font-size-small">{{ $customer->supportChats->first()?->message ?? 'Sent attachment' }}</small>
                                                    </div>
                                                </a>
                                                <div class="divider m-0 px-2">
                                                    <hr class="my-0">
                                                </div>
                                            @empty
                                                <div class="p-3 text-center text-muted">
                                                    <i class="fa fa-frown-o mb-1 d-block font-large-1"></i>
                                                    No active conversations.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                    <!-- Content Placeholder -->
                                    <div
                                        class="col-md-8 d-none d-md-flex align-items-center justify-content-center bg-light rounded-right h-100">
                                        <div class="text-center p-3">
                                            <div class="mb-2 bg-rgba-primary p-2 d-inline-block rounded-circle">
                                                <i class="fa fa-comments text-primary" style="font-size: 4rem;"></i>
                                            </div>
                                            <h4 class="font-weight-bold">Help Center Messaging</h4>
                                            <p class="text-muted px-2">Select a customer from the left sidebar to start
                                                responding to their support requests.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection