<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Search\SearchService;
use App\Models\SavedSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Unified search endpoint
     */
    public function search(Request $request)
    {
        $request->validate([
            'target' => 'required|string|in:all,jobs,remote-jobs,employers,candidates,skills,categories,resumes,portfolios',
            'q' => 'nullable|string',
            'filters' => 'nullable|array',
        ]);

        $target = $request->input('target');
        $query = $request->input('q', '');
        $filters = $request->input('filters', []);
        $userId = Auth::guard('sanctum')->id();

        $results = $this->searchService->search($target, $query, $filters, 20, $userId);

        return response()->json([
            'status' => true,
            'data' => $results
        ]);
    }

    /**
     * Typeahead/autocomplete suggestions
     */
    public function suggestions(Request $request)
    {
        $query = $request->input('q', '');
        $suggestions = $this->searchService->getSuggestions($query);

        return response()->json([
            'status' => true,
            'data' => $suggestions
        ]);
    }

    /**
     * Trending searches
     */
    public function trending()
    {
        $trending = $this->searchService->getTrendingSearches();

        return response()->json([
            'status' => true,
            'data' => $trending
        ]);
    }

    /**
     * Save query filters for active user
     */
    public function saveSearch(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'q' => 'nullable|string',
            'filters' => 'nullable|array',
        ]);

        $user = $request->user();
        $saved = $this->searchService->saveSearch(
            $user->id,
            $request->input('name'),
            $request->input('q', ''),
            $request->input('filters', [])
        );

        return response()->json([
            'status' => true,
            'message' => 'Search filters saved successfully.',
            'data' => $saved
        ]);
    }

    /**
     * Retrieve all saved searches for active user
     */
    public function getSavedSearches(Request $request)
    {
        $user = $request->user();
        $saved = SavedSearch::where('user_id', $user->id)->latest()->get();

        return response()->json([
            'status' => true,
            'data' => $saved
        ]);
    }

    /**
     * Delete a specific saved search
     */
    public function deleteSavedSearch(Request $request, $id)
    {
        $user = $request->user();
        $saved = SavedSearch::where('user_id', $user->id)->where('id', $id)->firstOrFail();
        $saved->delete();

        return response()->json([
            'status' => true,
            'message' => 'Saved search deleted successfully.'
        ]);
    }
}
