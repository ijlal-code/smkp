@extends('layouts.app')

@section('content')

    <style>
        .list-group-item {
            transition: all 0.2s ease-in-out;
            border-left: 4px solid transparent;
        }
        .list-group-item:hover {
            background-color: #f9fafe; 
            border-left: 4px solid #ffc107; 
            transform: translateX(4px);
            z-index: 10;
        }
        .folder-icon { transition: transform 0.2s; }
        .list-group-item:hover .folder-icon { transform: scale(1.15); }
        
        /* Style Tabs Responsif & Scrollable Horizontal */
        .tabs-container {
            overflow-x: auto;
            white-space: nowrap;
            -ms-overflow-style: none;  
            scrollbar-width: none;  
            border-bottom: 2px solid #dee2e6;
        }
        .tabs-container::-webkit-scrollbar {
            display: none;
        }
        .nav-tabs {
            border-bottom: none;
            flex-wrap: nowrap;
            margin-bottom: 0;
        }
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 3px solid transparent;
        }
        .nav-tabs .nav-link:hover {
            color: #dc3545;
            border-color: transparent;
        }
        .nav-tabs .nav-link.active {
            color: #dc3545; 
            border-bottom: 3px solid #dc3545;
            font-weight: bold;
        }
    </style>

    {{-- BREADCRUMB --}}
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body p-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb m-0 align-items-center">
                    <li class="breadcrumb-item">
                        <a href="{{ route('smkp.index') }}" class="text-decoration-none text-danger fw-bold">
                            <i class="bi bi-house-door-fill"></i> Home
                        </a>
                    </li>
                    @if(isset($breadcrumbs))
                        @foreach($breadcrumbs as $crumb)
                            <li class="breadcrumb-item {{ $loop->last ? 'active text-dark fw-bold' : '' }}">
                                @if(!$loop->last)
                                    @if(isset($crumb->is_tab_root))
                                        <a href="{{ route('smkp.index') }}#tab-{{ $crumb->id }}" class="text-decoration-none text-danger">
                                            {{ $crumb->name }}
                                        </a>
                                    @else
                                        <a href="{{ route('smkp.index', $crumb->id) }}" class="text-decoration-none text-danger">
                                            {{ $crumb->code }} {{ $crumb->name }}
                                        </a>
                                    @endif
                                @else
                                    {{ $crumb->code }} {{ $crumb->name }}
                                @endif
                            </li>
                        @endforeach
                    @endif
                </ol>
            </nav>
        </div>
    </div>

    {{-- ALERT --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 border-start border-5 border-success shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- HEADER ACTION CONTROLS --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            @if($currentFolder)
                @php
                    $backLink = $currentFolder->parent_id && $currentFolder->parent->type !== 'tab' 
                                ? route('smkp.index', $currentFolder->parent_id) 
                                : route('smkp.index') . '#tab-' . ($currentFolder->parent_id ?? '');
                @endphp
                <a href="{{ $backLink }}" class="btn btn-light border shadow-sm px-4">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            @endif
        </div>

        <div class="d-flex gap-2">
            @php
                $canUpload = Auth::user()->role === 'Auditor' || !$currentFolder || stripos($currentFolder->name, 'panduan') === false;
            @endphp
            @if($canUpload)
            <button class="btn btn-danger shadow-sm px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
                <i class="bi bi-cloud-upload me-2"></i> Upload File
            </button>
            @endif

            @if(Auth::user()->role === 'Auditor' && $currentFolder)
            <button class="btn btn-success shadow-sm px-4 fw-bold" onclick="openCreateModal('{{ $currentFolder->id }}', '{{ $currentFolder->name }}')">
                <i class="bi bi-folder-plus me-2"></i> Tambah Sub-Folder
            </button>
            @endif
        </div>
    </div>

    @if(!$currentFolder)
        {{-- ========================================================= --}}
        {{-- TAMPILAN ROOT: TABS DINAMIS (RESPONSIF & SCROLLABLE) --}}
        {{-- ========================================================= --}}
        
        <div class="tabs-container mb-3 d-flex align-items-center">
            <ul class="nav nav-tabs flex-grow-1" id="rootTabs" role="tablist">
                @foreach($tabs as $index => $tab)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $index == 0 ? 'active' : '' }}" id="btn-tab-{{ $tab->id }}" data-bs-toggle="tab" data-bs-target="#pane-tab-{{ $tab->id }}" type="button" role="tab">
                        <i class="bi bi-folder-fill me-2"></i> {{ strtoupper($tab->name) }}
                    </button>
                </li>
                @endforeach
                
                @if(Auth::user()->role === 'Auditor')
                <li class="nav-item ms-2 align-self-center pe-2" role="presentation">
                    <button class="btn btn-sm btn-outline-danger fw-bold rounded-pill px-3 mt-1 mb-1" data-bs-toggle="modal" data-bs-target="#createTabModal">
                        <i class="bi bi-plus-lg"></i> Tambah Tab Baru
                    </button>
                </li>
                @endif
            </ul>
        </div>

        <div class="tab-content" id="rootTabsContent">
            @foreach($tabs as $index => $tab)
            <div class="tab-pane fade {{ $index == 0 ? 'show active' : '' }}" id="pane-tab-{{ $tab->id }}" role="tabpanel">
                
                @if(Auth::user()->role === 'Auditor')
                    <div class="d-flex flex-wrap justify-content-end mb-3 gap-2 align-items-center bg-light p-2 rounded border">
                        <span class="text-muted small fw-bold me-auto"><i class="bi bi-gear-fill"></i> PENGATURAN TAB: {{ $tab->name }}</span>
                        <button class="btn btn-warning btn-sm fw-bold text-dark" onclick="openEditModal('{{ $tab->id }}', '{{ $tab->code }}', '{{ $tab->name }}')">
                            <i class="bi bi-pencil"></i> Edit Tab
                        </button>
                        <button class="btn btn-danger btn-sm fw-bold" onclick="openDeleteModal('{{ route('smkp.delete_folder', $tab->id) }}', '{{ $tab->name }}', 'Tab beserta seluruh isinya')">
                            <i class="bi bi-trash"></i> Hapus Tab
                        </button>
                        <button class="btn btn-success btn-sm fw-bold" onclick="openCreateModal('{{ $tab->id }}', '{{ $tab->name }}')">
                            <i class="bi bi-folder-plus"></i> Tambah Folder Sini
                        </button>
                    </div>
                @endif

                @if(stripos($tab->name, 'panduan') !== false)
                    <div class="alert alert-info border-0 shadow-sm small py-2">
                        <i class="bi bi-info-circle-fill me-2"></i> Area ini berisi dokumen panduan resmi. @if(Auth::user()->role !== 'Auditor') (Akses: Read-Only / Hanya Lihat) @endif
                    </div>
                @endif

                @if($tab->children->count() > 0)
                    <div class="card shadow-sm border-0 rounded-2 mb-4">
                        <div class="list-group list-group-flush">
                            @foreach($tab->children as $folder)
                                @include('smkp.folder_item', ['folder' => $folder])
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="text-center py-4 text-muted small border rounded bg-light mb-4">
                        Belum ada folder di dalam {{ $tab->name }}.
                    </div>
                @endif
            </div>
            @endforeach
            
            @if($tabs->count() == 0)
                <div class="text-center py-5 border rounded bg-light shadow-sm">
                    <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
                    <span class="text-muted">Belum ada Tab Root / Kategori Utama. Minta Auditor untuk menambahkannya.</span>
                </div>
            @endif
        </div>

    @else
        {{-- ========================================================= --}}
        {{-- TAMPILAN SUB FOLDER (STANDAR) --}}
        {{-- ========================================================= --}}
        
        <div class="d-flex align-items-center mb-3">
            <h6 class="text-black fw-bold m-0 border-bottom border-dark border-2 pb-1 pe-3">
                <i class="bi bi-folder-fill text-warning me-2"></i> FOLDER: {{ $currentFolder->name }}
            </h6>
        </div>

        @if($currentFolder->children->count() > 0)
            <div class="card shadow-sm border-0 rounded-2 mb-5">
                <div class="list-group list-group-flush">
                    @foreach($currentFolder->children as $folder)
                        @include('smkp.folder_item', ['folder' => $folder])
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    {{-- FILTER & PENCARIAN FILE --}}
    @php
        // Cek apakah di posisi saat ini tidak ada subfolder lagi
        $noSubfolders = $currentFolder ? $currentFolder->children->count() == 0 : $tabs->count() == 0;
    @endphp

    @if(($noSubfolders && $files->count() > 0) || request()->has('q') || request()->has('unit'))
        <div class="card border-0 shadow-sm bg-light mb-4 mt-4">
            <div class="card-body p-3">
                <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-center">
                    
                    @if(Auth::user()->role === 'Auditor')
                        {{-- KHUSUS AUDITOR: Tampilkan Filter Unit & Form Pencarian --}}
                        <div class="col-md-4">
                            <select name="unit" class="form-select" onchange="this.form.submit()">
                                <option value="">- Filter Semua Role Unit -</option>
                                @php 
                                    $targetRoles = ['KTT', 'Pengelola Sistem', 'Audit Internal', 'Pengelola Risiko', 'Pengelola Legal', 'Pengelola K3 & Lingk.', 'Pengel. SDM & Diklat', 'Pengawas Operasional', 'Bag. K3 & KO Pertamb.', 'PJO', 'Pengawas Oper. PJO', 'Pengawas Teknik PJO', 'Bag. K3 & KO PJO']; 
                                @endphp
                                @foreach($targetRoles as $roleOption)
                                    <option value="{{ $roleOption }}" {{ request('unit') == $roleOption ? 'selected' : '' }}>
                                        {{ $roleOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="q" class="form-control" placeholder="Cari nama dokumen atau uploader..." value="{{ request('q') }}">
                        </div>
                    @else
                        {{-- SELAIN AUDITOR: Tampilkan Form Pencarian Saja (Lebih Lebar) --}}
                        <div class="col-md-10">
                            <input type="text" name="q" class="form-control" placeholder="Cari nama dokumen atau uploader..." value="{{ request('q') }}">
                        </div>
                    @endif

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-search me-1"></i> Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- LIST FILE --}}
    @if($files->count() > 0)
        <div class="d-flex align-items-center mb-3 mt-4">
            <h6 class="text-secondary fw-bold m-0 border-bottom border-secondary border-2 pb-1 pe-3">
                <i class="bi bi-file-earmark-text-fill me-2"></i> DAFTAR DOKUMEN
            </h6>
        </div>

        <div class="card shadow-sm border-0 rounded-2">
            <div class="list-group list-group-flush">
                @foreach($files as $file)
                    <div class="list-group-item list-group-item-action d-flex flex-wrap justify-content-between align-items-center p-3 gap-3">
                        <div class="d-flex align-items-center overflow-hidden flex-grow-1">
                        @php
                            $ext = strtolower(pathinfo($file->file_path, PATHINFO_EXTENSION));
                            $iconClass = match($ext) {
                                'pdf' => 'bi-file-earmark-pdf-fill text-danger',
                                'doc', 'docx' => 'bi-file-earmark-word-fill text-primary',
                                'xls', 'xlsx' => 'bi-file-earmark-excel-fill text-success',
                                'ppt', 'pptx' => 'bi-file-earmark-ppt-fill text-warning',
                                'jpg', 'jpeg', 'png' => 'bi-file-earmark-image-fill text-info',
                                default => 'bi-file-earmark-text-fill text-secondary'
                            };
                        @endphp
                            <i class="bi {{ $iconClass }} fs-2 me-3"></i>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold text-dark text-break">{{ $file->name }}</h6>
                                <div class="small text-muted">{{ strtoupper($ext) }} &bull; {{ $file->user->name ?? '-' }} ({{ $file->user->role ?? '-' }}) &bull; {{ $file->created_at->format('d M Y') }}</div>
                            </div>
                        </div>
                        <div class="d-flex gap-1 gap-sm-2 ms-auto mt-2 mt-sm-0">
    {{-- Tombol Lihat --}}
    <a href="{{ route('smkp.view_file', $file->id) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-2 px-sm-3" title="Lihat Dokumen">
        <i class="bi bi-eye"></i> 
        <span class="d-none d-sm-inline ms-1">Lihat</span>
    </a>

    {{-- Tombol Unduh --}}
    <a href="{{ route('smkp.download', $file->id) }}" class="btn btn-sm btn-outline-dark rounded-pill px-2 px-sm-3" title="Unduh Dokumen">
        <i class="bi bi-download"></i> 
        <span class="d-none d-sm-inline ms-1">Unduh</span>
    </a>
    
    {{-- Tombol Hapus --}}
    @if(Auth::user()->role === 'Auditor' || $file->user_id === Auth::id())
    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 px-sm-3" title="Hapus Dokumen" 
            onclick="openDeleteModal('{{ route('smkp.delete_file', $file->id) }}', '{{ $file->name }}', 'Dokumen')">
        <i class="bi bi-trash"></i> 
        <span class="d-none d-sm-inline ms-1">Hapus</span>
    </button>
    @endif
</div>
                    </div>
                @endforeach
            </div>
        </div>
    @elseif((!$currentFolder && $tabs->count() > 0) || $currentFolder)
        <div class="text-center py-5">
            <i class="bi bi-file-earmark-x fs-1 text-muted mb-3 d-block"></i>
            <span class="text-muted">Tidak ada dokumen di area ini.</span>
        </div>
    @endif

    {{-- MODALS KHUSUS AUDITOR --}}
    @if(Auth::user()->role === 'Auditor')
    
    {{-- MODAL CREATE TAB BARU --}}
    <div class="modal fade" id="createTabModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('smkp.create_tab') }}" method="POST" class="w-100">
                @csrf
                <div class="modal-content rounded-3 border-0 shadow">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold">Buat Tab / Kategori Baru</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">NAMA TAB ROOT</label>
                            <input type="text" name="name" class="form-control" required placeholder="Contoh: SOP BARU, K3, dsb...">
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-link text-secondary text-decoration-none" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger px-4">Buat Tab Baru</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL CREATE FOLDER --}}
    <div class="modal fade" id="createFolderModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form id="createFolderForm" action="" method="POST" class="w-100">
                @csrf
                <div class="modal-content rounded-3 border-0 shadow">
                    <div class="modal-header bg-black text-white">
                        <h5 class="modal-title fw-bold">Buat Folder</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">DI DALAM</label>
                            <input type="text" id="displayParentName" class="form-control-plaintext fw-bold text-uppercase" readonly value="">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">KODE</label>
                            <input type="text" name="code" class="form-control" placeholder="Contoh: I.1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">NAMA FOLDER</label>
                            <input type="text" name="name" class="form-control" required placeholder="Nama Bab / Sub-bab">
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-link text-secondary text-decoration-none" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success px-4">Simpan Folder</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editFolderModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form id="editFolderForm" action="" method="POST" class="w-100">
                @csrf @method('PUT')
                <div class="modal-content rounded-3 border-0 shadow">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold">Edit Folder / Tab</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">KODE (Boleh Kosong)</label>
                            <input type="text" name="code" id="editFolderCode" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">NAMA</label>
                            <input type="text" name="name" id="editFolderName" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-link text-secondary text-decoration-none" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning px-4">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- MODAL UPLOAD --}}
    <div class="modal fade" id="uploadFileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('smkp.upload', $currentFolder ? $currentFolder->id : null) }}" method="POST" enctype="multipart/form-data" class="w-100">
                @csrf
                <div class="modal-content rounded-3 border-0 shadow">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold">Upload Dokumen</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">FILE</label>
                            <input type="file" name="file" id="inputUploadFile" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">NAMA DOKUMEN</label>
                            <input type="text" name="name" id="inputDocumentName" class="form-control" required placeholder="Nama file...">
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-link text-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger px-4">Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL HAPUS --}}
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <form id="deleteForm" action="" method="POST" class="w-100">
                @csrf @method('DELETE')
                <div class="modal-content rounded-3 border-0 shadow">
                    <div class="modal-body p-4 text-center">
                        <div class="mb-3 text-danger"><i class="bi bi-exclamation-circle fs-1"></i></div>
                        <h5 class="fw-bold mb-2">Hapus <span id="deleteType">Item</span>?</h5>
                        <p class="text-muted small mb-4">"<span id="deleteName" class="fw-bold"></span>"<br>Tindakan ini permanen.</p>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light w-50" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger w-50">Ya, Hapus</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Auto isi nama file
            const fileInput = document.getElementById('inputUploadFile');
            const nameInput = document.getElementById('inputDocumentName');
            if (fileInput && nameInput) {
                fileInput.addEventListener('change', function () {
                    if (this.files && this.files.length > 0) {
                        let fName = this.files[0].name;
                        nameInput.value = fName.substring(0, fName.lastIndexOf('.')) || fName;
                    }
                });
            }

            // Mencegah Tab Root kehilangan Active State ketika di refresh lewat Breadcrumb / redirect kembal
            let hash = window.location.hash;
            if (hash) {
                let targetId = hash.replace('#tab-', '#pane-tab-');
                let tabBtn = document.querySelector('button[data-bs-target="' + targetId + '"]');
                if (tabBtn) {
                    let activeTab = new bootstrap.Tab(tabBtn);
                    activeTab.show();
                    // Scroll container tab responsif agar tab yang aktif terlihat di layar mobile
                    tabBtn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                }
            }
        });

        // Function Setter Modal
        function openCreateModal(parentId, parentName) {
            let form = document.getElementById('createFolderForm');
            let baseUrl = "{{ route('smkp.create_folder', 'placeholder_id') }}";
            form.action = baseUrl.replace('placeholder_id', parentId);
            document.getElementById('displayParentName').value = parentName;
            new bootstrap.Modal(document.getElementById('createFolderModal')).show();
        }

        @if(Auth::user()->role === 'Auditor')
        function openEditModal(id, code, name) {
            let form = document.getElementById('editFolderForm');
            let baseUrl = "{{ route('smkp.update_folder', 'placeholder_id') }}";
            form.action = baseUrl.replace('placeholder_id', id);
            document.getElementById('editFolderCode').value = code;
            document.getElementById('editFolderName').value = name;
            new bootstrap.Modal(document.getElementById('editFolderModal')).show();
        }
        @endif

        function openDeleteModal(url, name, type) {
            document.getElementById('deleteForm').action = url;
            document.getElementById('deleteName').innerText = name;
            document.getElementById('deleteType').innerText = type;
            new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
        }
    </script>
@endsection