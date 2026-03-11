<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pokemon;
use App\Services\PokeApiService;
use App\Services\QuestionCatalog;
use App\Services\QuestionEvaluator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PokemonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Pokemon::query()->orderBy('pokeapi_id');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($builder) use ($search): void {
                if (is_numeric($search)) {
                    $builder->orWhere('pokeapi_id', (int) $search);
                }

                $normalized = Str::lower($search);
                $parts = preg_split('/\s+/', $normalized) ?: [];
                foreach ($parts as $part) {
                    $token = trim($part);
                    if ($token === '') {
                        continue;
                    }

                    $builder->where(function ($sub) use ($token): void {
                        $sub
                            ->where('display_name', 'like', "%{$token}%")
                            ->orWhere('slug', 'like', "%{$token}%");
                    });
                }
            });
        }

        if ($generation = $request->query('generation')) {
            $query->where('generation', (int) $generation);
        }

        if ($type = $request->query('type')) {
            $query->where(function ($builder) use ($type): void {
                $builder->where('primary_type', $type)->orWhere('secondary_type', $type);
            });
        }

        $limit = min(max((int) $request->query('limit', 50), 1), 200);
        $offset = max((int) $request->query('offset', 0), 0);

        return response()->json([
            'data' => $query->offset($offset)->limit($limit)->get(),
            'total_loaded' => Pokemon::query()->count(),
        ]);
    }

    public function show(Pokemon $pokemon): JsonResponse
    {
        return response()->json(['data' => $pokemon]);
    }

    public function sync(Request $request, PokeApiService $pokeApiService): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:1025'],
            'offset' => ['nullable', 'integer', 'min:0', 'max:2000'],
        ]);

        $summary = $pokeApiService->sync(
            $validated['limit'] ?? 151,
            $validated['offset'] ?? 0,
        );

        return response()->json([
            'message' => 'Sincronización completada',
            'summary' => $summary,
            'total_loaded' => Pokemon::query()->count(),
        ]);
    }

    public function questions(): JsonResponse
    {
        $language = request()->query('lang', 'es') === 'en' ? 'en' : 'es';

        return response()->json([
            'questions' => QuestionCatalog::all(),
            'type_labels' => QuestionCatalog::typeLabels($language),
        ]);
    }

    public function evaluate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pokemon_id' => ['required', 'exists:pokemons,id'],
            'question_key' => ['required', 'string'],
        ]);

        $pokemon = Pokemon::query()->findOrFail($validated['pokemon_id']);
        $answer = QuestionEvaluator::evaluate($validated['question_key'], $pokemon);

        return response()->json([
            'answer' => $answer ?? 'unknown',
        ]);
    }
}
