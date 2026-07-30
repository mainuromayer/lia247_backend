<link rel="apple-touch-icon" href="{{ asset($setting->admin_favicon ??'') }}">
<link rel="shortcut icon" type="image/x-icon" href="{{ asset($setting->admin_favicon ??'') }}">

<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600" rel="stylesheet">

<!-- BEGIN: Vendor CSS-->
<link rel="stylesheet" type="text/css" href="{{ asset('backend/app-assets/vendors/css/vendors.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('backend/app-assets/vendors/css/charts/apexcharts.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('backend/app-assets/vendors/css/extensions/toastr.min.css') }}">
<link rel="stylesheet" type="text/css"
    href="{{ asset('backend/app-assets/vendors/css/forms/select/select2.min.css') }}">
<!-- END: Vendor CSS-->

<!-- BEGIN: Font awesome cdn-->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" />
<!-- END: Font awesome cdn-->
<!-- Remix Icons -->
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<!-- MDI Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/light-font@0.2.63/css/materialdesignicons-light.min.css" />
<!-- MDI Icons End -->

<!-- BEGIN: Theme CSS-->
<link rel="stylesheet" type="text/css" href="{{ asset('backend/app-assets/css/bootstrap.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('backend/app-assets/css/bootstrap-extended.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('backend/app-assets/css/colors.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('backend/app-assets/css/components.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('backend/app-assets/css/themes/dark-layout.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('backend/app-assets/css/themes/bordered-layout.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('backend/app-assets/css/themes/semi-dark-layout.css') }}">

<!-- BEGIN: Page CSS-->
<link rel="stylesheet" type="text/css"
    href="{{ asset('backend/app-assets/css/core/menu/menu-types/vertical-menu.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('backend/app-assets/css/pages/dashboard-ecommerce.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('backend/app-assets/css/plugins/charts/chart-apex.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf/notyf.min.css">
<!-- END: Page CSS-->

<!-- BEGIN: Custom CSS-->
<link rel="stylesheet" type="text/css" href="{{ asset('backend/assets/css/style.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('backend/assets/css/custom.css') }}">
<!-- END: Custom CSS-->

<!-- plugins css-->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" />
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.0/dist/sweetalert2.min.css" rel="stylesheet">
<link href="{{ asset('vendor/flasher/flasher.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('backend/assets/datatable/css/datatables.min.css') }}">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.min.css" />
{{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" /> --}}
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>

<link href='https://cdn.jsdelivr.net/npm/sweetalert2@10.10.1/dist/sweetalert2.min.css'>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.all.min.js"></script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
<style>
    .main-menu a {
        text-decoration: none !important;
    }

    .main-menu a:hover {
        text-decoration: none !important;
    }

    .main-menu .nav-item a.active {
        text-decoration: none !important;
    }

    .dropify-wrapper .dropify-message p {
        font-size: 23px;
    }

    .card-body {
        padding: 24px 22px;
    }

    .card .card-title {
        font-weight: 600;
        font-size: 23px;
    }

    .dt-length {
        margin-bottom: 15px;
    }

    .dt-length label {
        text-transform: capitalize;
    }

    input#select_all {
        padding: 6px;
        margin-left: 1px;
    }

    .select_data {
        margin-left: 1px;
    }

    .select2-container .select2-selection--single .select2-selection__rendered {
        font-size: 13px !important;
    }
</style>
<style>
    .dashboard-date {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
        font-weight: 600;
        color: #333;
        background: #f8f9fa;
        padding: 8px 15px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .dashboard-date i {
        color: #007bff;
        font-size: 18px;
        padding-top: 2px;
    }

    #timer {
        font-weight: bold;
        color: #000000;
    }

    /* Dark Mode Overrides for Dashboard Date & Clock */
    html.dark-layout .dashboard-date {
        color: #ffffff !important;
        background: #283046 !important;
        box-shadow: 0 4px 24px 0 rgba(0, 0, 0, 0.25) !important;
    }
    html.dark-layout .dashboard-date i {
        color: #ffffff !important;
    }
    html.dark-layout #timer {
        color: #ffffff !important;
    }

    /* Tailwind Conflict Resolvers for Bootstrap Checkboxes & Toggles */
    div.form-checkbox, div.form-check {
        border: none !important;
        background: transparent !important;
        width: auto !important;
        height: auto !important;
        box-shadow: none !important;
        appearance: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* Restore Bootstrap's left padding for form controls so labels do not overlap */
    div.form-check {
        padding-left: 2.75em !important;
    }

    /* Keep checkboxes in datatables centered without padding-left offsets */
    table div.form-check, table div.form-checkbox {
        padding-left: 0 !important;
    }

    .form-switch .form-check-input {
        width: 2.5em !important;
        height: 1.25em !important;
        background-size: contain !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba(0, 0, 0, 0.25)'/%3e%3c/svg%3e") !important;
        background-position: left center !important;
        border-radius: 2em !important;
        transition: background-position 0.15s ease-in-out !important;
    }
    .form-switch .form-check-input:checked {
        background-position: right center !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e") !important;
    }
</style>


@stack('style')
