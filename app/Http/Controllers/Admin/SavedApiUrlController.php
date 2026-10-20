<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RevealSavedApiUrlRequest;
use App\Http\Requests\Admin\UpdateSavedApiUrlRequest;
use App\Models\User;
use App\Services\SavedApiUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class SavedApiUrlController extends Controller
{
    public function update(UpdateSavedApiUrlRequest $request, SavedApiUrlService $service, string $provider): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $redirect = redirect()->route('data-master.index', ['tab' => $provider])->header('Cache-Control', 'no-store');

        try {
            $service->save($provider, $request->apiUrl(), $actor);
        } catch (ValidationException $exception) {
            return $redirect->withErrors($exception->errors(), 'api_connections_'.$provider);
        }

        return $redirect->with('success', 'Link API berhasil disimpan.');
    }

    public function reveal(RevealSavedApiUrlRequest $request, SavedApiUrlService $service, string $provider): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        try {
            $url = $service->get($provider, $actor);
        } catch (ValidationException $exception) {
            return response()->json(['errors' => $exception->errors()], 422)->header('Cache-Control', 'no-store, private');
        }

        return response()->json(['api_url' => $url])->header('Cache-Control', 'no-store, private');
    }
}
