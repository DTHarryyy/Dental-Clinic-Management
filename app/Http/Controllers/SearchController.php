<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearch;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, GlobalSearch $search)
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $term = preg_replace('/\s+/u', ' ', trim($data['q']));

        if (mb_strlen($term) < 2) {
            return response()->json(['message' => 'Enter at least two characters.'], 422);
        }

        return response()->json($search->search($term, $request->user()));
    }
}
