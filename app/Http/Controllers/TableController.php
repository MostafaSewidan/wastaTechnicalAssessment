<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Table;

class TableController extends Controller
{
    use \App\Traits\CacheForget;
    public function index()
    {
        $lims_table_all = Table::where('is_active', true)->get();
        return view('backend.table.index', compact('lims_table_all'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['is_active'] = true;
        Table::create($data);
        $this->cacheForget('table_list');
        return redirect()->back()->with('message', 'Table created successfully');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        Table::find($request->table_id)->update($request->all());
        $this->cacheForget('table_list');
        return redirect()->back()->with('message', 'Table updated successfully');
    }

    public function destroy($id)
    {
        Table::find($id)->update(['is_active'=>false]);
        $this->cacheForget('table_list');
        return redirect()->back()->with('message', 'Table deleted successfully');
    }
}
