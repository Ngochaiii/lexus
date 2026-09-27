{{--
    Mã đo lường khai ở Cài đặt → Đo lường (GA4, GTM, Facebook Pixel). Bỏ trống thì không in gì —
    không nhúng script rỗng, không gọi domain lạ khi chưa cấu hình.
--}}
@php
    $ga4   = strtoupper(trim((string) catalog_setting('ga4_id')));
    $ga4   = preg_match('/^G-[A-Z0-9]{4,20}$/', $ga4) ? $ga4 : null;
    $gtm   = catalog_setting('gtm_id');
    $pixel = catalog_setting('facebook_pixel');
@endphp

@if ($ga4)
    {{-- gtag() có ngay để insight.js gửi sự kiện (xếp hàng chờ); còn gtag.js
         (~150 KB) tải sau sự kiện load để không tranh băng thông với ảnh chính (LCP). --}}
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $ga4 }}');
        addEventListener('load', function () {
            var s = document.createElement('script');
            s.async = true;
            s.src = 'https://www.googletagmanager.com/gtag/js?id={{ $ga4 }}';
            document.head.appendChild(s);
        });
    </script>
@endif

@if (filled($gtm))
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
        var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
        j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gtm }}');
    </script>
@endif

@if (filled($pixel))
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
        document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init','{{ $pixel }}');fbq('track','PageView');
    </script>
@endif
