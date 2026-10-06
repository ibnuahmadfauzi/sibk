<form class="row g-3 align-items-end" action="{{ route('data-master.classrooms.store') }}" method="POST">
    @csrf
    <div class="col-12 col-sm">
        <label class="form-label" for="classroom-name">Nama rombel</label>
        <input class="form-control" id="classroom-name" name="name" value="{{ old('name') }}" maxlength="100" placeholder="Contoh: 10 RPL 1" required>
    </div>
    <div class="col-12 col-sm-auto"><button class="btn btn-primary" type="submit">Tambah Rombel</button></div>
</form>
