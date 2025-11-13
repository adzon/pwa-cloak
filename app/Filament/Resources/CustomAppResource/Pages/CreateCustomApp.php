<?php

namespace App\Filament\Resources\CustomAppResource\Pages;

use App\Filament\Resources\CustomAppResource;
use App\Filament\Resources\CustomAppResource\Enum\ButtonPositionEnum;
use App\Models\Application;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class CreateCustomApp extends CreateRecord
{
    protected static string $resource = CustomAppResource::class;

    public function getHeading(): string|Htmlable
    {
        return '应用页面设计';
    }

    public function mount(): void
    {
        parent::mount();
        
        // 检查是否有复制参数
        $copyFromId = request()->query('copy_from');
        
        if ($copyFromId) {
            $sourceApp = Application::with(['languages', 'localeApplications'])->find($copyFromId);
            
            if ($sourceApp) {
                // 准备表单数据
                $formData = [];
                
                // 复制基本信息（排除 id, user_id, created_at, updated_at）
                $formData = array_merge($formData, $sourceApp->only([
                    'name',
                    'remark',
                    'google_show',
                    'official_verified',
                    'icon',
                    'background_color',
                    'theme_color',
                    'category',
                    'display_mode',
                    'orientation',
                    'apk_upload_enabled',
                    'apk',
                    'ercode_show',
                    'package_priority',
                    'ios_guide',
                    'w2a_auto_down',
                    'is_iframe',
                    'complaint',
                    'complaint_config',
                ]));
                
                // 设置应用类型为自定义应用
                $formData['app_type'] = Application::APP_TYPE_CUSTOM;
                
                // 复制语言关系
                $formData['languages'] = $sourceApp->languages->pluck('id')->toArray();
                
                // 复制本地化数据
                $localeApplications = [];
                foreach ($sourceApp->localeApplications as $localeApp) {
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
                    
                    $localeApplications[$languageId] = [
                        'name' => $localeApp->name,
                        'manufacturer' => $localeApp->manufacturer,
                        'icon' => $localeApp->icon,
                        'downloads' => $localeApp->downloads,
                        'age_limit' => $localeApp->age_limit,
                        'comment_count' => $localeApp->comment_count,
                        'introduction' => $localeApp->introduction,
                        'images' => $images,
                        'install_button' => $localeApp->install_button ?? true,
                        'install_button_text' => $localeApp->install_button_text ?? '',
                        'install_button_color' => $localeApp->install_button_color ?? '',
                        'install_button_position' => $localeApp->install_button_position ?? ButtonPositionEnum::BOTTOM,
                    ];
                }
                
                $formData['localeApplications'] = $localeApplications;
                
                // 填充表单
                $this->form->fill($formData);
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 设置当前用户ID
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

                $application->localeApplications()->create([
                    'language_id' => $languageId,
                    'name' => $localeData['name'] ?? '',
                    'manufacturer' => $localeData['manufacturer'] ?? '',
                    'icon' => $localeData['icon'] ?? '',
                    'downloads' => $localeData['downloads'] ?? '',
                    'age_limit' => $localeData['age_limit'] ?? 0,
                    'comment_count' => $localeData['comment_count'] ?? 0,
                    'introduction' => $localeData['introduction'] ?? '',
                    'images' => $images,
                    'install_button' => $localeData['install_button'] ?? true,
                    'install_button_text' => $localeData['install_button_text'] ?? '',
                    'install_button_color' => $localeData['install_button_color'] ?? '',
                    'install_button_position' => $localeData['install_button_position'] ?? ButtonPositionEnum::BOTTOM,
                ]);
            }
        }

        return $application;
    }
}
