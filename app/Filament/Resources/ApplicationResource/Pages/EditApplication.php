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

    protected function handleRecordUpdate($record, array $data): Application
    {
        $record->update($data);

        // 保存语言关系
        if (isset($data['languages'])) {
            $record->languages()->sync($data['languages']);
        }

        // 保存多语言本地化数据
        if (!empty($data['localeApplications'])) {
            foreach ($data['localeApplications'] as $languageId => $localeData) {
                $localeApp = $record->localeApplications()->updateOrCreate(
                    ['language_id' => $languageId],
                    [
                        'name' => $localeData['name'] ?? '',
                        'manufacturer' => $localeData['manufacturer'] ?? '',
                        'icon' => $localeData['icon'] ?? '',
                        'downloads' => $localeData['downloads'] ?? '',
                        'age_limit' => $localeData['age_limit'] ?? 0,
                        'comment_count' => $localeData['comment_count'] ?? 0,
                        'introduction' => $localeData['introduction'] ?? '',
                        'images' => isset($localeData['images']) ? json_encode($localeData['images']) : null,
                        'label' => $localeData['label'] ?? [],
                    ]
                );

                if (!empty($localeData['reviews'])) {
                    // 先清除旧的评论关联（避免重复）
                    $localeApp->comments()->detach();

                    foreach ($localeData['reviews'] as $review) {
                        // 如果评论已经有 ID，说明是已存在的评论，直接关联
                        if (isset($review['id']) && $review['id']) {
                            $localeApp->comments()->attach($review['id']);
                        } else {
                            // 新评论，创建后关联
                            $newComment = \App\Models\Comment::create([
                                'user_id' => Auth::id(),
                                'language_id' => $review['language_id'] ?? $languageId,
                                'nickname' => $review['nickname'] ?? '匿名用户',
                                'content' => $review['content'] ?? '',
                            ]);
                            $localeApp->comments()->attach($newComment->id);
                        }
                    }
                }
            }
        }

        return $record;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['id'])) {
            $record = Application::with(['languages', 'localeApplications.comments'])->find($data['id']);
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
                        $images = is_string($localeApp->images)
                            ? json_decode($localeApp->images, true)
                            : $localeApp->images;
                        if (is_array($images) && !array_is_list($images)) {
                            $images = array_values($images);
                        }
                    }

                    // 处理评论数据
                    $reviews = [];
                    foreach ($localeApp->comments as $comment) {
                        $reviews[] = [
                            'id' => $comment->id,
                            'nickname' => $comment->nickname,
                            'content' => $comment->content,
                            'language_id' => $comment->language_id,
                        ];
                    }

                    // 处理标签数据：确保转换为字符串数组格式（兼容旧的对象数组格式）
                    $labels = [];
                    if ($localeApp->label && is_array($localeApp->label)) {
                        foreach ($localeApp->label as $label) {
                            if (is_array($label) && isset($label['value'])) {
                                // 旧格式：[{'value': 'movie'}]
                                $labels[] = $label['value'];
                            } elseif (is_string($label)) {
                                // 新格式：['movie']
                                $labels[] = $label;
                            }
                        }
                    }

                    $localeApplications[$languageId] = [
                        'id' => $localeApp->id,
                        'name' => $localeApp->name,
                        'manufacturer' => $localeApp->manufacturer,
                        'icon' => $localeApp->icon,
                        'downloads' => $localeApp->downloads,
                        'age_limit' => $localeApp->age_limit,
                        'comment_count' => $localeApp->comment_count,
                        'introduction' => $localeApp->introduction,
                        'label' => $labels,
                        'images' => $images,
                        'reviews' => $reviews,
                    ];
                }

                $data['localeApplications'] = $localeApplications;
            }
        }

        return $data;
    }
}
