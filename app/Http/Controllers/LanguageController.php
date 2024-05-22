<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Redirect;
use App\Models\Language;

class LanguageController extends Controller
{
    public function switchLanguage($locale)
    {
        setcookie('language', $locale, time() + (86400 * 365), "/");
        return Redirect::back();
    }

    public function switchLandingPageLanguage($lang_id)
    {
        setcookie('landing_page_language', $lang_id, time() + (86400 * 365), "/");
        return Redirect::back();
    }

    public function index()
    {
        $lims_language_all = Language::get();
        return view('landlord.language.index', compact('lims_language_all'));
    }

    public function store(Request $request)
    {
        if(!env('USER_VERIFIED'))
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        $data = $request->all();
        if(isset($request->is_default))
            $data['is_default'] = true;
        else
            $data['is_default'] = false;
        Language::create($data);
        $this->cacheForget('languages');
        return redirect()->back()->with('message', 'Language created successfully');
    }

    public function update(Request $request)
    {
        if(!env('USER_VERIFIED'))
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        $data = $request->all();
        if(isset($request->is_default)) {
            $data['is_default'] = true;
            Language::where('is_default', true)->first()->update(['is_default' => false]);
            cache()->forget('hero');
            cache()->forget('module_descriptions');
            cache()->forget('faq_descriptions');
            cache()->forget('tenant_signup_descriptions');
        }
        else
            $data['is_default'] = false;
        Language::find($data['language_id'])->update($data);
        $this->cacheForget('languages');
        return redirect()->back()->with('message', 'Language updated successfully');
    }
}
