<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreCvRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CvController extends Controller
{
    private const DISK = 'local';

    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Cv', [
            'cv' => $this->currentCv($request),
        ]);
    }

    public function update(StoreCvRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->cv_path !== null) {
            Storage::disk(self::DISK)->delete($user->cv_path);
        }

        $file = $request->file('cv');

        $user->forceFill([
            'cv_path' => $file->store('cv', self::DISK),
            'cv_original_name' => $file->getClientOriginalName(),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('CV uploaded.')]);

        return to_route('cv.edit');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->cv_path !== null) {
            Storage::disk(self::DISK)->delete($user->cv_path);
        }

        $user->forceFill(['cv_path' => null, 'cv_original_name' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('CV removed.')]);

        return to_route('cv.edit');
    }

    public function download(Request $request): StreamedResponse
    {
        $user = $request->user();

        abort_if($user->cv_path === null || ! Storage::disk(self::DISK)->exists($user->cv_path), 404);

        return Storage::disk(self::DISK)->download($user->cv_path, $user->cv_original_name);
    }

    /**
     * @return array{name: string, size: int, uploaded_at: string}|null
     */
    private function currentCv(Request $request): ?array
    {
        $user = $request->user();

        if ($user->cv_path === null || ! Storage::disk(self::DISK)->exists($user->cv_path)) {
            return null;
        }

        return [
            'name' => $user->cv_original_name ?? 'cv.pdf',
            'size' => Storage::disk(self::DISK)->size($user->cv_path),
            'uploaded_at' => Carbon::createFromTimestamp(Storage::disk(self::DISK)->lastModified($user->cv_path))->toIso8601String(),
        ];
    }
}
