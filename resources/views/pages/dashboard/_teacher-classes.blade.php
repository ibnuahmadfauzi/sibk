<div class="sibk-teacher-classes p-3">
    <div class="nav nav-pills flex-nowrap gap-2 mb-3" role="tablist" aria-label="Tingkat kelas binaan">
        @foreach($dashboard['context_panel']['groups'] as $grade => $group)
            <button class="nav-link flex-fill {{ $grade === 10 ? 'active' : '' }}" id="teacher-grade-{{ $grade }}-tab" data-bs-toggle="tab" data-bs-target="#teacher-grade-{{ $grade }}" type="button" role="tab" aria-controls="teacher-grade-{{ $grade }}" aria-selected="{{ $grade === 10 ? 'true' : 'false' }}">Kelas {{ $grade }}</button>
        @endforeach
    </div>
    <div class="tab-content sibk-teacher-classes__content">
        @foreach($dashboard['context_panel']['groups'] as $grade => $group)
            <div class="tab-pane {{ $grade === 10 ? 'active show' : '' }}" id="teacher-grade-{{ $grade }}" role="tabpanel" aria-labelledby="teacher-grade-{{ $grade }}-tab" tabindex="0">
                <!-- <h3 class="h6 mb-1">Kelas {{ $grade }}</h3> -->
                <p class="small text-muted mb-3">{{ $group['class_count'] }} kelas &bull; {{ $group['student_count'] }} murid</p>
                <div class="sibk-teacher-classes__list">
                    @forelse(array_slice($group['items'], 0, 6) as $item)
                        <a href="{{ $item['url'] }}" class="sibk-teacher-classes__row text-decoration-none"><span>{{ $item['label'] }}</span><span class="text-nowrap">{{ $item['value'] }}</span></a>
                    @empty
                        <p class="text-muted small py-3">Belum ada kelas binaan pada tingkat ini.</p>
                    @endforelse
                    @if(count($group['items']) > 6)
                        <details>
                            <summary class="text-primary small py-3 text-end">Lihat Semua</summary>
                            @foreach(array_slice($group['items'], 6) as $item)
                                <a href="{{ $item['url'] }}" class="sibk-teacher-classes__row text-decoration-none"><span>{{ $item['label'] }}</span><span class="text-nowrap">{{ $item['value'] }}</span></a>
                            @endforeach
                        </details>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
