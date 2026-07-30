@extends('backend.app')

@section('title', 'Chat with ' . $customer->name)

@push('style')
<style>
    .chat-wrapper { border-radius: 1rem !important; overflow: hidden; border: 1px solid #ebebeb; }
    .max-w-75 { max-width: 75%; }
    .active-chat { border-left: 4px solid #7367f0 !important; }
    .bg-rgba-primary { background-color: rgba(115, 103, 240, 0.1) !important; }
    .bg-rgba-black-10 { background-color: rgba(0, 0, 0, 0.05) !important; }
    .p-0-5 { padding: 0.3rem !important; }
    .px-1-5 { padding-left: 1.25rem !important; padding-right: 1.25rem !important; }
    .p-1-5 { padding: 1.25rem !important; }
    .mt-0-5 { margin-top: 0.25rem !important; }
    .mr-0-5 { margin-right: 0.25rem !important; }
    .font-size-small { font-size: 0.75rem !important; }
    .overflow-auto { overflow-y: auto !important; }
    .truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    #chat-messages::-webkit-scrollbar { width: 5px; }
    #chat-messages::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 10px; }
    
    .bubble-admin { border-radius: 18px; border-bottom-right-radius: 2px !important; }
    .bubble-customer { border-radius: 18px; border-bottom-left-radius: 2px !important; }
    
    .preview-item { position: relative; display: inline-block; margin-right: 12px; margin-bottom: 12px; }
    .preview-item img, .preview-item .doc-preview { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; }
    .preview-item .doc-preview { display: flex; align-items: center; justify-content: center; background: #f8f9fa; color: #6c757d; font-size: 24px; }
    .remove-file { position: absolute; top: -6px; right: -6px; background: #ff4d4f; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 14px; cursor: pointer; border: none; line-height: 1; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
    .remove-file:hover { background: #d9363e; }

    .chat-msg-row { position: relative; }
    .msg-actions { opacity: 1; margin: 0; display: inline-flex; align-items: center; }
    .msg-actions .dropdown-menu { box-shadow: 0 4px 8px rgba(0,0,0,0.1); border: 1px solid #ebebeb; }
    .msg-actions .dropdown-item { font-size: 0.85rem; padding: 6px 12px; border: none; background: transparent; width: 100%; text-align: left; }
</style>
@endpush

@section('content')
<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper">
        <div class="content-body">
            <div class="row">
                <div class="col-12">
                    <div class="card p-3 chat-wrapper shadow border-0 bg-white" style="height: calc(100vh - 200px);">
                        <div class="card-content h-100">
                            <div class="row no-gutters h-100">
                                <!-- Chat Sidebar -->
                                <div class="col-md-4 col-12 chat-sidebar border-right d-none d-md-flex flex-column h-100">
                                    <div class="chat-search p-1 border-bottom bg-light">
                                        <h5 class="mb-0 font-weight-bold">Recent Chats</h5>
                                    </div>
                                    <div class="chat-user-list-wrapper list-group list-group-flush overflow-auto flex-grow-1">
                                        @foreach($customers as $chatUser)
                                        <a href="{{ route('admin.support_chat.show', $chatUser->id) }}" 
                                           class="list-group-item list-group-item-action border-0 px-2 py-1 d-flex align-items-center gap-3 {{ $customer->id == $chatUser->id ? 'bg-rgba-primary active-chat' : '' }}">
                                            <div class="avatar m-0 mr-1">
                                                <img src="{{ $chatUser->avatar ? asset($chatUser->avatar) : asset('uploads/systems/logo/default_avatar.png') }}" 
                                                     height="45" width="45" class="rounded-circle border" alt="avatar">
                                            </div>
                                            <div class="user-chat-info w-100 overflow-hidden">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0 font-weight-bold truncate" style="max-width: 140px;">{{ $chatUser->name }}</h6>
                                                    <small class="text-muted">{{ $chatUser->supportChats->first()?->created_at->format('h:i A') }}</small>
                                                </div>
                                                <small class="text-muted truncate d-block font-size-small">{{ $chatUser->supportChats->first()?->message ?? 'Sent attachment' }}</small>
                                            </div>
                                        </a>
                                        <div class="divider m-0 px-2"><hr class="my-0"></div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Chat Content -->
                                <div class="col-md-8 col-12 chat-app-window d-flex flex-column h-100">
                                    <div class="chat-header border-bottom py-1 px-2 d-flex justify-content-between align-items-center bg-white">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar m-0 mr-1">
                                                <img src="{{ $customer->avatar ? asset($customer->avatar) : asset('uploads/systems/logo/default_avatar.png') }}" 
                                                     height="45" width="45" class="rounded-circle border" alt="avatar">
                                            </div>
                                            <div>
                                                <h6 class="mb-0 font-weight-bold">{{ $customer->name }}</h6>
                                                <small class="text-success"><i class="fa fa-circle font-small-2"></i> Online</small>
                                            </div>
                                        </div>
                                        <div class="chat-header-icons">
                                            <a href="{{ route('admin.support_chat.index') }}" class="btn btn-sm btn-outline-secondary d-md-none"><i class="fa fa-arrow-left"></i> Back</a>
                                        </div>
                                    </div>

                                    <div class="chat-container p-2 flex-grow-1 overflow-auto bg-light" id="chat-messages">
                                        @foreach($messages as $msg)
                                        <div class="d-flex mb-2 {{ $msg->sender_type == 'admin' ? 'justify-content-end' : 'justify-content-start' }} chat-msg-row" id="msg-row-{{ $msg->id }}">
                                            <div class="max-w-75">
                                                <div class="d-flex align-items-center {{ $msg->sender_type == 'admin' ? 'flex-row-reverse' : 'flex-row' }}">
                                                    <div class="p-1 px-1-5 {{ $msg->sender_type == 'admin' ? 'bg-primary text-white shadow-sm bubble-admin' : 'bg-white border text-dark bubble-customer' }} message-content-wrapper" id="msg-bubble-{{ $msg->id }}" style="min-width: 80px;">
                                                        @if($msg->message)
                                                            <p class="mb-0 msg-text" style="line-height: 1.4;">{{ $msg->message }}</p>
                                                        @endif
                                                        @php
                                                            $filesList = is_string($msg->files) ? json_decode($msg->files, true) : $msg->files;
                                                            if (!is_array($filesList) && !empty($msg->files)) {
                                                                $filesList = [$msg->files];
                                                            }
                                                        @endphp
                                                        @if($filesList && count($filesList) > 0)
                                                            <div class="mt-1 d-flex flex-wrap">
                                                                @foreach($filesList as $file)
                                                                <a href="{{ asset($file) }}" target="_blank" 
                                                                   class="{{ $msg->sender_type == 'admin' ? 'text-white' : 'text-primary' }} d-flex align-items-center mr-1 mb-1 bg-rgba-black-10 p-0-5 rounded">
                                                                    <i class="fa fa-file-alt mr-0-5"></i> 
                                                                    <small class="font-weight-bold">View File</small>
                                                                </a>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="msg-actions dropdown mx-1">
                                                        <button class="btn btn-sm btn-link text-muted p-1" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="box-shadow: none;">
                                                            <i class="fa fa-ellipsis-v"></i>
                                                        </button>
                                                        <div class="dropdown-menu {{ $msg->sender_type == 'admin' ? 'dropdown-menu-right' : 'dropdown-menu-left' }}" style="min-width: 100px;">
                                                            @if($msg->sender_type == 'admin')
                                                            <button type="button" class="dropdown-item edit-msg-btn" data-id="{{ $msg->id }}" data-message="{{ $msg->message }}">
                                                                <i class="fa fa-edit text-primary mr-1"></i> Edit
                                                            </button>
                                                            @endif
                                                            <button type="button" class="dropdown-item delete-msg-btn text-danger" data-id="{{ $msg->id }}">
                                                                <i class="fa fa-trash text-danger mr-1"></i> Delete
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="mt-0-5 {{ $msg->sender_type == 'admin' ? 'text-right' : 'text-left' }}">
                                                    <small class="text-muted font-size-small">{{ $msg->created_at->format('h:i A') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>

                                    <div class="chat-footer border-top p-1-5 bg-white">
                                        <form action="{{ route('admin.support_chat.send', $customer->id) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <div id="file-preview-container" style="display: none; padding: 0.5rem;"></div>
                                            <div class="input-group">
                                                <input type="text" name="message" class="form-control border-right-0 bg-light p-3" placeholder="Type your response here..." style="border-radius: 25px 0 0 25px;">
                                                <div class="input-group-append">
                                                    <input type="file" name="files[]" id="file-upload" style="display: none;" multiple>
                                                    <button type="button" class="btn btn-light border-left-0 border-right-0" onclick="document.getElementById('file-upload').click();">
                                                        <i class="fa fa-paperclip text-primary"></i>
                                                    </button>
                                                    <button class="btn btn-primary px-3" type="submit" style="border-radius: 0 25px 25px 0;">
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
@endsection

@push('script')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var chatBox = document.getElementById("chat-messages");
        chatBox.scrollTop = chatBox.scrollHeight;

        const fileInput = document.getElementById('file-upload');
        const previewContainer = document.getElementById('file-preview-container');
        let selectedFiles = new DataTransfer();

        fileInput.addEventListener('change', function(e) {
            Array.from(e.target.files).forEach(file => {
                selectedFiles.items.add(file);
            });
            updateFileInputAndPreview();
        });

        function updateFileInputAndPreview() {
            fileInput.files = selectedFiles.files;
            previewContainer.innerHTML = '';
            
            if (selectedFiles.files.length > 0) {
                previewContainer.style.display = 'block';
                Array.from(selectedFiles.files).forEach((file, index) => {
                    const reader = new FileReader();
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'preview-item';

                    const removeBtn = document.createElement('span');
                    removeBtn.className = 'remove-file';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.onclick = function() {
                        removeFile(index);
                    };

                    if (file.type.startsWith('image/')) {
                        reader.onload = function(e) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.title = file.name;
                            previewDiv.appendChild(img);
                            previewDiv.appendChild(removeBtn);
                        }
                        reader.readAsDataURL(file);
                    } else {
                        const docDiv = document.createElement('div');
                        docDiv.className = 'doc-preview';
                        docDiv.innerHTML = '<i class="fa fa-file-alt"></i>';
                        docDiv.title = file.name;
                        previewDiv.appendChild(docDiv);
                        previewDiv.appendChild(removeBtn);
                    }
                    previewContainer.appendChild(previewDiv);
                });
            } else {
                previewContainer.style.display = 'none';
            }
        }

        function removeFile(index) {
            const dt = new DataTransfer();
            Array.from(selectedFiles.files).forEach((file, i) => {
                if (i !== index) {
                    dt.items.add(file);
                }
            });
            selectedFiles = dt;
            updateFileInputAndPreview();
        }
    });

    $(document).ready(function() {
        // Delete message (No confirmation, direct delete)
        $(document).on('click', '.delete-msg-btn', function() {
            const msgId = $(this).data('id');
            const rowElement = $(`#msg-row-${msgId}`);
            let url = "{{ route('admin.support_chat.delete_message', ':id') }}";
            url = url.replace(':id', msgId);

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        rowElement.fadeOut(400, function() {
                            $(this).remove();
                        });
                        Toast.fire({
                            icon: 'success',
                            title: response.message
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Something went wrong', 'error');
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong';
                    Swal.fire('Error', errorMsg, 'error');
                }
            });
        });

        // Edit message click
        $(document).on('click', '.edit-msg-btn', function() {
            const msgId = $(this).data('id');
            const bubble = $(`#msg-bubble-${msgId}`);
            
            // Avoid opening multiple inline editors for the same message
            if (bubble.find('.edit-msg-container').length > 0) return;

            const originalText = $(this).attr('data-message');
            const textElement = bubble.find('.msg-text');
            textElement.hide();

            // Append edit form
            const editForm = `
                <div class="edit-msg-container mt-1">
                    <textarea class="form-control mb-1 edit-msg-textarea text-dark" rows="2" style="resize: none;">${originalText}</textarea>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-sm btn-outline-light cancel-edit mr-1 p-0-5" style="font-size:0.75rem;">Cancel</button>
                        <button type="button" class="btn btn-sm btn-success save-edit p-0-5" style="font-size:0.75rem;">Save</button>
                    </div>
                </div>
            `;
            bubble.append(editForm);
            
            // Adjust buttons for customer vs admin color schemes
            if (bubble.hasClass('bg-white')) {
                bubble.find('.cancel-edit').removeClass('btn-outline-light').addClass('btn-outline-secondary');
            }
        });

        // Cancel edit
        $(document).on('click', '.cancel-edit', function() {
            const container = $(this).closest('.edit-msg-container');
            const bubble = $(this).closest('.message-content-wrapper');
            container.remove();
            bubble.find('.msg-text').show();
        });

        // Save edit
        $(document).on('click', '.save-edit', function() {
            const container = $(this).closest('.edit-msg-container');
            const bubble = $(this).closest('.message-content-wrapper');
            const textarea = container.find('.edit-msg-textarea');
            const newText = textarea.val().trim();
            const msgId = bubble.attr('id').replace('msg-bubble-', '');
            const editBtn = bubble.closest('.max-w-75').find('.edit-msg-btn');

            if (!newText) {
                Toast.fire({
                    icon: 'warning',
                    title: 'Message cannot be empty.'
                });
                return;
            }

            let url = "{{ route('admin.support_chat.update_message', ':id') }}";
            url = url.replace(':id', msgId);

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    message: newText
                },
                success: function(response) {
                    if (response.success) {
                        container.remove();
                        let textElement = bubble.find('.msg-text');
                        if (textElement.length > 0) {
                            textElement.text(newText).show();
                        } else {
                            bubble.prepend(`<p class="mb-0 msg-text" style="line-height: 1.4;">${newText}</p>`);
                        }
                        // Update the data-message attribute on the Edit button
                        editBtn.attr('data-message', newText);
                        Toast.fire({
                            icon: 'success',
                            title: response.message
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Something went wrong', 'error');
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong';
                    Swal.fire('Error', errorMsg, 'error');
                }
            });
        });
    });
</script>
@endpush
