<?php

namespace App\Filament\Resources\ApplicationResource\Pages;

use App\Filament\Resources\ApplicationResource;
use App\Models\Application;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function afterSave(): void
    {
        Notification::make()
            ->success()
            ->title('保存成功')
            ->send();
    }

    protected function handleRecordCreation(array $data): Application
    {
        $application = Application::create($data);

        // 保存语言关系
        if (!empty($data['languages'])) {
            $application->languages()->sync($data['languages']);
        }

        // 保存多语言本地化数据
        if (!empty($data['localeApplications'])) {
            foreach ($data['localeApplications'] as $languageId => $localeData) {
                $application->localeApplications()->create([
                    'language_id' => $languageId,
                    'name' => $localeData['name'] ?? '',
                    'manufacturer' => $localeData['manufacturer'] ?? '',
                    'icon' => $localeData['icon'] ?? '',
                    'downloads' => $localeData['downloads'] ?? '',
                    'age_limit' => $localeData['age_limit'] ?? 0,
                    'comment_count' => $localeData['comment_count'] ?? 0,
                    'introduction' => $localeData['introduction'] ?? '',
                    'images' => isset($localeData['images']) ? json_encode($localeData['images']) : null,
                    'label' => $localeData['label'] ?? '',
                ]);

                // 保存评论
                if (!empty($localeData['comments'])) {
                    foreach ($localeData['comments'] as $comment) {
                        $newComment = \App\Models\Comment::create([
                            'user_id' => Auth::id(),
                            'language_id' => $comment['language_id'] ?? $languageId,
                            'nickname' => $comment['nickname'],
                            'content' => $comment['content'],
                        ]);
                        $application->comments()->attach($newComment->id);
                    }
                }
            }
        }

        return $application;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        \Log::debug('mutateFormDataBeforeFill called with data:', $data);
        if (isset($data['id'])) {
            $record = Application::with(['languages', 'localeApplications.comments'])->find($data['id']);
            \Log::debug($record);
            if ($record) {
                // 设置语言选择
                $data['languages'] = $record->languages->pluck('id')->toArray();

                // 准备 localeApplications 数据
                $localeApplications = [];
                foreach ($record->localeApplications as $localeApp) {
                    $languageId = $localeApp->language_id;

                    // 处理图片数据
                    $images = [];
                    if ($localeApp->images) {
                        $images = is_string($localeApp->images) ?
                            json_decode($localeApp->images, true) :
                            $localeApp->images;

                        // 确保是索引数组而不是关联数组
                        if (is_array($images) && !array_is_list($images)) {
                            $images = array_values($images);
                        }
                    }


                    // 处理评论数据（用于 Repeater）
                    $commentRecords = $localeApp->comments; // 这是 comments 表的数据集合
                    $reviews = [];
                    foreach ($commentRecords as $comment) {
                        $reviews[] = [
                            'id' => $comment->id,
                            'nickname' => $comment->nickname,
                            'content' => $comment->content,
                            'language_id' => $comment->language_id,
                        ];
                    }

                    \Log::debug('localeApplications', $reviews);
                    $localeApplications[$languageId] = [
                        'id' => $localeApp->id,
                        'name' => $localeApp->name,
                        'manufacturer' => $localeApp->manufacturer,
                        'icon' => $localeApp->icon,
                        'downloads' => $localeApp->downloads,
                        'age_limit' => $localeApp->age_limit,
                        'comment_count' => $localeApp->comment_count, // 这是属性字段
                        'introduction' => $localeApp->introduction,
                        'images' => $images,
                        'label' => $localeApp->label ?? '',
                        'reviews' => $reviews, // 用于 Repeater 组件
                    ];
                }

                $data['localeApplications'] = $localeApplications;
            }
        }
        \Log::debug('data', $data);
        return $data;
    }

}
