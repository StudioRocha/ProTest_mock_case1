<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Profile;
use App\Http\Requests\ProfileRequest;

class ProfileController extends Controller
{
    public function edit()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $profile = $user->profile;
        
        // プロフィールが存在しない場合は、ユーザー名を初期値として設定
        if (!$profile) {
            $profile = new Profile();
            $profile->usernames = $user->name;
        } else {
            // プロフィールが存在する場合も、ユーザー名が空の場合は初期値を設定
            if (empty($profile->usernames)) {
                $profile->usernames = $user->name;
            }
        }
        
        return view('mypage.profile', compact('user', 'profile'));
    }

    public function update(ProfileRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();

        $payload = [
            'user_id' => $user->id,
            'usernames' => $data['username'],
            'postal_codes' => $data['postal_code'],
            'addresses' => $data['address'],
            'building_names' => $data['building_name'] ?? null,
        ];

        if ($request->hasFile('avatar')) {
            // avatarsディレクトリが存在しない場合は作成し、777権限を付与
            $avatarsPath = storage_path('app/public/avatars');
            if (!file_exists($avatarsPath)) {
                mkdir($avatarsPath, 0777, true);
                chmod($avatarsPath, 0777);
            }
            
            $path = $request->file('avatar')->store('avatars', 'public');
            $payload['avatar_paths'] = $path;
        }

        Profile::updateOrCreate(['user_id' => $user->id], $payload);

        return redirect('/')->with('success', 'プロフィールを更新しました');
    }
}
