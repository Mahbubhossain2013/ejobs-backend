<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CvTemplate;
use Illuminate\Http\Request;

class CvTemplateController extends Controller
{
    /**
     * Get all active CV templates
     */
    public function index()
    {
        $templates = CvTemplate::where('is_active', true)->get();

        return response()->json(['status' => true, 'data' => $templates]);
    }

    public function publicTemplates()
    {
        return $this->index();
    }
}