<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clinicId = $request->query('clinic');
        
        $query = Room::with('clinic');
        
        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }
        
        $rooms = $query->paginate(10);
        $clinics = Clinic::all(); // Para o filtro de clínicas
        
        return view('admin.rooms.index', compact('rooms', 'clinics', 'clinicId'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $clinicId = $request->query('clinic');
        $clinic = $clinicId ? Clinic::find($clinicId) : null;
        $clinics = Clinic::all();
        
        return view('admin.rooms.create', compact('clinics', 'clinic'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clinic_id' => 'required|exists:clinics,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.rooms.create', ['clinic' => $request->clinic_id])
                ->withErrors($validator)
                ->withInput();
        }

        Room::create($request->all());

        return redirect()->route('admin.rooms.index', ['clinic' => $request->clinic_id])
            ->with('success', 'Sala criada com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Room $room)
    {
        $room->load('clinic');
        
        return view('admin.rooms.show', compact('room'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Room $room)
    {
        $clinics = Clinic::all();
        
        return view('admin.rooms.edit', compact('room', 'clinics'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Room $room)
    {
        $validator = Validator::make($request->all(), [
            'clinic_id' => 'required|exists:clinics,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.rooms.edit', $room->id)
                ->withErrors($validator)
                ->withInput();
        }

        $room->update($request->all());

        return redirect()->route('admin.rooms.index', ['clinic' => $room->clinic_id])
            ->with('success', 'Sala atualizada com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room $room)
    {
        $clinicId = $room->clinic_id;
        
        try {
            $room->delete();
            return redirect()->route('admin.rooms.index', ['clinic' => $clinicId])
                ->with('success', 'Sala excluída com sucesso.');
        } catch (\Exception $e) {
            return redirect()->route('admin.rooms.index', ['clinic' => $clinicId])
                ->with('error', 'Erro ao excluir sala. Verifique se não há agendamentos associados.');
        }
    }

    /**
     * Display rooms for a specific clinic.
     */
    public function byClinic(Clinic $clinic)
    {
        $rooms = Room::where('clinic_id', $clinic->id)->paginate(10);
        $clinics = Clinic::all(); // Para o filtro de clínicas
        $clinicId = $clinic->id;
        
        return view('admin.rooms.index', compact('rooms', 'clinics', 'clinicId', 'clinic'));
    }
}