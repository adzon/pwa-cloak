<?php

namespace App\Filament\Resources\ApplicationResource\Pages;

use App\Filament\Resources\ApplicationResource;
use App\Filament\Resources\CustomAppResource\Enum\ButtonPositionEnum;
use App\Models\Application;
use App\Models\Comment;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;

    public function getHeading(): string|Htmlable
    {
        return '应用页面设计';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // 初始化 localeApplications 数据结构，确保每个语言的 comment_ids 都有默认值
        if (!isset($data['localeApplications'])) {
            $data['localeApplications'] = [];
        }

        // 如果用户已经选择了语言，初始化这些语言的 localeApplications
        if (isset($data['languages']) && is_array($data['languages'])) {
            foreach ($data['languages'] as $languageId) {
                if (!isset($data['localeApplications'][$languageId])) {
                    $data['localeApplications'][$languageId] = [
                        'comment_ids' => []
                    ];
                } elseif (!isset($data['localeApplications'][$languageId]['comment_ids'])) {
                    $data['localeApplications'][$languageId]['comment_ids'] = [];
                }
            }
        }

        return $data;
    }

    protected function beforeCreate(): void
    {
        // 获取表单数据
        $data = $this->form->getState();

        // 验证评论数量
        if (isset($data['localeApplications'])) {
            $errors = [];
            foreach ($data['localeApplications'] as $languageId => $localeData) {
                $commentIds = $localeData['comment_ids'] ?? [];

                if (is_string($commentIds)) {
                    $commentIds = json_decode($commentIds, true) ?? [];
                }

                if (!is_array($commentIds) || count($commentIds) < 2) {
                    $errors["data.localeApplications.{$languageId}.comment_ids"] = '请至少选择两条评论';
                }
            }

            if (!empty($errors)) {
                throw ValidationException::withMessages($errors);
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        // 手动验证评论数量
        if (isset($data['localeApplications'])) {
            $errors = [];
            foreach ($data['localeApplications'] as $languageId => $localeData) {
                $commentIds = $localeData['comment_ids'] ?? [];

                // 确保是数组
                if (is_string($commentIds)) {
                    $commentIds = json_decode($commentIds, true) ?? [];
                }

                // 验证至少选择2条评论
                if (!is_array($commentIds) || count($commentIds) < 2) {
                    $errors["data.localeApplications.{$languageId}.comment_ids"] = '请至少选择两条评论';
                }
            }

            if (!empty($errors)) {
                throw ValidationException::withMessages($errors);
            }
        }

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
                    'language_id' => $languageId,
                    'name' => $localeData['name'] ?? '',
                    'manufacturer' => $localeData['manufacturer'] ?? '',
                    'icon' => $localeData['icon'] ?? '',
                    'downloads' => $localeData['downloads'] ?? '',
                    'age_limit' => $localeData['age_limit'] ?? 0,
                    'comment_count' => $localeData['comment_count'] ?? 0,
                    'introduction' => $localeData['introduction'] ?? '',
                    'images' => $images,
                    'label' => $localeData['label'] ?? [],
                    'install_button' => $localeData['install_button'] ?? true,
                    'install_button_text' => $localeData['install_button_text'] ?? '',
                    'install_button_color' => $localeData['install_button_color'] ?? '',
                    'install_button_position' => $localeData['install_button_position'] ?? ButtonPositionEnum::BOTTOM,
                ]);

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
                            $localeApp->comments()->attach($commentIds);
                        }
                    }
                }
            }
        }

        return $application;
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
        if (!isSuperAdmin()) {
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
