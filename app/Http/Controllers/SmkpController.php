<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class SmkpController extends Controller
{
    public function index(Request $request, $folderId = null)
    {
        $user = Auth::user();
        $units = []; 
        $tabs = collect(); // <-- TAMBAHKAN BARIS INI

        // --- AUTO-MIGRATE SCRIPT: Otomatis memindahkan folder lama ke dalam struktur Tab dinamis ---

        // --- AUTO-MIGRATE SCRIPT: Otomatis memindahkan folder lama ke dalam struktur Tab dinamis ---
        if (Folder::whereNull('parent_id')->whereIn('type', ['main', 'panduan'])->exists()) {
            $tabUtama = Folder::firstOrCreate(['name' => 'FOLDER UTAMA', 'type' => 'tab']);
            Folder::whereNull('parent_id')->where('type', 'main')->update(['parent_id' => $tabUtama->id, 'type' => 'folder']);
            
            $tabPanduan = Folder::firstOrCreate(['name' => 'PANDUAN & PROSEDUR', 'type' => 'tab']);
            Folder::whereNull('parent_id')->where('type', 'panduan')->update(['parent_id' => $tabPanduan->id, 'type' => 'folder']);
        }

        if (!$folderId) {
            // === POSISI ROOT (HALAMAN UTAMA) ===
            // Ambil semua Folder level teratas sebagai TABS
            $tabs = Folder::whereNull('parent_id')->where('type', 'tab')->orderBy('id')->get();
            $tabs->load('children'); 
            
            $currentFolder = null;
            $breadcrumbs = [];
            $fileQuery = FileUpload::whereNull('folder_id');

        } else {
            // === POSISI DI DALAM FOLDER (SUB-FOLDER) ===
            $currentFolder = Folder::with('children')->findOrFail($folderId);
            $folders = $currentFolder->children;
            
            $breadcrumbs = [];
            $temp = $currentFolder;
            while($temp) {
                if ($temp->type === 'tab') {
                    $temp->is_tab_root = true; // Tandai agar link breadcrumb mengarah ke Hash Anchor Tab
                }
                array_unshift($breadcrumbs, $temp);
                $temp = $temp->parent;
            }

            $fileQuery = $currentFolder->files()->getQuery();     
        }

        // --- 2. LOGIKA FILTER & PENCARIAN ---
        if ($request->has('unit') && $request->unit != '') {
            $fileQuery->whereHas('user', function($q) use ($request) {
                $q->where('role', $request->unit);
            });
        }

        if ($request->has('q') && $request->q != '') {
            $search = $request->q;
            $fileQuery->where(function($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhereHas('user', function($u) use ($search) {
                          $u->where('name', 'like', '%' . $search . '%') 
                            ->orWhere('role', 'like', '%' . $search . '%'); 
                      });
            });
        }

        // --- 3. LOGIKA AKSES ROLE (VISIBILITY) ---
        if ($user->role === 'Auditor') {
            $files = $fileQuery->with('user')->latest()->get();
        } else {
            $isPanduanArea = false;
            if ($currentFolder) {
                $rootTab = $currentFolder;
                while($rootTab->parent_id != null) {
                    $rootTab = $rootTab->parent;
                }
                if (stripos($rootTab->name, 'panduan') !== false) {
                    $isPanduanArea = true;
                }
            }

            if ($isPanduanArea) {
                $files = $fileQuery->with('user')->latest()->get();
            } else {
                $fileQuery->where(function($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhereHas('user', function($u) {
                          $u->where('role', 'Auditor');
                      });
                });
                $files = $fileQuery->with('user')->latest()->get();
            }
        }

        return view('smkp.index', compact('tabs', 'files', 'currentFolder', 'breadcrumbs', 'units'));
    }

    public function upload(Request $request, $folderId = null)
        {
            $request->validate([
                'file' => 'required|file|mimes:pdf,doc,docx|max:51200', 
                'name' => 'required|string|max:255', 
            ]);

        if ($folderId) {
            $folder = Folder::findOrFail($folderId);
            $rootTab = $folder;
            while($rootTab->parent_id != null) {
                $rootTab = $rootTab->parent;
            }

            if (stripos($rootTab->name, 'panduan') !== false && Auth::user()->role !== 'Auditor') {
                abort(403, 'Hanya Auditor yang dapat mengunggah dokumen di folder Panduan.');
            }
        }

        $file = $request->file('file');
        
        // 1. UBAH DI SINI: Gunakan parameter kedua 'public' agar masuk ke disk public
        $path = $file->store('smkp_files', 'public');

        FileUpload::create([
            'folder_id' => $folderId,
            'user_id'   => Auth::id(),
            'name'      => $request->name,
            
            // 2. UBAH DI SINI: Tidak perlu str_replace lagi karena hasilnya otomatis 'smkp_files/namafile.ext'
            'file_path' => $path, 
            
            'mime_type' => $file->getClientMimeType(),
        ]);

        return back()->with('success', 'File berhasil disimpan.');
    }

    public function createTab(Request $request)
    {
        if (Auth::user()->role !== 'Auditor') abort(403, 'Hanya Auditor yang dapat membuat Tab.');

        $request->validate(['name' => 'required|string|max:255']);

        Folder::create([
            'name' => strtoupper($request->name),
            'type' => 'tab'
        ]);

        return back()->with('success', 'Tab Root berhasil ditambahkan.');
    }

    public function createFolder(Request $request, $parentId = null)
    {
        if (Auth::user()->role !== 'Auditor') abort(403, 'Hanya Auditor yang dapat membuat folder.');

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
        ]);

        Folder::create([
            'name' => $request->name,
            'code' => $request->code,
            'parent_id' => $parentId,
            'type' => 'folder'
        ]);

        return back()->with('success', 'Folder berhasil dibuat.');
    }

    public function updateFolder(Request $request, $id)
    {
        if (Auth::user()->role !== 'Auditor') abort(403, 'Hanya Auditor yang dapat mengubah folder.');

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
        ]);

        $folder = Folder::findOrFail($id);
        $folder->update(['name' => $request->name, 'code' => $request->code]);

        return back()->with('success', 'Update berhasil.');
    }

    public function deleteFolder($id)
    {
        if (Auth::user()->role !== 'Auditor') abort(403, 'Hanya Auditor yang dapat menghapus folder.');

        $folder = Folder::findOrFail($id);
        $parentId = $folder->parent_id;
        
        $folder->delete();

        if($parentId) {
            return to_route('smkp.index', $parentId)->with('success', 'Berhasil dihapus.');
        }
        return to_route('smkp.index')->with('success', 'Berhasil dihapus.');
    }

    public function download($id)
    {
        $file = FileUpload::with(['folder', 'user'])->findOrFail($id);
        $user = Auth::user();

        $isPanduanFile = false;
        if ($file->folder) {
            $rootTab = $file->folder;
            while($rootTab->parent_id != null) {
                $rootTab = $rootTab->parent;
            }
            if (stripos($rootTab->name, 'panduan') !== false) {
                $isPanduanFile = true;
            }
        }

        $isUploadedByAuditor = $file->user && $file->user->role === 'Auditor';

        if ($user->role !== 'Auditor' && $file->user_id !== $user->id && !$isPanduanFile && !$isUploadedByAuditor) {
            abort(403, 'Anda tidak memiliki izin untuk mengunduh file ini.');
        }

        // PENYESUAIAN DI SINI: Gunakan Storage::disk('public') agar membaca dari disk yang benar
        return Storage::disk('public')->download($file->file_path, $file->name . '.' . pathinfo($file->file_path, PATHINFO_EXTENSION));
    }

    public function viewFile($id)
    {
        $file = FileUpload::with(['folder', 'user'])->findOrFail($id);
        $user = Auth::user();

        // 1. Cek izin akses (Sama persis seperti logika download)
        $isPanduanFile = false;
        if ($file->folder) {
            $rootTab = $file->folder;
            while($rootTab->parent_id != null) {
                $rootTab = $rootTab->parent;
            }
            if (stripos($rootTab->name, 'panduan') !== false) {
                $isPanduanFile = true;
            }
        }

        $isUploadedByAuditor = $file->user && $file->user->role === 'Auditor';

        if ($user->role !== 'Auditor' && $file->user_id !== $user->id && !$isPanduanFile && !$isUploadedByAuditor) {
            abort(403, 'Anda tidak memiliki izin untuk melihat file ini.');
        }

        $filePath = storage_path('app/public/' . $file->file_path);
        if (!file_exists($filePath)) {
            abort(404, 'File tidak ditemukan di server.');
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // 2. Jika file berupa PDF atau Gambar, render langsung secara "inline" di Browser
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif'])) {
            return response()->file($filePath, [
                'Content-Disposition' => 'inline; filename="' . $file->name . '.' . $ext . '"'
            ]);
        }

        // 3. Jika file berupa Word/Excel/PPT, tampilkan melalui halaman Blade Preview
        // Note: Memerlukan koneksi internet dan domain publik untuk merender Office File via Google Docs Viewer
        $publicUrl = asset('storage/' . $file->file_path); 
        
        return view('smkp.preview', compact('file', 'publicUrl', 'ext'));
    }

    public function deleteFile($id)
    {
        $file = FileUpload::findOrFail($id);
        $user = Auth::user();

        if ($user->role !== 'Auditor' && $file->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus file ini.');
        }

        // PENYESUAIAN DI SINI: Hapus fisik file menggunakan disk public
        if (Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete();

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }
}