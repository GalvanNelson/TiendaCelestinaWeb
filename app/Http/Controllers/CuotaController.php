<?php

namespace App\Http\Controllers;

use App\Models\Cuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuotaController extends Controller
{
    /**
     * Marcar cuota como pagada
     */
    public function marcarPagada(Request $request, Cuota $cuota)
    {
        $validated = $request->validate([
            'fecha_pago' => 'required|date',
        ], [
            'fecha_pago.required' => 'La fecha de pago es obligatoria.',
        ]);

        DB::beginTransaction();
        try {
            $cuota->update([
                'fecha_pago' => $validated['fecha_pago'],
                'estado' => 'pagada',
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Cuota marcada como pagada.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al marcar la cuota: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar estados de cuotas vencidas (comando programado)
     */
    public function actualizarEstadosVencidos()
    {
        Cuota::where('estado', 'pendiente')
            ->where('fecha_vencimiento', '<', now())
            ->update(['estado' => 'vencida']);

        return response()->json(['message' => 'Estados actualizados']);
    }
}
