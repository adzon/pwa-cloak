<?php

namespace App\Filament\Resources\ApplicationResource\Pages;

use App\Filament\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;
    
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
        $comment = Comment::where('id', $commentId)
            ->where('user_id', Auth::id())
            ->first();
        
        if ($comment) {
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
        $comment = Comment::where('id', $commentId)
            ->where('user_id', Auth::id())
            ->first();
        
        if ($comment) {
            $comment->delete();
            
            Notification::make()
                ->success()
                ->title('评论已删除')
                ->send();
        }
    }
}
