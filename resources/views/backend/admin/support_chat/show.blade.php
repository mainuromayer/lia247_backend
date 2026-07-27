@extends('backend.app')

@section('title', 'Chat with ' . $customer->name)

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
                            @forelse($customers as $chatUser)
                                <a href="{{ route('admin.support_chat.show', $chatUser->id) }}"
                                    class="list-group-item list-group-item-action border-0 px-2 py-1 d-flex align-items-center {{ $customer->id == $chatUser->id ? 'bg-rgba-primary active-chat' : '' }}">
                                    <div class="avatar m-0 mr-1">
                                        <img src="{{ $chatUser->avatar ? asset($chatUser->avatar) : asset('backend/assets/images/default-avatar.png') }}"
                                            height="45" width="45" class="rounded-circle border" alt="avatar">
                                    </div>
                                    <div class="user-chat-info w-100 overflow-hidden">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0 font-weight-bold truncate" style="max-width: 140px;">
                                                {{ $chatUser->name }}</h6>
                                            <small
                                                class="text-muted">{{ $chatUser->supportChats->last()?->created_at->format('h:i A') }}</small>
                                        </div>
                                        <small
                                            class="text-muted truncate d-block font-size-small">{{ $chatUser->supportChats->last()?->message ?? 'Sent attachment' }}</small>
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
                </div>
            </div>
        </div>
    </div>
<!-- -==================== -->
 


    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper">
        <div class="content-header row">
            <div class="content-header-left col-md-9 col-12 mb-2">
                <div class="row breadcrumbs-top">
                    <div class="col-12">
                        <h2 class="content-header-title float-left mb-0">Help Center</h2>
                        <div class="breadcrumb-wrapper col-12">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('admin.support_chat.index') }}">Support
                                        Chat</a></li>
                                <li class="breadcrumb-item active">{{ $customer->name }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-body">
            <div class="row">
                <div class="col-12">
                    <div class="card chat-wrapper shadow border-0 bg-white" style="height: calc(100vh - 200px);">
                        <div class="card-content h-100">
                            <div class="row no-gutters h-100">
                                <!-- Chat Sidebar -->
                                <div class="col-md-4 col-12 chat-sidebar border-right d-none d-md-flex flex-column h-100">
                                    <div class="chat-search p-1 border-bottom bg-light">
                                        <h5 class="mb-0 font-weight-bold">Recent Chats</h5>
                                    </div>
                                    <div
                                        class="chat-user-list-wrapper list-group list-group-flush overflow-auto flex-grow-1">
                                        @foreach(\App\Models\User::where('user_type', 'customer')->whereHas('supportChats')->get() as $chatUser)
                                            <a href="{{ route('admin.support_chat.show', $chatUser->id) }}"
                                                class="list-group-item list-group-item-action border-0 px-2 py-1 d-flex align-items-center {{ $customer->id == $chatUser->id ? 'bg-rgba-primary active-chat' : '' }}">
                                                <div class="avatar m-0 mr-1">
                                                    <img src="{{ $chatUser->avatar ? asset($chatUser->avatar) : asset('backend/assets/images/default-avatar.png') }}"
                                                        height="45" width="45" class="rounded-circle border" alt="avatar">
                                                </div>
                                                <div class="user-chat-info w-100 overflow-hidden">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="mb-0 font-weight-bold truncate" style="max-width: 140px;">
                                                            {{ $chatUser->name }}</h6>
                                                        <small
                                                            class="text-muted">{{ $chatUser->supportChats->last()?->created_at->format('h:i A') }}</small>
                                                    </div>
                                                    <small
                                                        class="text-muted truncate d-block font-size-small">{{ $chatUser->supportChats->last()?->message ?? 'Sent attachment' }}</small>
                                                </div>
                                            </a>
                                            <div class="divider m-0 px-2">
                                                <hr class="my-0">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Chat Content -->
                                <div class="col-md-8 col-12 chat-app-window d-flex flex-column h-100">
                                    <div
                                        class="chat-header border-bottom py-1 px-2 d-flex justify-content-between align-items-center bg-white">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar m-0 mr-1">
                                                <img src="{{ $customer->avatar ? asset($customer->avatar) : asset('backend/assets/images/default-avatar.png') }}"
                                                    height="45" width="45" class="rounded-circle border" alt="avatar">
                                            </div>
                                            <div>
                                                <h6 class="mb-0 font-weight-bold">{{ $customer->name }}</h6>
                                                <small class="text-success"><i class="fa fa-circle font-small-2"></i>
                                                    Online</small>
                                            </div>
                                        </div>
                                        <div class="chat-header-icons">
                                            <a href="{{ route('admin.support_chat.index') }}"
                                                class="btn btn-sm btn-outline-secondary d-md-none"><i
                                                    class="fa fa-arrow-left"></i> Back</a>
                                        </div>
                                    </div>

                                    <div class="chat-container p-2 flex-grow-1 overflow-auto bg-light" id="chat-messages">
                                        @foreach($messages as $msg)
                                            <div
                                                class="d-flex mb-2 {{ $msg->sender_type == 'admin' ? 'justify-content-end' : 'justify-content-start' }}">
                                                <div class="max-w-75">
                                                    <div class="p-1 px-1-5 {{ $msg->sender_type == 'admin' ? 'bg-primary text-white shadow-sm' : 'bg-white border text-dark' }}"
                                                        style="border-radius: 18px; border-bottom-{{ $msg->sender_type == 'admin' ? 'right' : 'left' }}-radius: 2px;">
                                                        @if($msg->message)
                                                            <p class="mb-0" style="line-height: 1.4;">{{ $msg->message }}</p>
                                                        @endif
                                                        @if($msg->files)
                                                            <div class="mt-1 d-flex flex-wrap">
                                                                @foreach($msg->files as $file)
                                                                    <a href="{{ asset('storage/' . $file) }}" target="_blank"
                                                                        class="{{ $msg->sender_type == 'admin' ? 'text-white' : 'text-primary' }} d-flex align-items-center mr-1 mb-1 bg-rgba-black-10 p-0-5 rounded">
                                                                        <i class="fa fa-file-alt mr-0-5"></i>
                                                                        <small class="font-weight-bold">View File</small>
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div
                                                        class="mt-0-5 {{ $msg->sender_type == 'admin' ? 'text-right' : 'text-left' }}">
                                                        <small
                                                            class="text-muted font-size-small">{{ $msg->created_at->format('h:i A') }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="chat-footer border-top p-1-5 bg-white">
                                        <form action="{{ route('admin.support_chat.send', $customer->id) }}" method="POST"
                                            enctype="multipart/form-data">
                                            @csrf
                                            <div class="input-group input-group-lg shadow-sm rounded">
                                                <input type="text" name="message"
                                                    class="form-control border-right-0 bg-light"
                                                    placeholder="Type your response here..."
                                                    style="border-radius: 25px 0 0 25px;">
                                                <div class="input-group-append">
                                                    <input type="file" name="files[]" id="file-upload"
                                                        style="display: none;" multiple>
                                                    <button type="button" class="btn btn-light border-left-0 border-right-0"
                                                        onclick="document.getElementById('file-upload').click();">
                                                        <i class="fa fa-paperclip text-primary"></i>
                                                    </button>
                                                    <button class="btn btn-primary px-2" type="submit"
                                                        style="border-radius: 0 25px 25px 0;">
                                                        <i class="fa fa-paper-plane"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

<!-- -==================== -->

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var chatBox = document.getElementById("chat-messages");
            chatBox.scrollTop = chatBox.scrollHeight;
        });
    </script>
@endsection