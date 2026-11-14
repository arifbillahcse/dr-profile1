<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Models\DoctorProfile;
use App\Models\MediaAsset;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', [
            'profile' => DoctorProfile::firstOrCreate(['key' => 'primary'], ['name' => config('app.name'), 'credentials' => []]),
            'site' => SiteSetting::firstOrCreate(['key' => 'primary'], ['site_title' => config('app.name'), 'social_links' => []]),
            'mediaOptions' => MediaAsset::where('disk', 'public')->latest('id')->get(),
        ]);
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $request->validated();
            $profile = $data['profile'];
            $site = $data['site'];
            if (array_key_exists('credentials_text', $profile)) {
                $profile['credentials'] = $this->lines($profile['credentials_text']);
                unset($profile['credentials_text']);
            }
            if (array_key_exists('additional_phones_text', $site)) {
                $site['additional_phones'] = $this->lines($site['additional_phones_text']);
                unset($site['additional_phones_text']);
            }
            DoctorProfile::updateOrCreate(['key' => 'primary'], $profile);
            SiteSetting::updateOrCreate(['key' => 'primary'], $site);
        });

        return to_route('admin.settings.edit')->with('status', 'Website settings saved.');
    }

    /** @return list<string> */
    private function lines(?string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\\r\\n|\\r|\\n/', $value ?? '')), fn (string $line): bool => $line !== ''));
    }
}
