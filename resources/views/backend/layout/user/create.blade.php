@extends('backend.app')
@section('title', 'Create Sub Admin')

@section('content')
    <div class="app-content content ">
        <div class="row">
            <div class="col-lg-7 m-auto">
                <form action="{{ route('admin.sub_admin.store') }}" method="POST">@csrf
                    <div class="card card-body shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Create Sub Admin Account</h4>
                            <a href="{{ route('admin.sub_admin.index') }}" class="btn btn-secondary btn-sm">Back</a>
                        </div>

                        <div class="row mb-3">
                            <label for="role" class="col-3 col-form-label font-weight-bold">Select Role</label>
                            <div class="col-9">
                                <select name="role" class="form-control" id="role" required>
                                    <option value="">-- Select Role --</option>
                                    @foreach ($roles as $r)
                                        <option value="{{ $r->name }}">{{ ucwords(str_replace('_', ' ', $r->name)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="name" class="col-3 col-form-label font-weight-bold">Full Name</label>
                            <div class="col-9">
                                <input type="text" name="name" id="name" class="form-control" placeholder="Enter full name" value="{{ old('name') }}" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="email" class="col-3 col-form-label font-weight-bold">Email Address</label>
                            <div class="col-9">
                                <input type="email" name="email" id="email" class="form-control" placeholder="admin@example.com" value="{{ old('email') }}" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="phone" class="col-3 col-form-label font-weight-bold">Phone Number</label>
                            <div class="col-9">
                                <input type="text" name="phone" id="phone" class="form-control" placeholder="Optional phone number" value="{{ old('phone') }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-3 col-form-label font-weight-bold">Password</label>
                            <div class="col-9">
                                <div class="input-group input-group-merge form-password-toggle">
                                    <input type="password" name="password" id="password" class="form-control" placeholder="Minimum 6 characters" required>
                                    <span class="input-group-text cursor-pointer toggle-password-visibility"><i data-feather="eye"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password_confirmation" class="col-3 col-form-label font-weight-bold">Confirm Password</label>
                            <div class="col-9">
                                <div class="input-group input-group-merge form-password-toggle">
                                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Re-enter password" required>
                                    <span class="input-group-text cursor-pointer toggle-password-visibility"><i data-feather="eye"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12 text-right">
                                <button type="submit" class="btn btn-success px-4">
                                    <i class="fa fa-check me-1"></i> Save Sub Admin
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
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
