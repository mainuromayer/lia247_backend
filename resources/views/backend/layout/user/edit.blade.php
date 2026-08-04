@extends('backend.app')
@section('title', 'Edit Sub Admin')

@section('content')
    <div class="app-content content ">
        <form action="{{ route('admin.sub_admin.update', $user->id) }}" method="POST" enctype="multipart/form-data">@csrf
            <input type="hidden" name="id" value="{{ $user->id }}">
            <div class="row">
                <div class="col-lg-6 m-auto">
                    <div class="card card-body shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Edit Sub Admin Info</h4>
                            <a href="{{ route('admin.sub_admin.index') }}" class="btn btn-secondary btn-sm">Back</a>
                        </div>
                        <div class="row mb-3">
                            <label for="role" class="col-3 col-form-label font-weight-bold">Select Role</label>
                            <div class="col-9">
                                <select name="role" class="form-control" id="role" required>
                                    <option value="">-- Select Role --</option>
                                    @foreach ($roles as $r)
                                        <option value="{{ $r->name }}" {{ (isset($userRole) && $userRole->name == $r->name) ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $r->name)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="name" class="col-3 col-form-label font-weight-bold">Full Name</label>
                            <div class="col-9">
                                <input type="text" name="name" id="name" class="form-control" placeholder="Name..."
                                    value="{{ old('name', $user->name) }}" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="email" class="col-3 col-form-label font-weight-bold">Email Address</label>
                            <div class="col-9">
                                <input type="email" name="email" id="email" class="form-control" placeholder="Email..."
                                    value="{{ old('email', $user->email) }}" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="phone" class="col-3 col-form-label font-weight-bold">Phone Number</label>
                            <div class="col-9">
                                <input type="text" name="phone" id="phone" class="form-control" placeholder="Optional phone number"
                                    value="{{ old('phone', $user->phone) }}">
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="" class="col-3 col-form-label"><i>New Password</i></label>
                            <div class="col-9">
                                <div class="input-group input-group-merge form-password-toggle">
                                    <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                                    <span class="input-group-text cursor-pointer toggle-password-visibility"><i data-feather="eye"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="" class="col-3 col-form-label"><i>Confirm Password</i></label>
                            <div class="col-9">
                                <div class="input-group input-group-merge form-password-toggle">
                                    <input type="password" name="password_confirmation" class="form-control" placeholder="Re-enter new password">
                                    <span class="input-group-text cursor-pointer toggle-password-visibility"><i data-feather="eye"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="text-end">
                                    <button type="submit" class="btn btn-success mt-2">
                                        <i class="ri-save-line"></i> Update
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            $(document).on('click', '.toggle-password-visibility', function() {
                const $input = $(this).closest('.input-group').find('input');
                const $icon = $(this).find('i');
                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    if (typeof feather !== 'undefined') {
                        $icon.replaceWith(feather.icons['eye-off'].toSvg());
                    }
                } else {
                    $input.attr('type', 'password');
                    if (typeof feather !== 'undefined') {
                        $icon.replaceWith(feather.icons['eye'].toSvg());
                    }
                }
            });
        });
    </script>
@endpush
