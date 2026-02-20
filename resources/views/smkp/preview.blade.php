@extends('layouts.app')

@section('content')
<div class="card shadow-sm border-0 mb-3 rounded-3">
    <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="fw-bold m-0 text-primary">
            <i class="bi bi-eye-fill me-2"></i> Preview Dokumen: {{ $file->name }}
        </h5>
        <div>
            <a href="{{ route('smkp.download', $file->id) }}" class="btn btn-dark btn-sm me-1 shadow-sm">
                <i class="bi bi-download"></i> Unduh Asli
            </a>
            <button onclick="window.close()" class="btn btn-secondary btn-sm shadow-sm">
                <i class="bi bi-x-circle"></i> Tutup
            </button>
        </div>
    </div>
</div>

@if(in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']))
    <div class="alert alert-warning border-start border-4 border-warning shadow-sm small py-2 mb-3">
        <i class="bi bi-info-circle-fill me-2"></i> 
        <strong>Catatan:</strong> Preview file Excel/Word menggunakan layanan eksternal. Apabila preview tertulis <em>"No Preview Available"</em> (karena project sedang dijalankan di localhost/offline), silakan gunakan tombol <strong>Unduh Asli</strong>.
    </div>
    
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-light" style="height: 75vh;">
        {{-- Integrasi Google Docs Viewer untuk membaca Word dan Excel --}}
        <iframe src="https://docs.google.com/gview?url={{ urlencode($publicUrl) }}&embedded=true" width="100%" height="100%" frameborder="0"></iframe>
    </div>
@else
    <div class="text-center py-5 mt-4 border rounded bg-light">
        <i class="bi bi-file-earmark-x fs-1 text-muted mb-3 d-block"></i>
        <h5 class="text-muted">Format file ini tidak didukung untuk dibaca langsung.</h5>
        <a href="{{ route('smkp.download', $file->id) }}" class="btn btn-outline-dark mt-2">Silakan Unduh File</a>
    </div>
@endif
@endsection