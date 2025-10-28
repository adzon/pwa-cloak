<?php

namespace App\Filament\Resources\ApplicationResource\Pages;

use App\Filament\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        return $data;
    }

    protected function handleRecordCreation(array $data): Application
    {
        $application = Application::create($data);

        // 同步语言关系
        if (!empty($data['languages'])) {
            $application->languages()->sync($data['languages']);
        }

        // 保存每种语言的详情
        if (!empty($data['localeApplications'])) {
            foreach ($data['localeApplications'] as $languageId => $localeData) {
                $images = isset($localeData['images'])
                    ? (is_array($localeData['images'])
                        ? json_encode($localeData['images'])
                        : $localeData['images'])
                    : null;

                $localeApp = $application->localeApplications()->create([
                    'language_id'   => $languageId,
                    'name'          => $localeData['name'] ?? '',
                    'manufacturer'  => $localeData['manufacturer'] ?? '',
                    'icon'          => $localeData['icon'] ?? '',
                    'downloads'     => $localeData['downloads'] ?? '',
                    'age_limit'     => $localeData['age_limit'] ?? 0,
                    'comment_count' => $localeData['comment_count'] ?? 0,
                    'introduction'  => $localeData['introduction'] ?? '',
                    'images'        => $images,
                    'label'         => $localeData['label'] ?? [],
                ]);

                // 保存评论
                if (!empty($localeData['reviews'])) {
                    foreach ($localeData['reviews'] as $review) {
                        $comment = Comment::create([
                            'user_id'     => Auth::id(),
                            'language_id' => $review['language_id'] ?? $languageId,
                            'nickname'    => $review['nickname'] ?? '匿名用户',
                            'content'     => $review['content'] ?? '',
                        ]);
                        $localeApp->comments()->attach($comment->id);
                    }
                }
            }
        }

        return $application;
    }
}
