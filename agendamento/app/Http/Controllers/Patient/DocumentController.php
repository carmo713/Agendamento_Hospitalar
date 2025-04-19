<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Display a listing of the patient's documents.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $patient = Auth::user()->patient;
        
        $query = Document::where('patient_id', $patient->id);
        
        // Filtros opcionais
        if ($request->has('type') && $request->type != 'all') {
            $query->where('type', $request->type);
        }
        
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        $documents = $query->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();
        
        $documentTypes = [
            'exam' => 'Exames',
            'report' => 'Relatórios',
            'image' => 'Imagens médicas',
            'other' => 'Outros'
        ];
        
        return view('patient.documents.index', [
            'documents' => $documents,
            'documentTypes' => $documentTypes,
            'selectedType' => $request->type ?? 'all'
        ]);
    }
    
    /**
     * Show the form for uploading a new document.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $documentTypes = [
            'exam' => 'Exames',
            'report' => 'Relatórios',
            'image' => 'Imagens médicas',
            'other' => 'Outros'
        ];
        
        return view('patient.documents.upload', [
            'documentTypes' => $documentTypes
        ]);
    }
    
    /**
     * Store a newly created document.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:exam,report,image,other',
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
            'date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);
        
        try {
            $patient = Auth::user()->patient;
            
            // Upload do arquivo
            $file = $request->file('file');
            $path = $file->store('patient_documents/' . $patient->id, 'public');
            
            $document = new Document();
            $document->patient_id = $patient->id;
            $document->name = $request->name;
            $document->type = $request->type;
            $document->file_path = $path;
            $document->date = $request->date;
            $document->notes = $request->notes;
            $document->save();
            
            return redirect()->route('patient.documents.index')
                ->with('success', 'Documento enviado com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao enviar o documento. Por favor, tente novamente.')
                ->withInput();
        }
    }
    
    /**
     * Display the specified document.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $document = Document::where('patient_id', Auth::user()->patient->id)
            ->findOrFail($id);
        
        $documentTypes = [
            'exam' => 'Exame',
            'report' => 'Relatório',
            'image' => 'Imagem médica',
            'other' => 'Outro'
        ];
        
        return view('patient.documents.show', [
            'document' => $document,
            'documentType' => $documentTypes[$document->type] ?? 'Documento'
        ]);
    }
    
    /**
     * Show the form for editing a document.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $document = Document::where('patient_id', Auth::user()->patient->id)
            ->findOrFail($id);
        
        $documentTypes = [
            'exam' => 'Exames',
            'report' => 'Relatórios',
            'image' => 'Imagens médicas',
            'other' => 'Outros'
        ];
        
        return view('patient.documents.edit', [
            'document' => $document,
            'documentTypes' => $documentTypes
        ]);
    }
    
    /**
     * Update the specified document.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:exam,report,image,other',
            'file' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
            'date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);
        
        $document = Document::where('patient_id', Auth::user()->patient->id)
            ->findOrFail($id);
        
        try {
            $document->name = $request->name;
            $document->type = $request->type;
            $document->date = $request->date;
            $document->notes = $request->notes;
            
            // Se houver um novo arquivo
            if ($request->hasFile('file')) {
                // Excluir arquivo antigo
                if (Storage::disk('public')->exists($document->file_path)) {
                    Storage::disk('public')->delete($document->file_path);
                }
                
                // Upload do novo arquivo
                $file = $request->file('file');
                $path = $file->store('patient_documents/' . $document->patient_id, 'public');
                $document->file_path = $path;
            }
            
            $document->save();
            
            return redirect()->route('patient.documents.show', $document->id)
                ->with('success', 'Documento atualizado com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao atualizar o documento. Por favor, tente novamente.')
                ->withInput();
        }
    }
    
    /**
     * Remove the specified document.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $document = Document::where('patient_id', Auth::user()->patient->id)
            ->findOrFail($id);
        
        try {
            // Excluir arquivo
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            
            $document->delete();
            
            return redirect()->route('patient.documents.index')
                ->with('success', 'Documento excluído com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao excluir o documento. Por favor, tente novamente.');
        }
    }
    
    /**
     * Download the specified document.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function download($id)
    {
        $document = Document::where('patient_id', Auth::user()->patient->id)
            ->findOrFail($id);
        
        if (Storage::disk('public')->exists($document->file_path)) {
            $path = Storage::disk('public')->path($document->file_path);
            $filename = $document->name . '.' . pathinfo($path, PATHINFO_EXTENSION);
            
            return response()->download($path, $filename);
        }
        
        return back()->with('error', 'Arquivo não encontrado.');
    }
}