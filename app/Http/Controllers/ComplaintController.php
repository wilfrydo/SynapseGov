<?php

namespace App\Http\Controllers;

use App\Models\Complaint;

class ComplaintController extends Controller
{
    /**
     * API endpoint for complaint statistics
     */
    public function stats()
    {
        $stats = [
            'total' => Complaint::count(),
            'pending' => Complaint::where('status', 'pending')->count(),
            'investigating' => Complaint::where('status', 'investigating')->count(),
            'resolved' => Complaint::where('status', 'resolved')->count(),
            'by_priority' => Complaint::selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->get()
                ->pluck('count', 'priority'),
            'by_category' => Complaint::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->get()
                ->pluck('count', 'category'),
        ];

        return response()->json($stats);
    }
}
