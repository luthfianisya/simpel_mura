<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RincianBiayaController extends Controller
{
    public function index()
    {
        return view('rincian-biaya.index');
    }
}