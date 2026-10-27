<?php

namespace App\Http\Controllers\Api\N8n;

use App\Http\Controllers\Controller;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FaqController extends Controller
{
    /** FAQ dasar penggunaan LMS (Workflow 2, node "Ambil FAQ"). */
    public function index(): AnonymousResourceCollection
    {
        return FaqResource::collection(Faq::aktif()->orderBy('id')->get());
    }
}
