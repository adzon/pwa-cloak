<?php

namespace App\Filament\Resources\CustomAppResource\Pages;

use App\Filament\Resources\CustomAppResource;
use App\Filament\Resources\CustomAppResource\Enum\ButtonPositionEnum;
use App\Models\Application;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditCustomApp extends EditRecord
{
    protected static string $resource = CustomAppResource::class;

    public function getHeading(): string|Htmlable
    {
        return '应用页面设计';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['id'])) {
            $record = Application::with(['languages', 'localeApplications'])->find($data['id']);
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

                    $localeApplications[$languageId] = [
                        'id' => $localeApp->id,
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

                $data['localeApplications'] = $localeApplications;
            }
        }

        return $data;
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
                $images = isset($localeData['images'])
                    ? (is_array($localeData['images'])
                        ? json_encode($localeData['images'])
                        : $localeData['images'])
                    : null;

                $record->localeApplications()->updateOrCreate(
                    ['language_id' => $languageId],
                    [
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
                    ]
                );
            }
        }

        return $record;
    }
}
