<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Biller;
use App\Models\MailSetting;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Auth;
use App\Mail\BillerCreate;
use Mail;

class BillerController extends Controller
{
    use \App\Traits\CacheForget;
    use \App\Traits\TenantInfo;
    use \App\Traits\MailInfo;

    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('billers-index')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if(empty($all_permission))
                $all_permission[] = 'dummy text';
            $lims_biller_all = biller::where('is_active', true)->get();
            return view('backend.biller.index',compact('lims_biller_all', 'all_permission'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function create()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('billers-add'))
            return view('backend.biller.create');
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'company_name' => [
                'max:255',
                    Rule::unique('billers')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'email' => [
                'email',
                'max:255',
                    Rule::unique('billers')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'image' => 'image|mimes:jpg,jpeg,png,gif|max:10000',
        ]);

        $lims_biller_data = $request->except('image');
        $lims_biller_data['is_active'] = true;
        $image = $request->image;
        if ($image) {
            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis");
            if(!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
                $image->move('public/images/biller', $imageName);
            }
            else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                $image->move('public/images/biller', $imageName);
            }
            $lims_biller_data['image'] = $imageName;
        }
        Biller::create($lims_biller_data);
        $this->cacheForget('biller_list');

        $mailSetting = MailSetting::latest()->first();
        $message = $this->mailAction($lims_biller_data, $mailSetting);
        return redirect('biller')->with('message', $message);

    }

    public function edit($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('billers-edit')) {
            $lims_biller_data = Biller::where('id',$id)->first();
            return view('backend.biller.edit',compact('lims_biller_data'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'company_name' => [
                'max:255',
                    Rule::unique('billers')->ignore($id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'email' => [
                'email',
                'max:255',
                    Rule::unique('billers')->ignore($id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],

            'image' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
        ]);

        $lims_biller_data = Biller::findOrFail($id);
        $input = $request->except('image');
        $image = $request->image;
        if ($image) {
            $this->fileDelete('images/biller/', $lims_biller_data->image);

            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis");
            if(!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
                $image->move('public/images/biller', $imageName);
            }
            else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                $image->move('public/images/biller', $imageName);
            }
            $input['image'] = $imageName;
        }

        $lims_biller_data->update($input);
        $this->cacheForget('biller_list');
        return redirect('biller')->with('message','Data updated successfully');
    }

    public function importBiller(Request $request)
    {
        $upload=$request->file('file');
        $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
        if($ext != 'csv')
            return redirect()->back()->with('not_permitted', 'Please upload a CSV file');
        $filename =  $upload->getClientOriginalName();
        $filePath=$upload->getRealPath();
        //open and read
        $file=fopen($filePath, 'r');
        $header= fgetcsv($file);
        $escapedHeader=[];
        //validate
        foreach ($header as $key => $value) {
            $lheader=strtolower($value);
            $escapedItem=preg_replace('/[^a-z]/', '', $lheader);
            array_push($escapedHeader, $escapedItem);
        }

        $mailSetting = MailSetting::latest()->first();

        //looping through othe columns
        while($columns=fgetcsv($file))
        {
            if($columns[0]=="")
                continue;
            foreach ($columns as $key => $value) {
                $value=preg_replace('/\D/','',$value);
            }
            $data= array_combine($escapedHeader, $columns);

            $biller = Biller::firstOrNew(['company_name'=>$data['companyname']]);
            $biller->name = $data['name'];
            $biller->image = $data['image'];
            $biller->vat_number = $data['vatnumber'];
            $biller->email = $data['email'];
            $biller->phone_number = $data['phonenumber'];
            $biller->address = $data['address'];
            $biller->city = $data['city'];
            $biller->state = $data['state'];
            $biller->postal_code = $data['postalcode'];
            $biller->country = $data['country'];
            $biller->is_active = true;
            $biller->save();
            $message = $this->mailAction($data, $mailSetting);
        }
        $this->cacheForget('biller_list');
        return redirect('biller')->with('message', $message);
    }

    protected function mailAction($data, $mailSetting)
    {
        $message = 'Data inserted successfully';
        if(!$mailSetting) {
            $message = 'Data inserted successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
        }
        else if($data['email'] && $mailSetting) {
            try{
                $this->setMailInfo($mailSetting);
                Mail::to($data['email'])->send(new BillerCreate($data));
            }
            catch(\Exception $e){
                $message = $e->getMessage();
            }
        }
        return $message;
    }

    public function deleteBySelection(Request $request)
    {
        $biller_id = $request['billerIdArray'];
        // Biller::whereIn($biller_id)->update(['is_active'=>false]);

        foreach ($biller_id as $id) {
            $lims_biller_data = Biller::find($id);
            $lims_biller_data->is_active = false;
            $lims_biller_data->save();

            $this->fileDelete('images/biller/', $lims_biller_data->image);
        }

        $this->cacheForget('biller_list');
        return 'Biller deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_biller_data = Biller::find($id);
        $this->fileDelete('images/biller/', $lims_biller_data->image);

        $lims_biller_data->is_active = false;
        $lims_biller_data->save();
        $this->cacheForget('biller_list');
        return redirect('biller')->with('not_permitted','Data deleted successfully');
    }
}
