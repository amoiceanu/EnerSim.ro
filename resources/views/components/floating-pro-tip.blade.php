<aside x-show="proTipVisible && activeNav!=='dashboard'" x-transition.opacity.duration.200ms x-cloak class="floating-pro-tip" aria-label="Sfat profesional">
    <button type="button" @click="proTipVisible=false" class="floating-pro-tip-close" aria-label="Închide sfatul">×</button>
    <div class="floating-pro-tip-title"><span>✦</span><b>Sfaturi Pro</b></div>
    <p x-text="currentProTip.text"></p>
    <button type="button" @click="navigate(currentProTip.target)" class="floating-pro-tip-action"><span x-text="currentProTip.action"></span> →</button>
</aside>
