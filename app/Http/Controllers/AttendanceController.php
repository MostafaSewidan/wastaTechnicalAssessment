<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\HrmSetting;
use App\Models\Attendance;
use Carbon\Carbon;
use Auth;
use DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AttendanceController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('attendance')) {
            $lims_employee_list = Employee::where('is_active', true)->get();
            $lims_hrm_setting_data = HrmSetting::latest()->first();
            $general_setting = DB::table('general_settings')->latest()->first();
            if(Auth::user()->role_id > 2 && $general_setting->staff_access == 'own')
            $lims_attendance_data = Attendance::leftJoin('employees', 'employees.id', '=', 'attendances.employee_id')
                ->leftJoin('users', 'users.id', '=', 'attendances.user_id')
                ->orderBy('attendances.date', 'desc')
                ->where('attendances.user_id', Auth::id())
                ->select(['attendances.*', 'employees.name as employee_name', 'users.name as user_name'])
                ->get()
                ->groupBy(['date','employee_id']);
            else
            $lims_attendance_data = Attendance::leftJoin('employees', 'employees.id', '=', 'attendances.employee_id')
                ->leftJoin('users', 'users.id', '=', 'attendances.user_id')
                ->orderBy('attendances.date', 'desc')
                ->select(['attendances.*', 'employees.name as employee_name', 'users.name as user_name'])
                ->get()
                ->groupBy(['date','employee_id']);

            $lims_attendance_all= [];
            foreach ($lims_attendance_data as  $attendance_data) {
                foreach ($attendance_data as $data) {
                    $checkin_checkout = '';
                    foreach ($data as $key => $dt) {
                        $date = $dt->date;
                        $employee_name = $dt->employee_name;
                        $checkin_checkout .= (($dt->checkin != null) ? $dt->checkin : 'N/A'). ' - ' .(($dt->checkout != null) ? $dt->checkout : 'N/A'). '<br>';
                        $status = $dt->status;
                        $user_name = $dt->user_name;
                        $employee_id = $dt->employee_id;
                    }
                    $lims_attendance_all[] = ['date'=>$date, 'employee_name'=>$employee_name,
                                            'checkin_checkout'=>$checkin_checkout, 'status'=>$status,
                                            'user_name'=>$user_name, 'employee_id'=>$employee_id];
                }
            }
            return view('backend.attendance.index', compact('lims_employee_list', 'lims_hrm_setting_data', 'lims_attendance_all'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }


    public function create()
    {
        //
    }


    public function store(Request $request)
    {
        $data = $request->all();
        $employee_id =  $data['employee_id'];
        $lims_hrm_setting_data = HrmSetting::latest()->first();
        $checkin = $lims_hrm_setting_data->checkin;
        foreach ($employee_id as $id) {
            $data['date'] = date('Y-m-d', strtotime(str_replace('/', '-', $data['date'])));
            $data['user_id'] = Auth::id();
            $lims_attendance_data = Attendance::whereDate('date', $data['date'])->where('employee_id', $id)->first();
            $data['employee_id'] = $id;
            if(!$lims_attendance_data){
                $diff = strtotime($checkin) - strtotime($data['checkin']);
                if($diff >= 0)
                    $data['status'] = 1;
                else
                    $data['status'] = 0;
            }
            else {
                $data['status'] = $lims_attendance_data->status;
            }
            Attendance::create($data);
        }
        return redirect()->back()->with('message', 'Attendance created successfully');
    }

    public function importDeviceCsv(Request $request)
    {
        $upload = $request->file('file');
        if ($request->Attendance_Device_date_format == null || $upload == null) {
            return redirect()->back()->with('not_permitted', 'Please select Attendance Device Date Format and upload a CSV file');
        }

        $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
        if($ext != 'csv')
            return redirect()->back()->with('not_permitted', 'Please upload a CSV file');

        $filename =  $upload->getClientOriginalName();
        $filePath=$upload->getRealPath();
        //open and read
        $file=fopen($filePath, 'r');
        $exclude_header= fgetcsv($file);

        $employee_all = Employee::all();
        $lims_hrm_setting_data = HrmSetting::latest()->first();
        $checkin = $lims_hrm_setting_data->checkin;
        $data = [];
        //looping through other columns
        while($columns=fgetcsv($file))
        {
            if($columns[0]=="" || $columns[1]=="")
                continue;

            $staff_id = $columns[0];
            $employee = $employee_all->where('staff_id', $staff_id)->first();
            if (!$employee)
                return redirect()->back()->with('not_permitted', 'Staff id - '. $staff_id. ' is not available within the POS system');

            $dt_time = explode(' ', $columns[1], 2);
            $attendance_date = Carbon::createFromFormat($request->Attendance_Device_date_format, $dt_time[0])->format('Y-m-d');
            $attendance_time = str_replace(' ','',$dt_time[1]);
            $i = 0;
            $status = 0;
            foreach ($data as $key => $dt) {
                if ($dt['date'] == $attendance_date && $dt['employee_id'] == $employee->id) {
                    $status = $dt['status'];
                    $i++;
                    if ($dt['checkout'] == null) {
                        $data[$key]['checkout'] =  $attendance_time;
                        $i = -1;
                        break;
                    }
                }
            }
            //checkout update
            if ($i == -1) {
                continue;
            }
            //create attendance at first time for the employee and date
            elseif ($i == 0) {
                $diff = strtotime($checkin) - strtotime($attendance_time);
                if($diff >= 0)
                    $status = 1;
                else
                    $status = 0;

                $data[] = ['date' => $attendance_date, 'employee_id' => $employee->id, 'user_id' => Auth::id(),
                    'checkin' => $attendance_time, 'checkout' => null, 'status' => $status];
            }
            //create attendance after first time
            else {
                $data[] = ['date' => $attendance_date, 'employee_id' => $employee->id, 'user_id' => Auth::id(),
                    'checkin' => $attendance_time, 'checkout' => null, 'status' => $status];
            }
        }
        //create composite via migration with this 2nd array parameter
        Attendance::upsert($data, ['date','employee_id','checkin'], ['checkout']);
        return redirect()->back()->with('message', 'Attendance created successfully');
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
        //
    }

    public function deleteBySelection(Request $request)
    {
        $attendance_selected = $request['attendanceSelectedArray'];
        foreach ($attendance_selected as $att_selected) {
            Attendance::wheredate('date', $att_selected[0])->where('employee_id', $att_selected[1])->delete();
        }
        return 'Attendance deleted successfully!';
    }

    public function delete($date, $employee_id)
    {
        Attendance::wheredate('date', $date)->where('employee_id', $employee_id)->delete();
        return redirect()->back()->with('not_permitted', 'Attendance deleted successfully');
    }
}
