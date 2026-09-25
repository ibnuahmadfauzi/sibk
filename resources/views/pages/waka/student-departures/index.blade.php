@extends('layouts.app-2')

@section('page-title', 'Proses Keluar Murid - Ruang BK')

@section('body')
<div class="sibk-dashboard" data-page-id="WAKA-DEPARTURES">
    <div class="sibk-page-header mb-4">
        <div class="sibk-page-header__copy">
            <h1>Proses Keluar Murid</h1>
            <p>Pantau proses keluar murid tanpa mengubah datanya.</p>
        </div>
    </div>

    <section class="sibk-panel">
        @if($departures->isEmpty())
            <div class="p-5 text-center text-muted">Belum ada proses keluar murid.</div>
        @else
            <div class="table-responsive">
                <table class="table sibk-table align-middle mb-0">
                    <thead><tr><th>Murid</th><th>Kelas</th><th>Jenis</th><th>Status</th><th>Dilaporkan</th><th>Efektif</th><th>Rekomendasi</th><th>Keputusan</th><th>Pencatat</th><th>Pemutus</th></tr></thead>
                    <tbody>
                        @foreach($departures as $departure)
                            @php $membership = $departure->student->classMemberships->first(); @endphp
                            <tr>
                                <td class="fw-semibold">{{ $departure->student->name }}</td>
                                <td>{{ $membership?->classroom?->name ?? '-' }}</td>
                                <td>{{ $departure->typeLabel() }}</td>
                                <td>{{ $departure->statusLabel() }}</td>
                                <td>{{ $departure->reported_at->locale('id')->translatedFormat('d M Y') }}</td>
                                <td>{{ $departure->effective_date?->locale('id')->translatedFormat('d M Y') ?? '-' }}</td>
                                <td>{{ $departure->recommendation_summary ?: '-' }}</td>
                                <td>{{ $departure->decision_note ?: '-' }}</td>
                                <td>{{ $departure->recorder?->name ?? '-' }}</td>
                                <td>{{ $departure->finalizer?->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($departures->hasPages())<div class="p-4">{{ $departures->links() }}</div>@endif
        @endif
    </section>
</div>
@endsection
