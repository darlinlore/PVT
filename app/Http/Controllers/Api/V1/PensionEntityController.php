<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\PensionEntity;
class PensionEntityController extends Controller
{
    public function index()
    {
        return PensionEntity::get();
    }
}