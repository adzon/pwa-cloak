<?php

namespace App\Services;

use App\Filament\Resources\PixelResource\Enum\ChannelEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 像素验证服务
 * 用于验证不同广告渠道的 Access Token 是否有效
 */
class PixelValidationService
{
    /**
     * 验证 Facebook Pixel Access Token
     * 使用 Graph API v21.0 (推荐使用最新的稳定版本)
     * 
     * @param string $pixelId
     * @param string $accessToken
     * @return array ['valid' => bool, 'message' => string, 'data' => array]
     */
    public function validateFacebookPixel(string $pixelId, string $accessToken): array
    {
        try {
            // 使用 Facebook Graph API 验证 Pixel
            // 文档: https://developers.facebook.com/docs/marketing-api/conversions-api/
            // v21.0 是当前推荐版本，每个版本支持约2年
            $url = "https://graph.facebook.com/v21.0/{$pixelId}";

            $response = Http::timeout(15)
                ->retry(2, 100) // 添加重试机制
                ->get($url, [
                    'access_token' => $accessToken,
                    'fields' => 'id,name,is_unavailable,owner_ad_account,owner_business'
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // 检查是否有错误
                if (isset($data['error'])) {
                    $errorCode = $data['error']['code'] ?? 0;
                    $errorMessage = $data['error']['message'] ?? 'Unknown error';
                    
                    // 特殊错误码处理
                    $errorType = match ($errorCode) {
                        190 => '访问令牌已过期或无效',
                        100 => '参数无效',
                        200 => '权限不足',
                        default => $errorMessage
                    };

                    return [
                        'valid' => false,
                        'message' => "Facebook API 错误 ({$errorCode}): {$errorType}",
                        'data' => $data,
                        'error_code' => $errorCode
                    ];
                }

                // 检查像素是否可用
                if (isset($data['is_unavailable']) && $data['is_unavailable']) {
                    return [
                        'valid' => false,
                        'message' => 'Facebook 像素不可用或已被禁用',
                        'data' => $data
                    ];
                }

                // 验证成功
                return [
                    'valid' => true,
                    'message' => 'Facebook 像素验证成功',
                    'data' => $data,
                    'pixel_name' => $data['name'] ?? 'Unknown'
                ];
            }

            // 处理 HTTP 错误响应
            $statusCode = $response->status();
            $error = $response->json() ?? [];
            $errorMessage = $error['error']['message'] ?? $response->body();

            return [
                'valid' => false,
                'message' => "Facebook API 请求失败 (HTTP {$statusCode}): {$errorMessage}",
                'data' => $error,
                'http_status' => $statusCode
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Facebook Pixel 连接失败', [
                'pixel_id' => $pixelId,
                'error' => $e->getMessage()
            ]);

            return [
                'valid' => false,
                'message' => "连接 Facebook API 失败: {$e->getMessage()}",
                'data' => []
            ];
        } catch (\Exception $e) {
            Log::error('Facebook Pixel 验证失败', [
                'pixel_id' => $pixelId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'valid' => false,
                'message' => "验证异常: {$e->getMessage()}",
                'data' => []
            ];
        }
    }

    /**
     * 验证 TikTok Pixel Access Token
     * 使用 TikTok Events API v1.3 (当前最新版本)
     *
     * @param string $pixelId
     * @param string $accessToken
     * @return array ['valid' => bool, 'message' => string, 'data' => array]
     */
    public function validateTikTokPixel(string $pixelId, string $accessToken): array
    {
        try {
            // 使用 TikTok Events API 验证
            // 文档: https://business-api.tiktok.com/portal/docs?id=1739584855420929
            $url = "https://business-api.tiktok.com/open_api/v1.3/pixel/info/";

            $response = Http::timeout(15)
                ->retry(2, 100) // 添加重试机制
                ->withHeaders([
                    'Access-Token' => $accessToken,
                    'Content-Type' => 'application/json'
                ])
                ->get($url, [
                    'pixel_code' => $pixelId,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // TikTok API 返回格式: {"code": 0, "message": "OK", "data": {...}}
                // code: 0 表示成功，非0表示错误
                if (isset($data['code'])) {
                    $code = $data['code'];
                    
                    if ($code === 0 || $code === '0') {
                        $pixelData = $data['data'] ?? [];
                        
                        return [
                            'valid' => true,
                            'message' => 'TikTok 像素验证成功',
                            'data' => $pixelData,
                            'pixel_name' => $pixelData['pixel_name'] ?? 'Unknown'
                        ];
                    }
                    
                    // 处理特定错误码
                    $errorMessage = match ($code) {
                        40100, 40101 => 'Access Token 无效或已过期',
                        40102 => '权限不足',
                        40104 => 'Pixel ID 不存在或无权访问',
                        40002 => '参数错误',
                        50000, 50001 => 'TikTok 服务器内部错误',
                        60001 => 'TikTok 服务限流',
                        default => $data['message'] ?? 'TikTok API 返回错误'
                    };

                    return [
                        'valid' => false,
                        'message' => "TikTok API 错误 ({$code}): {$errorMessage}",
                        'data' => $data,
                        'error_code' => $code
                    ];
                }

                // 无法识别的响应格式
                return [
                    'valid' => false,
                    'message' => 'TikTok API 返回格式异常',
                    'data' => $data
                ];
            }

            // 处理 HTTP 错误响应
            $statusCode = $response->status();
            $error = $response->json() ?? [];

            return [
                'valid' => false,
                'message' => "TikTok API 请求失败 (HTTP {$statusCode})",
                'data' => $error,
                'http_status' => $statusCode
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('TikTok Pixel 连接失败', [
                'pixel_id' => $pixelId,
                'error' => $e->getMessage()
            ]);

            return [
                'valid' => false,
                'message' => "连接 TikTok API 失败: {$e->getMessage()}",
                'data' => []
            ];
        } catch (\Exception $e) {
            Log::error('TikTok Pixel 验证失败', [
                'pixel_id' => $pixelId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'valid' => false,
                'message' => "验证异常: {$e->getMessage()}",
                'data' => []
            ];
        }
    }

    /**
     * 验证 Google Ads Conversion Tracking
     * 使用 Google Ads API v18 (当前最新版本)
     *
     * @param string $conversionId 格式: customers/{customer_id}
     * @param string $accessToken OAuth2 访问令牌
     * @return array ['valid' => bool, 'message' => string, 'data' => array]
     */
    public function validateGoogleConversion(string $conversionId, string $accessToken): array
    {
        try {
            // 使用 Google Ads API 验证
            // 文档: https://developers.google.com/google-ads/api/rest/overview
            
            // 注意: Google Ads API 需要更复杂的认证流程
            // 需要配置 OAuth2 客户端和 Developer Token
            // 这里使用简化的验证方法，实际生产环境建议使用官方 SDK

            // 提取客户ID
            $customerId = $this->extractGoogleCustomerId($conversionId);
            
            if (!$customerId) {
                return [
                    'valid' => false,
                    'message' => 'Google Customer ID 格式无效',
                    'data' => []
                ];
            }

            // 使用 Google Ads API v18 查询客户信息
            $url = "https://googleads.googleapis.com/v18/customers/{$customerId}:searchStream";

            // 查询客户基本信息和转化操作
            $query = "SELECT customer.id, customer.descriptive_name, customer.currency_code FROM customer LIMIT 1";

            $response = Http::timeout(15)
                ->retry(2, 100)
                ->withHeaders([
                    'Authorization' => "Bearer {$accessToken}",
                    'developer-token' => config('services.google.developer_token', ''),
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'query' => $query
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // 检查是否返回了客户数据
                if (isset($data['results']) && count($data['results']) > 0) {
                    $customerData = $data['results'][0]['customer'] ?? [];
                    
                    return [
                        'valid' => true,
                        'message' => 'Google Ads 账户验证成功',
                        'data' => $customerData,
                        'customer_id' => $customerData['id'] ?? $customerId,
                        'customer_name' => $customerData['descriptiveName'] ?? 'Unknown'
                    ];
                }

                // API 返回成功但无数据
                return [
                    'valid' => false,
                    'message' => 'Google Ads 账户无数据或无访问权限',
                    'data' => $data
                ];
            }

            // 处理 HTTP 错误响应
            $statusCode = $response->status();
            $error = $response->json() ?? [];
            
            // Google Ads API 错误格式处理
            $errorMessage = 'Unknown error';
            $errorCode = null;
            
            if (isset($error['error'])) {
                $errorMessage = $error['error']['message'] ?? $errorMessage;
                $errorCode = $error['error']['code'] ?? null;
                
                // 处理特定错误
                $errorType = match ($errorCode) {
                    401 => 'Access Token 无效或已过期',
                    403 => '权限不足或 Developer Token 无效',
                    404 => '客户账户不存在',
                    429 => 'API 请求限流',
                    default => $errorMessage
                };
                
                return [
                    'valid' => false,
                    'message' => "Google Ads API 错误 ({$errorCode}): {$errorType}",
                    'data' => $error,
                    'error_code' => $errorCode,
                    'http_status' => $statusCode
                ];
            }

            return [
                'valid' => false,
                'message' => "Google API 请求失败 (HTTP {$statusCode}): {$errorMessage}",
                'data' => $error,
                'http_status' => $statusCode
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Google Ads 连接失败', [
                'conversion_id' => $conversionId,
                'error' => $e->getMessage()
            ]);

            return [
                'valid' => false,
                'message' => "连接 Google Ads API 失败: {$e->getMessage()}",
                'data' => []
            ];
        } catch (\Exception $e) {
            Log::error('Google Conversion 验证失败', [
                'conversion_id' => $conversionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'valid' => false,
                'message' => "验证异常: {$e->getMessage()}",
                'data' => []
            ];
        }
    }

    /**
     * 从 Google Conversion ID 中提取 Customer ID
     *
     * @param string $conversionId
     * @return string|null
     */
    private function extractGoogleCustomerId(string $conversionId): ?string
    {
        // 支持多种格式:
        // 1. customers/1234567890
        // 2. 1234567890
        // 3. 123-456-7890 (带连字符)
        
        // 移除 "customers/" 前缀
        $customerId = str_replace('customers/', '', $conversionId);
        
        // 移除连字符
        $customerId = str_replace('-', '', $customerId);
        
        // 验证是否为数字
        if (preg_match('/^\d{10}$/', $customerId)) {
            return $customerId;
        }
        
        return null;
    }

    /**
     * 根据渠道验证像素
     *
     * @param int $channel
     * @param string $pixelId
     * @param string $accessToken
     * @return array
     */
    public function validatePixel(int $channel, string $pixelId, string $accessToken): array
    {
        return match ($channel) {
            ChannelEnum::FACEBOOK_ID => $this->validateFacebookPixel($pixelId, $accessToken),
            ChannelEnum::TIKTOK_ID => $this->validateTikTokPixel($pixelId, $accessToken),
            ChannelEnum::GOOGLE_ID => $this->validateGoogleConversion($pixelId, $accessToken),
            default => [
                'valid' => false,
                'message' => '不支持的渠道类型',
                'data' => []
            ]
        };
    }

    /**
     * 批量验证像素
     *
     * @param array $pixels 像素数组，每个元素包含 channel, pixel_id, access_token
     * @return array 验证结果数组
     */
    public function validatePixelsBatch(array $pixels): array
    {
        $results = [];

        foreach ($pixels as $pixel) {
            $result = $this->validatePixel(
                $pixel['channel'],
                $pixel['pixel_id'],
                $pixel['access_token']
            );

            $results[] = [
                'pixel_id' => $pixel['pixel_id'],
                'channel' => $pixel['channel'],
                'result' => $result
            ];

            // 避免请求过快，添加延迟
            usleep(300000); // 0.3秒
        }

        return $results;
    }
}

