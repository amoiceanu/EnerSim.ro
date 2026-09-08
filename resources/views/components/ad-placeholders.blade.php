<aside class="ad-placeholders" aria-label="Spații publicitare">
    <div class="ad-placeholder ad-placeholder-top"><span>PUBLICITATE · GOOGLE ADS</span><b>Spațiu rezervat pentru banner</b><small>728 × 90</small></div>
    <div class="ad-placeholder ad-placeholder-left"><span>PUBLICITATE</span><b>Google Ads</b><small>160 × 600</small></div>
    <div class="ad-placeholder ad-placeholder-right"><span>PUBLICITATE</span><b>Google Ads</b><small>160 × 600</small></div>
</aside>

<style>
    .ad-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 1px solid #c7b7f8;
        border-radius: 12px;
        background: linear-gradient(135deg, #f1edff 0%, #faf9ff 48%, #e8f8ff 100%);
        box-shadow: 0 10px 26px rgba(84, 58, 154, .12);
        color: #75669e;
        text-align: center;
    }
    .ad-placeholder-top { width: min(728px, calc(100% - 40px)); min-height: 90px; margin: 14px auto 0; }
    .ad-placeholder-left, .ad-placeholder-right { position: fixed; top: 172px; z-index: 30; width: 140px; min-height: 480px; }
    .ad-placeholder-left { left: 14px; }.ad-placeholder-right { right: 14px; }
    /* Desktop placements stay above the dashboard/sidebar stacking contexts. */
    @media (min-width: 1151px) {
        .ad-placeholder-left, .ad-placeholder-right {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: fixed !important;
            top: 172px !important;
            z-index: 1000;
        }
        .ad-placeholder-left { left: 14px !important; }
        .ad-placeholder-right { right: 14px !important; }
    }
    @media (min-width: 1600px) {
        .ad-placeholder-left, .ad-placeholder-right {
            width: 160px;
            min-height: 600px;
        }
    }
    .ad-placeholder span { color: #5931ce; font-size: 9px; font-weight: 850; letter-spacing: .12em; }
    .ad-placeholder b { margin-top: 4px; color: #273654; font-size: 12px; }
    .ad-placeholder small { margin-top: 3px; color: #8d82af; font-size: 10px; }
    @media (min-width: 1151px) and (max-width: 1500px) {
        .ad-placeholder-left, .ad-placeholder-right { position: fixed; top: 172px; z-index: 60; display: flex; width: 104px; min-height: 420px; }
        .ad-placeholder-left { left: 10px; }.ad-placeholder-right { right: 10px; }
    }
    @media (max-width: 1150px) {
        .ad-placeholders { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; width: min(1080px, calc(100% - 40px)); margin: 14px auto 0; }
        .ad-placeholder-top, .ad-placeholder-left, .ad-placeholder-right { position: static; width: auto; min-height: 90px; margin: 0; }
    }
    @media (max-width: 760px) { .ad-placeholders { grid-template-columns: 1fr; width: calc(100% - 28px); } }
    @media (max-width: 640px) { .ad-placeholder-top { width: calc(100% - 28px); min-height: 68px; margin-top: 10px; } }
    @media print { .ad-placeholders { display: none !important; } }
</style>
