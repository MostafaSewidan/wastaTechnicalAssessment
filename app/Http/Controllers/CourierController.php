<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Courier;
use DB;

class CourierController extends Controller
{

    public function index()
    {
        $lims_courier_all = Courier::where('is_active', true)->orderBy('id', 'desc')->get();
        return view('backend.courier.index', compact('lims_courier_all'));
    }

    public function shippers()
    {
        if(isset($_GET['shipper'])){
            $state = $_GET['state'];
            $shipper = $_GET['shipper'];
            $lims_courier_all = DB::table('places')->where('state',$state)->where('company',$shipper)->get();
        }
        else
        {
            $lims_courier_all = DB::table('places')->groupby('company')->get();

        }
        return view('backend.courier.shippers', compact('lims_courier_all'));
    }
    public function addshipper($name){
       $data = DB::table('places')->where('company','Basic')->get();
       foreach($data as $item){
           DB::table('places')->insert([
               'state' => $item->state,
               'city' => $item->city,
               'shipping' => $item->shipping,
               'fee' => $item->fee,
               'company' => $name,
               ]);
       }
               return redirect()->back()->with('message', $name.' created successfully');


    }
    public function updateplaces(Request $request){
       $ids = $request->id;
       $shippings = $request->shipping;
       $fees = $request->fee;
       
       foreach ($ids as $key => $id){
           $shipping = $shippings[$key];
           $fee = $fees[$key];
           $update = DB::table('places')->where('id',$id)->update(['shipping' => $shipping , 'fee' => $fee]);
           if($update){
               echo $id.' with shipping '.$shipping.' and '.$fee.' Updated<br><hr><br>';
           }
       }
       
      echo 'سيتم تحويلك بعد 10 ثوان';
      sleep(10);
                      return redirect()->back()->with('message', ' Updated Successfully');

    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $request->is_active = true;
        Courier::create($request->all());
        return redirect()->back()->with('message', 'Courier created successfully');
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
        Courier::find($request->id)->update($request->all());
        return redirect()->back()->with('message', 'Courier updated successfully');
    }

    public function destroy($id)
    {
        Courier::find($id)->update(['is_active' => false]);
        return redirect()->back()->with('not_permitted', 'Courier deleted successfully');
    }
}
