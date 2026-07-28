<footer class="footer footer-static footer-light">
    <p class="clearfix mb-0">
        <span class="float-md-left d-block d-md-inline-block mt-25">
            @if(isset($setting) && !empty($setting->admin_copyright_text))
                {!! $setting->admin_copyright_text !!}
            @elseif(isset($setting) && !empty($setting->copyright_text))
                {!! $setting->copyright_text !!}
            @else
                COPYRIGHT &copy; {{ date('Y') }} <a class="ml-25" href="#" target="_blank">{{ $setting->admin_title ?? 'Laravel' }}</a>, All rights Reserved
            @endif
        </span>
        <span class="float-md-right d-none d-md-block">
        </span>
    </p>
</footer>
