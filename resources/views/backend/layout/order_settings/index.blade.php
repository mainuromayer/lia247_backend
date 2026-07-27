@extends('backend.app')
@section('title', 'Order Settings')
@section('content')
    <div class="app-content content ">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Order Settings (VAT & Pickup)</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.order_settings.update') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>VAT Percentage (%)</label>
                            <input type="number" step="0.01" name="vat_percentage" class="form-control" value="{{ old('vat_percentage', $setting->vat_percentage ?? 0) }}" required>
                            <small class="form-text text-muted">This will be applied to all applicable orders on checkout.</small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Default Estimated Pickup Time</label>
                            <input type="text" name="pickup_estimated_time" class="form-control" value="{{ old('pickup_estimated_time', $setting->pickup_estimated_time ?? '') }}" placeholder="e.g. 15-20 mins">
                            <small class="form-text text-muted">A generic estimated time to show customers who opt to pick up their orders.</small>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6 form-group">
                            <label>Food Commission (%)</label>
                            <input type="number" step="0.01" name="food_commission" class="form-control" value="{{ old('food_commission', $setting->food_commission ?? 0) }}" required>
                            <small class="form-text text-muted">Commission taken from food orders.</small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Ride Commission (%)</label>
                            <input type="number" step="0.01" name="ride_commission" class="form-control" value="{{ old('ride_commission', $setting->ride_commission ?? 0) }}" required>
                            <small class="form-text text-muted">Commission taken from ride bookings.</small>
                        </div>
                    </div>


                    
                    <button type="submit" class="btn btn-primary mt-3">Save Settings</button>
                </form>
            </div>
        </div>
    </div>
@endsection
