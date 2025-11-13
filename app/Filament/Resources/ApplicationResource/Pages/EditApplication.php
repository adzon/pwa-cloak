<?php

namespace App\Filament\Resources\ApplicationResource\Pages;

use App\Filament\Resources\ApplicationResource;
use App\Filament\Traits\ProtectsUserOwnership;
use App\Models\Application;
use App\Models\Comment;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditApplication extends EditRecord
{
    use ProtectsUserOwnership;

    protected static string $resource = ApplicationResource::class;

    public function getHeading(): string|Htmlable
    {
        return '应用页面设计';
    }

    protected function onValidationError(ValidationException $exception): void
    {
        parent::onValidationError($exception);

        // 检查是否有评论相关的验证错误
        $errors = $exception->errors();
        $commentErrors = [];

        foreach ($errors as $field => $messages) {
            if (strpos($field, 'comment_ids') !== false && is_array($messages)) {
                array_push($commentErrors, ...$messages);
            }
        }

        // 如果有评论验证错误，显示明显的通知
        if (!empty($commentErrors)) {
            Notification::make()
                ->danger()
                ->title('验证失败')
                ->body(implode("\n", $commentErrors))
                ->persistent()
                ->send();
        }
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->success()
            ->title('保存成功')
            ->send();
    }

    protected function handleRecordUpdate($record, array $data): Application
    {
        // 确保不会修改 user_id（保护原始创建者）
        unset($data['user_id']);

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

                // 处理评论绑定
                if (isset($localeData['comment_ids']) && !empty($localeData['comment_ids'])) {
                    $commentIds = $localeData['comment_ids'];

                    // 确保是数组格式
                    if (is_string($commentIds)) {
                        $commentIds = json_decode($commentIds, true);
                    }

                    // 过滤空值并转换为整数
                    if (is_array($commentIds)) {
                        $commentIds = array_filter(array_map('intval', $commentIds));

                        if (!empty($commentIds)) {
                            $localeApp->comments()->sync($commentIds);
                        } else {
                            $localeApp->comments()->detach();
                        }
                    } else {
                        $localeApp->comments()->detach();
                    }
                } else {
                    $localeApp->comments()->detach();
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

                    // 处理评论数据 - 获取已绑定的评论ID
                    $commentIds = $localeApp->comments->pluck('id')->toArray();

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
                        'comment_ids' => $commentIds,
                    ];
                }

                $data['localeApplications'] = $localeApplications;
            }
        }

        return $data;
    }

    /**
     * 创建新评论
     */
    public function createComment(array $data)
    {
        $comment = Comment::create([
            'user_id' => Auth::id(),
            'nickname' => $data['nickname'],
            'content' => $data['content'],
            'language_id' => $data['language_id'],
        ]);

        Notification::make()
            ->success()
            ->title('评论创建成功')
            ->send();

        return $comment->id;
    }

    /**
     * 更新评论
     */
    public function updateComment(int $commentId, array $data)
    {
        $query = Comment::where('id', $commentId);

        // 超级管理员可以编辑所有评论，普通用户只能编辑自己的
        if (!\isSuperAdmin()) {
            $query->where('user_id', Auth::id());
        }

        $comment = $query->first();

        if ($comment) {
            // 保护原始创建者
            unset($data['user_id']);

            $comment->update([
                'nickname' => $data['nickname'],
                'content' => $data['content'],
                'language_id' => $data['language_id'],
            ]);

            Notification::make()
                ->success()
                ->title('评论已更新')
                ->send();
        }
    }

    /**
     * 删除评论
     */
    public function deleteComment(int $commentId)
    {
        $query = Comment::where('id', $commentId);

        // 超级管理员可以删除所有评论，普通用户只能删除自己的
        if (!\isSuperAdmin()) {
            $query->where('user_id', Auth::id());
        }

        $comment = $query->first();

        if ($comment) {
            $comment->delete();

            Notification::make()
                ->success()
                ->title('评论已删除')
                ->send();
        }
    }
}
