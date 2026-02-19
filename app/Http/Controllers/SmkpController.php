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
        $panduanFolders = collect(); 
        $folders = collect();

        // --- 1. LOGIKA FOLDER & BREADCRUMBS ---
        if (!$folderId) {
            // === POSISI ROOT (HALAMAN UTAMA) ===
            $folders = Folder::whereNull('parent_id')
                             ->where('type', 'main')
                             ->get();

            $panduanFolders = Folder::whereNull('parent_id')
                                    ->where('type', 'panduan')
                                    ->get();

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
            // AUDITOR: Melihat semua file
            $files = $fileQuery->with('user')->latest()->get();
        } else {
            // USER BIASA
            $isPanduanArea = $currentFolder && $currentFolder->type === 'panduan';

            if ($isPanduanArea) {
                // Di area PANDUAN: User biasa boleh melihat SEMUA file (Read Only)
                $files = $fileQuery->with('user')->latest()->get();
            } else {
                // Di area UTAMA/ROOT: 
                // Hanya file milik sendiri ATAU file yang diupload oleh Auditor (bisa dilihat semua role)
                $fileQuery->where(function($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhereHas('user', function($u) {
                          $u->where('role', 'Auditor');
                      });
                });
                $files = $fileQuery->with('user')->latest()->get();
            }
        }

        return view('smkp.index', compact('folders', 'panduanFolders', 'files', 'currentFolder', 'breadcrumbs', 'units'));
    }

    public function upload(Request $request, $folderId = null)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // Max 50MB
            'name' => 'required|string|max:255', 
        ]);

        if ($folderId) {
            $folder = Folder::findOrFail($folderId);
            if ($folder->type === 'panduan' && Auth::user()->role !== 'Auditor') {
                abort(403, 'Hanya Auditor yang dapat mengunggah dokumen di folder Panduan.');
            }
        }

        $file = $request->file('file');
        $path = $file->store('public/smkp_files');

        FileUpload::create([
            'folder_id' => $folderId,
            'user_id'   => Auth::id(),
            'name'      => $request->name,
            'file_path' => str_replace('public/', '', $path),
            'mime_type' => $file->getClientMimeType(),
        ]);

        return back()->with('success', 'File berhasil disimpan.');
    }

    // --- FOLDER MANAGEMENT (HANYA AUDITOR) ---

    public function createFolder(Request $request, $parentId = null)
    {
        if (Auth::user()->role !== 'Auditor') {
            abort(403, 'Hanya Auditor yang dapat membuat folder.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'type' => 'in:main,panduan',
        ]);

        $type = 'main';

        if ($parentId) {
            $parent = Folder::findOrFail($parentId);
            $type = $parent->type;
        } else {
            $type = $request->input('type', 'main');
        }

        Folder::create([
            'name' => $request->name,
            'code' => $request->code,
            'parent_id' => $parentId,
            'type' => $type
        ]);

        return back()->with('success', 'Folder berhasil dibuat.');
    }

    public function updateFolder(Request $request, $id)
    {
        if (Auth::user()->role !== 'Auditor') {
            abort(403, 'Hanya Auditor yang dapat mengubah folder.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
        ]);

        $folder = Folder::findOrFail($id);
        $folder->update([
            'name' => $request->name,
            'code' => $request->code
        ]);

        return back()->with('success', 'Folder berhasil diperbarui.');
    }

    public function deleteFolder($id)
    {
        if (Auth::user()->role !== 'Auditor') {
            abort(403, 'Hanya Auditor yang dapat menghapus folder.');
        }

        $folder = Folder::findOrFail($id);
        $parentId = $folder->parent_id;
        
        $folder->delete();

        if($parentId) {
            return to_route('smkp.index', $parentId)->with('success', 'Folder berhasil dihapus.');
        }
        return to_route('smkp.index')->with('success', 'Folder berhasil dihapus.');
    }
    
    // --- FILE ACTIONS ---

    public function download($id)
    {
        $file = FileUpload::with(['folder', 'user'])->findOrFail($id);
        $user = Auth::user();

        $isPanduanFile = $file->folder && $file->folder->type === 'panduan';
        $isUploadedByAuditor = $file->user && $file->user->role === 'Auditor';

        // Bisa didownload jika: Auditor, Milik Sendiri, Berada di Folder Panduan, atau diupload oleh Auditor
        if ($user->role !== 'Auditor' && $file->user_id !== $user->id && !$isPanduanFile && !$isUploadedByAuditor) {
            abort(403, 'Anda tidak memiliki izin untuk mengunduh file ini.');
        }

        return Storage::download('public/' . $file->file_path, $file->name . '.' . pathinfo($file->file_path, PATHINFO_EXTENSION));
    }

    public function deleteFile($id)
    {
        $file = FileUpload::findOrFail($id);
        $user = Auth::user();

        if ($user->role !== 'Auditor' && $file->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus file ini.');
        }

        if (Storage::exists('public/' . $file->file_path)) {
            Storage::delete('public/' . $file->file_path);
        }

        $file->delete();

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }
}