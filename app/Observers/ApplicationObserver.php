<?php

namespace App\Observers;

use App\Models\Application;
use App\Models\LocaleApplication;
use App\Models\LocaleApplicationComment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ApplicationObserver
{
    /**
     * Handle the Application "created" event.
     */
    public function created(Application $application): void
    {
        $this->handleLocaleApplications($application);
    }

    /**
     * Handle the Application "updated" event.
     */
    public function updated(Application $application): void
    {
        $this->handleLocaleApplications($application);
    }

    /**
     * 处理多语言应用的保存和评论关联
     */
    protected function handleLocaleApplications(Application $application): void
    {
        // 获取请求数据
        $request = request();
        
        // 检查是否有localeApplications数据
        $localeApplicationsData = $request->input('localeApplications', []);
        
        if (empty($localeApplicationsData)) {
            // 如果没有直接的localeApplications，尝试从data中获取
            $localeApplicationsData = $request->input('data.localeApplications', []);
            if (empty($localeApplicationsData)) {
                return;
            }
        }
        
        // 遍历所有语言的应用数据
        foreach ($localeApplicationsData as $languageId => $data) {
            // 获取已存在的LocaleApplication
            $localeApplication = LocaleApplication::where('app_id', $application->id)
                ->where('language_id', $languageId)
                ->first();
            
            if (!$localeApplication) {
                continue; // 如果LocaleApplication不存在，跳过
            }
            
            // 处理选中的评论关联
            $selectedComments = Arr::get($data, 'selected_comments', []);
            
            if (is_array($selectedComments)) {
                // 清除现有的关联
                LocaleApplicationComment::where('locale_application_id', $localeApplication->id)->delete();
                
                // 创建新的关联
                foreach ($selectedComments as $commentId) {
                    LocaleApplicationComment::create([
                        'locale_application_id' => $localeApplication->id,
                        'comment_id' => $commentId,
                    ]);
                }
            }
        }
    }
}