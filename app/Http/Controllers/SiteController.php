<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use App\Settings\GeneralSettings;

class SiteController extends Controller
{
    public function home(GeneralSettings $setting){
        return view('home',['setting'=>$setting]);
    }
}
