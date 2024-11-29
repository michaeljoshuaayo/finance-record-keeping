<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FinancialRecord;

class FinancialRecordController extends Controller
{
    public function index()
    {
        return FinancialRecord::all();
    }

    public function store(Request $request)
    {
        $record = FinancialRecord::create($request->all());
        return response()->json($record, 201);
    }

    public function update(Request $request, $id)
    {
        $record = FinancialRecord::findOrFail($id);
        $record->update($request->all());
        return response()->json($record, 200);
    }

    public function destroy($id)
    {
        FinancialRecord::destroy($id);
        return response()->json(null, 204);
    }

    public function deleteMultiple(Request $request)
    {
        FinancialRecord::destroy($request->ids);
        return response()->json(null, 204);
    }
}
