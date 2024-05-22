<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Validation\Rule;
use App\Models\Roles;
use App\Models\CustomerGroup;
use App\Models\Biller;
use App\Models\Warehouse;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        $lims_role_list = Roles::where('is_active', true)->get();
        $lims_customer_group_list = CustomerGroup::where('is_active', true)->get();
        $lims_biller_list = Biller::where('is_active', true)->get();
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        $numberOfUserAccount = User::where('is_active', true)->count();
        return view('backend.auth.register', compact('lims_role_list', 'lims_customer_group_list', 'lims_biller_list', 'lims_warehouse_list', 'numberOfUserAccount'));
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255|unique:users',
            'email' => [
                'email',
                'max:255',
                    Rule::unique('users')->where(function ($query) {
                    return $query->where('is_deleted', false);
                }),
            ],
            'password' => 'required|string|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $data['is_active'] = false;
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone_number'],
            'company_name' => $data['company_name'],
            'role_id' => $data['role_id'],
            'biller_id' => $data['biller_id'],
            'warehouse_id' => $data['warehouse_id'],
            'is_active' => $data['is_active'],
            'is_deleted' => false,
            'password' => bcrypt($data['password']),
        ]);

        if($data['role_id'] == 5) {
            $data['name'] = $data['customer_name'];
            $data['user_id'] = $user->id;
            $data['is_active'] = true;
            Customer::create($data);
        }

        return $user;
    }
}
