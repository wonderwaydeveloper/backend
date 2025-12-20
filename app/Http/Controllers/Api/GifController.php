<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GiphyService;
use Illuminate\Http\Request;

class GifController extends Controller
{
    protected $giphyService;

    public function __construct(GiphyService $giphyService)
    {
        $this->giphyService = $giphyService;
    }

    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $gifs = $this->giphyService->search(
            $request->q,
            $request->input('limit', 20)
        );

        return response()->json($gifs);
    }

    public function trending(Request $request)
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $gifs = $this->giphyService->trending(
            $request->input('limit', 20)
        );

        return response()->json($gifs);
    }
}
