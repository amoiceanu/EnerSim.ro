@php
    // Configure these values only after the AdSense publisher and slot IDs are available.
    $adClient = config('services.adsense.client'); // data-ad-client
    $adSlot = config('services.adsense.about_right_slot'); // data-ad-slot
@endphp
<aside class="about-ad-slot about-ad-slot-right" aria-label="Publicitate">
    @if(filled($adClient) && filled($adSlot))
        {{-- AdSense responsive settings: data-ad-format="auto" and data-full-width-responsive="true". --}}
        <ins class="adsbygoogle adsbygoogle-slot"
             data-ad-client="{{ $adClient }}"
             data-ad-slot="{{ $adSlot }}"
             data-ad-format="auto"
             data-full-width-responsive="true"></ins>
    @else
        <div class="about-ad-placeholder" aria-hidden="true">
            <small>Publicitate</small>
            <span>▤</span>
            <b>Spațiu publicitar</b>
            <em>160 × 600</em>
        </div>
    @endif
</aside>
