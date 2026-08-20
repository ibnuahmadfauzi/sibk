@extends('layouts.app-2')
@section('page-title', 'Koreksi Data - Ruang BK')
@section('body')
<div class="sibk-dashboard">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4"><div class="sibk-page-header__copy"><h1>Koreksi Data</h1><p>Daftar pengajuan koreksi data operasional dan master sesuai kewenangan.</p></div>@if($canCreateCorrection)<a href="{{ route('corrections.create') }}" class="btn btn-primary">+ Ajukan Koreksi</a>@endif</div>
    <div class="sibk-panel mb-4"><div class="sibk-panel__body p-4"><h2 class="sibk-filter-title mb-3">Filter Pengajuan</h2><form class="row g-3 align-items-end" action="{{ route('corrections.index') }}" method="GET">
        <div class="col-12 col-md-4"><label for="search" class="form-label">Cari</label><input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Nomor, objek, atribut, atau pengaju"></div>
        <div class="col-12 col-md-3"><label for="correction_type" class="form-label">Jenis Data</label><select class="form-select" id="correction_type" name="correction_type"><option value="">Semua jenis</option><option value="operational" @selected(request('correction_type') === 'operational')>Operasional</option><option value="master" @selected(request('correction_type') === 'master')>Master</option></select></div>
        <div class="col-12 col-md-3"><label for="status_id" class="form-label">Status</label><select class="form-select" id="status_id" name="status_id"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected((string) request('status_id') === (string) $status->id)>{{ $status->label }}</option>@endforeach</select></div>
        <div class="col-12 col-md-2"><button type="submit" class="btn btn-outline-primary w-100">Filter</button></div>
    </form></div></div>
    <div class="sibk-panel"><div class="table-responsive"><table class="table sibk-table mb-0"><thead><tr><th>No. Koreksi</th><th>Objek</th><th>Atribut</th><th>Pengaju</th><th>Jenis</th><th>Status</th><th>Tanggal</th><th></th></tr></thead><tbody>
    @forelse($corrections as $item)<tr><td class="fw-bold text-primary">{{ $item->registration_number }}</td><td class="fw-semibold">{{ $item->target_label }}</td><td>{{ $item->field_label }}</td><td>{{ $item->requester->name }}</td><td><span class="badge bg-light text-dark border">{{ $item->correction_type === 'master' ? 'Master' : 'Operasional' }}</span></td><td><span class="sibk-badge sibk-badge--{{ in_array($item->status->code, ['disetujui', 'selesai'], true) ? 'success' : ($item->status->code === 'ditolak' ? 'danger' : 'warning') }}">{{ $item->status->label }}</span></td><td class="text-muted">{{ $item->created_at->locale('id')->translatedFormat('d M Y') }}</td><td><a href="{{ route('corrections.show', $item) }}" class="fw-bold text-decoration-none">Buka</a></td></tr>
    @empty<tr><td colspan="8" class="text-center py-4 text-muted">Belum ada pengajuan koreksi data.</td></tr>@endforelse
    </tbody></table></div>@if($corrections->hasPages())<div class="p-4 border-top">{{ $corrections->links() }}</div>@endif</div>
</div>
@endsection
