<?php

namespace App\Http\Controllers\League\Teams;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;

class SearchCrest extends Controller
{
    public function __invoke()
    {
        $data = Request::validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $queries = collect([
            $data['name'],
            trim(preg_replace('/\b(FC|SC|AFC|CF|CS|FK|SK|AC|AS|SS|SSC|US|CD|RC|UD|SV|VfB|VfL|TSG|BV|IF|CFR|ACS)\b\.?/iu', '', $data['name'])),
        ])->filter()->unique();

        $results = collect();

        foreach ($queries as $query) {
            $response = Http::timeout(8)
                ->get('https://www.thesportsdb.com/api/v1/json/3/searchteams.php', ['t' => $query]);

            if ($response->failed()) {
                continue;
            }

            $results = collect($response->json('teams') ?? [])
                ->filter(fn ($team) => ($team['strSport'] ?? null) === 'Soccer' && ! empty($team['strBadge']))
                ->map(fn ($team) => [
                    'name' => $team['strTeam'],
                    'league' => $team['strLeague'] ?? null,
                    'country' => $team['strCountry'] ?? null,
                    'crest_url' => $team['strBadge'],
                ])
                ->values()
                ->take(8);

            if ($results->isNotEmpty()) {
                break;
            }
        }

        return response()->json(['results' => $results]);
    }
}
