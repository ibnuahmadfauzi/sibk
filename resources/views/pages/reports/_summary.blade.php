<div
    class="sibk-report-summary"
    aria-label="Ringkasan laporan"
>
    @foreach($items as $item)
        <div class="sibk-report-summary__item">
            <strong class="sibk-report-summary__value">{{ $item['value'] }}</strong>
            <span class="sibk-report-summary__label">{{ $item['label'] }}</span>
        </div>
    @endforeach
</div>
