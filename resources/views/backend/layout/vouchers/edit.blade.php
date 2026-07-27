@extends('backend.app')
@section('title', 'Edit Voucher')
@section('content')
    <div class="app-content content ">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Edit Voucher</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.vouchers.update', $voucher->id) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Voucher Code</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $voucher->code) }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Voucher Type</label>
                            <select name="type" class="form-control" required>
                                <option value="food" {{ $voucher->type == 'food' ? 'selected' : '' }}>Food</option>
                                <option value="ride" {{ $voucher->type == 'ride' ? 'selected' : '' }}>Ride</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Discount Type</label>
                            <select name="discount_type" class="form-control" required id="discount_type">
                                <option value="fixed" {{ $voucher->discount_type == 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
                                <option value="percentage" {{ $voucher->discount_type == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                <option value="buy_one_get_one" {{ $voucher->discount_type == 'buy_one_get_one' ? 'selected' : '' }}>Buy 1 Get 1</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group" id="discount_amount_group">
                            <label>Discount Amount</label>
                            <input type="number" step="0.01" name="discount_amount" class="form-control" value="{{ old('discount_amount', $voucher->discount_amount) }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Minimum Order Amount</label>
                            <input type="number" step="0.01" name="min_order_amount" class="form-control" value="{{ old('min_order_amount', $voucher->min_order_amount) }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Valid From</label>
                            <input type="datetime-local" name="valid_from" class="form-control" value="{{ old('valid_from', $voucher->valid_from ? \Carbon\Carbon::parse($voucher->valid_from)->format('Y-m-d\TH:i') : '') }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Valid Until</label>
                            <input type="datetime-local" name="valid_until" class="form-control" value="{{ old('valid_until', $voucher->valid_until ? \Carbon\Carbon::parse($voucher->valid_until)->format('Y-m-d\TH:i') : '') }}" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Update</button>
                    <a href="{{ route('admin.vouchers.index') }}" class="btn btn-secondary mt-3">Cancel</a>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('script')
<script>
    $(document).ready(function() {
        $('#discount_type').on('change', function() {
            if ($(this).val() === 'buy_one_get_one') {
                $('#discount_amount_group').hide();
                $('input[name="discount_amount"]').val(0).removeAttr('required');
            } else {
                $('#discount_amount_group').show();
                $('input[name="discount_amount"]').attr('required', true);
            }
        });
        $('#discount_type').trigger('change');
    });
</script>
@endpush
