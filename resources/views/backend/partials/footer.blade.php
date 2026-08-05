<footer class="footer footer-static footer-light">
    <p class="clearfix mb-0">
        <span class="float-md-left d-block d-md-inline-block mt-25">
            @php
                $currentYear = date('Y');
                $userText = $setting->copyright_text ?? 'Quicc Global Solutions. All rights reserved.';
                // Clean up any double © or double years if present in user input
                $userText = trim(str_replace(['©', '&copy;'], '', $userText));
                $userText = trim(preg_replace('/\b20\d{2}\b/', '', $userText));
                $text = '&copy; ' . $currentYear . ' ' . $userText;
            @endphp
            {!! $text !!}
        </span>
        <span class="float-md-right d-none d-md-block">
        </span>
    </p>
</footer>
