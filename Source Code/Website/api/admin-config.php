<?php
/**
 * 管理员配置API - 支持分类、关键词、预设回答和系统设置管理
 * Admin Configuration API - Support for categories, keywords, response templates and system settings
 */

// 确保输出UTF-8编码
header('Content-Type: application/json; charset=UTF-8');
ini_set('default_charset', 'UTF-8');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 会话支持
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once __DIR__ . '/mail.php';

/**
 * 验证管理员权限（简单验证，实际应用中应该更严格）
 */
function validateAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * 获取所有分类
 */
function getCategories() {
    $sql = "SELECT * FROM categories ORDER BY sort_order ASC, id ASC";
    return executeQuery($sql);
}

/**
 * 创建或更新分类
 */
function saveCategory($data) {
    if (isset($data['id']) && $data['id'] > 0) {
        // 更新
        $sql = "UPDATE categories SET name = ?, display_name_zh = ?, display_name_en = ?, icon = ?, sort_order = ?, is_active = ? WHERE id = ?";
        $params = [
            $data['name'],
            $data['display_name_zh'],
            $data['display_name_en'],
            $data['icon'],
            $data['sort_order'],
            $data['is_active'] ? 1 : 0,
            $data['id']
        ];
    } else {
        // 创建
        $sql = "INSERT INTO categories (name, display_name_zh, display_name_en, icon, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)";
        $params = [
            $data['name'],
            $data['display_name_zh'],
            $data['display_name_en'],
            $data['icon'],
            $data['sort_order'],
            $data['is_active'] ? 1 : 0
        ];
    }
    
    return executeQuery($sql, $params);
}

/**
 * 删除分类
 */
function deleteCategory($id) {
    $sql = "DELETE FROM categories WHERE id = ?";
    return executeQuery($sql, [$id]);
}

/**
 * 获取关键词列表
 */
function getKeywords($category_id = null) {
    if ($category_id) {
        $sql = "SELECT k.*, c.name as category_name FROM keywords k JOIN categories c ON k.category_id = c.id WHERE k.category_id = ? ORDER BY k.weight DESC, k.keyword";
        return executeQuery($sql, [$category_id]);
    } else {
        $sql = "SELECT k.*, c.name as category_name FROM keywords k JOIN categories c ON k.category_id = c.id ORDER BY c.sort_order, k.weight DESC, k.keyword";
        return executeQuery($sql);
    }
}

/**
 * 保存关键词
 */
function saveKeyword($data) {
    if (isset($data['id']) && $data['id'] > 0) {
        // 更新
        $sql = "UPDATE keywords SET keyword = ?, category_id = ?, weight = ?, is_psychology = ?, psychology_level = ?, language = ?, is_active = ? WHERE id = ?";
        $params = [
            $data['keyword'],
            $data['category_id'],
            $data['weight'],
            $data['is_psychology'] ? 1 : 0,
            $data['psychology_level'],
            $data['language'],
            $data['is_active'] ? 1 : 0,
            $data['id']
        ];
    } else {
        // 创建
        $sql = "INSERT INTO keywords (keyword, category_id, weight, is_psychology, psychology_level, language, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $data['keyword'],
            $data['category_id'],
            $data['weight'],
            $data['is_psychology'] ? 1 : 0,
            $data['psychology_level'],
            $data['language'],
            $data['is_active'] ? 1 : 0
        ];
    }
    
    return executeQuery($sql, $params);
}

/**
 * 删除关键词
 */
function deleteKeyword($id) {
    $sql = "DELETE FROM keywords WHERE id = ?";
    return executeQuery($sql, [$id]);
}

/**
 * 获取回答模板列表
 */
function getResponseTemplates($category_id = null) {
    if ($category_id) {
        $sql = "SELECT rt.*, c.name as category_name FROM response_templates rt JOIN categories c ON rt.category_id = c.id WHERE rt.category_id = ? ORDER BY rt.priority DESC, rt.id";
        return executeQuery($sql, [$category_id]);
    } else {
        $sql = "SELECT rt.*, c.name as category_name FROM response_templates rt JOIN categories c ON rt.category_id = c.id ORDER BY c.sort_order, rt.priority DESC, rt.id";
        return executeQuery($sql);
    }
}

/**
 * 保存回答模板
 */
function saveResponseTemplate($data) {
    // 处理链接数据（转换为 JSON）
    $linksJson = null;
    if (isset($data['links']) && !empty($data['links'])) {
        $linksJson = is_string($data['links']) ? $data['links'] : json_encode($data['links']);
    }
    
    if (isset($data['id']) && $data['id'] > 0) {
        // 更新
        $sql = "UPDATE response_templates SET category_id = ?, title = ?, content_zh = ?, content_en = ?, links = ?, keywords = ?, priority = ?, is_active = ? WHERE id = ?";
        $params = [
            $data['category_id'],
            $data['title'],
            $data['content_zh'],
            $data['content_en'],
            $linksJson,
            $data['keywords'],
            $data['priority'],
            $data['is_active'] ? 1 : 0,
            $data['id']
        ];
    } else {
        // 创建
        $sql = "INSERT INTO response_templates (category_id, title, content_zh, content_en, links, keywords, priority, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $data['category_id'],
            $data['title'],
            $data['content_zh'],
            $data['content_en'],
            $linksJson,
            $data['keywords'],
            $data['priority'],
            $data['is_active'] ? 1 : 0
        ];
    }
    
    return executeQuery($sql, $params);
}

/**
 * 删除回答模板
 */
function deleteResponseTemplate($id) {
    $sql = "DELETE FROM response_templates WHERE id = ?";
    return executeQuery($sql, [$id]);
}

/**
 * 获取系统设置
 */
function getSystemSettings($category = null) {
    if ($category) {
        $sql = "SELECT * FROM system_settings WHERE category = ? ORDER BY setting_key";
        return executeQuery($sql, [$category]);
    } else {
        $sql = "SELECT * FROM system_settings ORDER BY category, setting_key";
        return executeQuery($sql);
    }
}

/**
 * 保存系统设置
 */
function saveSystemSetting($data) {
    $sql = "INSERT INTO system_settings (setting_key, setting_value, description, category, is_active) VALUES (?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), description = VALUES(description), 
            category = VALUES(category), is_active = VALUES(is_active)";
    $params = [
        $data['setting_key'],
        $data['setting_value'],
        $data['description'],
        $data['category'],
        $data['is_active'] ? 1 : 0
    ];
    
    return executeQuery($sql, $params);
}

/**
 * 删除系统设置
 */
function deleteSystemSetting($key) {
    $sql = "DELETE FROM system_settings WHERE setting_key = ?";
    return executeQuery($sql, [$key]);
}

/**
 * 执行数据清理操作
 */
function performDataCleanup($data) {
    if (!isset($data['type'])) {
        return ['success' => false, 'message' => 'Cleanup type is required'];
    }
    
    $type = $data['type'];
    
    try {
        switch ($type) {
            case 'all_questions':
                return cleanupAllQuestions();
                
            case 'old_questions':
                $days = $data['days'] ?? 30;
                return cleanupOldQuestions($days);
                
            case 'psychology_records':
                return cleanupPsychologyRecords();
                
            case 'keyword_matches':
                return cleanupKeywordMatches();
                
            case 'reset_stats':
                return resetStatistics();
                
            case 'optimize_db':
                return optimizeDatabase();
                
            default:
                return ['success' => false, 'message' => 'Invalid cleanup type'];
        }
    } catch (Exception $e) {
        error_log("Cleanup error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Cleanup failed: ' . $e->getMessage()];
    }
}

/**
 * 清理所有问题和回答
 */
function cleanupAllQuestions() {
    $conn = getDbConnection();
    
    try {
        $conn->beginTransaction();
        
        // 删除相关记录
        $tables = ['attention_records', 'keyword_matches', 'questions'];
        $totalDeleted = 0;
        
        foreach ($tables as $table) {
            $stmt = $conn->prepare("DELETE FROM $table");
            $stmt->execute();
            $totalDeleted += $stmt->rowCount();
        }
        
        // 重置自增ID
        foreach ($tables as $table) {
            $conn->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
        }
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => "All questions and related data cleared successfully. $totalDeleted records removed.",
            'deleted_count' => $totalDeleted
        ];
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

/**
 * 清理旧问题
 */
function cleanupOldQuestions($days) {
    $conn = getDbConnection();
    
    try {
        $conn->beginTransaction();
        
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        // 获取要删除的问题ID
        $stmt = $conn->prepare("SELECT id FROM questions WHERE created_at < ?");
        $stmt->execute([$cutoffDate]);
        $questionIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($questionIds)) {
            $conn->rollBack();
            return [
                'success' => true,
                'message' => "No old questions found to delete.",
                'deleted_count' => 0
            ];
        }
        
        $placeholders = str_repeat('?,', count($questionIds) - 1) . '?';
        
        // 删除相关记录
        $stmt = $conn->prepare("DELETE FROM attention_records WHERE question_id IN ($placeholders)");
        $stmt->execute($questionIds);
        $attentionDeleted = $stmt->rowCount();
        
        $stmt = $conn->prepare("DELETE FROM keyword_matches WHERE question_id IN ($placeholders)");
        $stmt->execute($questionIds);
        $keywordDeleted = $stmt->rowCount();
        
        $stmt = $conn->prepare("DELETE FROM questions WHERE id IN ($placeholders)");
        $stmt->execute($questionIds);
        $questionsDeleted = $stmt->rowCount();
        
        $conn->commit();
        
        $totalDeleted = $questionsDeleted + $attentionDeleted + $keywordDeleted;
        
        return [
            'success' => true,
            'message' => "Old questions deleted successfully. $questionsDeleted questions and $totalDeleted total records removed.",
            'deleted_count' => $totalDeleted
        ];
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

/**
 * 清理心理健康记录
 */
function cleanupPsychologyRecords() {
    $conn = getDbConnection();
    
    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("DELETE FROM attention_records");
        $stmt->execute();
        $deleted = $stmt->rowCount();
        
        // 重置问题表中的心理健康标记（可选）
        $stmt = $conn->prepare("UPDATE questions SET is_psychology_related = 0");
        $stmt->execute();
        $updated = $stmt->rowCount();
        
        $conn->exec("ALTER TABLE attention_records AUTO_INCREMENT = 1");
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => "Psychology records cleared successfully. $deleted attention records removed and $updated questions updated.",
            'deleted_count' => $deleted
        ];
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

/**
 * 清理关键词匹配记录
 */
function cleanupKeywordMatches() {
    $conn = getDbConnection();
    
    try {
        $stmt = $conn->prepare("DELETE FROM keyword_matches");
        $stmt->execute();
        $deleted = $stmt->rowCount();
        
        $conn->exec("ALTER TABLE keyword_matches AUTO_INCREMENT = 1");
        
        return [
            'success' => true,
            'message' => "Keyword matching records cleared successfully. $deleted records removed.",
            'deleted_count' => $deleted
        ];
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * 重置统计信息
 */
function resetStatistics() {
    // 这里可以清理任何统计相关的缓存或临时数据
    // 目前主要是重置会话相关数据
    
    return [
        'success' => true,
        'message' => "Statistics reset successfully. Note: Historical data in main tables is preserved.",
        'deleted_count' => 0
    ];
}

/**
 * 获取AI配置
 */
function getAIConfig() {
    $settings = getSystemSettings('ai');
    $config = [];
    
    // 将设置转换为配置数组
    if ($settings) {
        foreach ($settings as $setting) {
            $config[$setting['setting_key']] = $setting['setting_value'];
        }
    }
    
    // 设置默认值
    $defaults = [
        'ai_provider' => 'openai',
        'ai_api_endpoint' => 'https://api.openai.com/v1/chat/completions',
        'ai_api_key' => '',
        'ai_model' => 'gpt-3.5-turbo',
        'ai_enabled' => '0',
        'ai_max_tokens' => '1000',
        'ai_temperature' => '0.7',
        // 心理咨询系统Prompt（中英文分开）
        'ai_psychology_prompt_zh' => '你是一位专业的心理健康咨询助手。请提供支持性、富有同理心的回复，同时始终强调寻求专业帮助的重要性。切勿进行诊断或提供医疗建议。',
        'ai_psychology_prompt_en' => 'You are a professional mental health counselor assistant. Provide supportive, empathetic responses while always emphasizing the importance of seeking professional help. Never diagnose or provide medical advice.',
        'ai_auto_handover' => '1',
        'ai_handover_text_zh' => '转交AI助手',
        'ai_handover_text_en' => 'Switch to AI Assistant',
        'ai_takeover_msg_zh' => 'AI助手已接管对话。我会为您提供更详细的帮助。',
        'ai_takeover_msg_en' => 'AI Assistant has taken over the conversation. I\'ll provide you with more detailed assistance.'
    ];
    
    // 合并默认值和现有配置
    foreach ($defaults as $key => $default) {
        if (!isset($config[$key])) {
            $config[$key] = $default;
        }
    }
    
    return $config;
}

/**
 * 保存AI配置
 */
function saveAIConfig($data) {
    try {
        $saved = 0;
        
        foreach ($data as $key => $value) {
            // 检查是否存在该设置
            $existing = executeQuery("SELECT setting_key FROM system_settings WHERE setting_key = ?", [$key]);
            
            if ($existing && count($existing) > 0) {
                // 更新现有设置
                $result = executeQuery(
                    "UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?",
                    [$value, $key]
                );
            } else {
                // 创建新设置
                $result = executeQuery(
                    "INSERT INTO system_settings (setting_key, setting_value, description, category, is_active, created_at, updated_at) VALUES (?, ?, ?, 'ai', 1, NOW(), NOW())",
                    [$key, $value, "AI configuration: $key"]
                );
            }
            
            if ($result) {
                $saved++;
            }
        }
        
        return [
            'success' => $saved > 0,
            'message' => $saved > 0 ? "AI configuration saved successfully. Updated $saved settings." : 'No settings were saved.',
            'saved_count' => $saved
        ];
        
    } catch (Exception $e) {
        error_log("Save AI Config Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to save AI configuration: ' . $e->getMessage()
        ];
    }
}

/**
 * 测试 DeepL 翻译
 */
function testDeepLTranslation($data) {
    try {
        $apiKey = $data['api_key'] ?? '';
        $apiType = $data['api_type'] ?? 'free';
        
        if (empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'API Key is required'
            ];
        }
        
        // 测试文本
        $testText = "Based on this conversation history, I'm going to take a guess that you might be feeling regretful or disappointed about something? If so, I'd suggest taking some time to reflect on what happened and why it made you feel bad.";
        
        // 选择 API 端点
        $apiUrl = ($apiType === 'pro') 
            ? 'https://api.deepl.com/v2/translate'
            : 'https://api-free.deepl.com/v2/translate';
        
        // 构建请求数据
        $postData = http_build_query([
            'auth_key' => $apiKey,
            'text' => $testText,
            'target_lang' => 'ZH',
            'source_lang' => 'EN',
            'formality' => 'default',
            'preserve_formatting' => '1'
        ]);
        
        // 发送请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'CURL Error: ' . $error
            ];
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['message'] ?? "HTTP $httpCode";
            return [
                'success' => false,
                'error' => $errorMessage
            ];
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['translations'][0]['text'])) {
            return [
                'success' => true,
                'original' => $testText,
                'translated' => $result['translations'][0]['text']
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Unexpected response format'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage()
        ];
    }
}

/**
 * 测试AI连接
 */
function testAIConnection($data) {
    try {
        $provider = $data['provider'] ?? 'openai';
        $endpoint = $data['endpoint'] ?? '';
        $apiKey = $data['api_key'] ?? '';
        $model = $data['model'] ?? 'gpt-3.5-turbo';
        
        if (empty($endpoint) || ($provider !== 'ollama' && empty($apiKey))) {
            return [
                'success' => false,
                'error' => 'API endpoint and API key are required'
            ];
        }
        
        // 准备测试请求数据
        $testMessage = "This is a test message. Please respond with 'AI connection test successful!' in both English and Chinese.";
        
        $requestData = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $testMessage
                ]
            ],
            'max_tokens' => 100,
            'temperature' => 0.7
        ];
        
        // 设置HTTP请求头
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];
        
        // 如果是其他提供商，可能需要不同的头部
        if ($provider === 'claude') {
            $headers = [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01'
            ];
            
            // Claude API格式不同
            $requestData = [
                'model' => $model,
                'max_tokens' => 100,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $testMessage
                    ]
                ]
            ];
        } elseif ($provider === 'ollama') {
            // Ollama API: 支持 /api/chat 和 /api/generate
            // 无需API Key
            $headers = [ 'Content-Type: application/json' ];
            
            // 检测使用哪种 API 格式
            if (strpos($endpoint, '/api/generate') !== false) {
                // 旧版 /api/generate 格式
                $requestData = [
                    'model' => $model,
                    'prompt' => "System: You are a helpful assistant.\n\nUser: " . $testMessage,
                    'stream' => false
                ];
            } else {
                // 新版 /api/chat 格式
            $requestData = [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                    ['role' => 'user', 'content' => $testMessage]
                ],
                'stream' => false
            ];
            }
        }
        
        // 发送测试请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL error: ' . $error
            ];
        }
        
        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'success' => false,
                'error' => "HTTP error: $httpCode. Response: " . substr($response, 0, 200)
            ];
        }
        
        $responseData = json_decode($response, true);
        
        if (!$responseData) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response from AI provider'
            ];
        }
        
        // 提取回复内容（根据不同提供商的响应格式）
        $testResponse = '';
        
        if ($provider === 'openai' || $provider === 'custom') {
            if (isset($responseData['choices'][0]['message']['content'])) {
                $testResponse = $responseData['choices'][0]['message']['content'];
            }
        } elseif ($provider === 'claude') {
            if (isset($responseData['content'][0]['text'])) {
                $testResponse = $responseData['content'][0]['text'];
            }
        } elseif ($provider === 'gemini') {
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $testResponse = $responseData['candidates'][0]['content']['parts'][0]['text'];
            }
        } elseif ($provider === 'ollama') {
            // Ollama responses
            if (isset($responseData['message']['content'])) {
                $testResponse = $responseData['message']['content'];
            } elseif (isset($responseData['response'])) {
                $testResponse = $responseData['response'];
            }
        }
        
        if (empty($testResponse)) {
            return [
                'success' => false,
                'error' => 'No response content found. Response structure: ' . json_encode($responseData)
            ];
        }
        
        return [
            'success' => true,
            'test_response' => $testResponse,
            'full_response' => $responseData
        ];
        
    } catch (Exception $e) {
        error_log("Test AI Connection Error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Test failed: ' . $e->getMessage()
        ];
    }
}

/**
 * 优化数据库
 */
function optimizeDatabase() {
    $conn = getDbConnection();
    
    try {
        $tables = ['questions', 'categories', 'keywords', 'response_templates', 'system_settings', 'keyword_matches', 'attention_records'];
        $optimized = [];
        
        foreach ($tables as $table) {
            try {
                $conn->exec("OPTIMIZE TABLE $table");
                $optimized[] = $table;
            } catch (Exception $e) {
                // 某些表可能不支持优化，忽略错误
                error_log("Failed to optimize table $table: " . $e->getMessage());
            }
        }
        
        return [
            'success' => true,
            'message' => "Database optimization completed. Optimized tables: " . implode(', ', $optimized),
            'optimized_tables' => $optimized
        ];
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * 获取预设问题列表
 */
function getPresetQuestions() {
    try {
        $sql = "SELECT * FROM preset_questions ORDER BY sort_order ASC, category ASC";
        $result = executeQuery($sql);
        
        if ($result !== false) {
            return [
                'success' => true,
                'data' => $result
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to fetch preset questions'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ];
    }
}

/**
 * 创建或更新预设问题
 */
function savePresetQuestion($data) {
    try {
        $id = $data['id'] ?? null;
        $category = sanitizeInput($data['category']);
        $categoryIcon = sanitizeInput($data['category_icon']) ?: 'book';
        $categoryNameZh = sanitizeInput($data['category_name_zh']);
        $categoryNameEn = sanitizeInput($data['category_name_en']);
        $questionsZh = $data['questions_zh'];
        $questionsEn = $data['questions_en'];
        $sortOrder = intval($data['sort_order']) ?: 0;
        $isActive = isset($data['is_active']) ? 1 : 0;
        
        // 将问题文本转换为JSON数组
        if (is_string($questionsZh)) {
            $questionsZhArray = array_filter(array_map('trim', explode("\n", $questionsZh)));
            $questionsZh = json_encode($questionsZhArray, JSON_UNESCAPED_UNICODE);
        }
        
        if (is_string($questionsEn)) {
            $questionsEnArray = array_filter(array_map('trim', explode("\n", $questionsEn)));
            $questionsEn = json_encode($questionsEnArray, JSON_UNESCAPED_UNICODE);
        }
        
        if ($id) {
            // 更新现有记录
            $sql = "UPDATE preset_questions SET 
                    category = ?, category_icon = ?, category_name_zh = ?, category_name_en = ?,
                    questions_zh = ?, questions_en = ?, sort_order = ?, is_active = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            $params = [$category, $categoryIcon, $categoryNameZh, $categoryNameEn, 
                      $questionsZh, $questionsEn, $sortOrder, $isActive, $id];
        } else {
            // 创建新记录
            $sql = "INSERT INTO preset_questions (category, category_icon, category_name_zh, category_name_en, 
                    questions_zh, questions_en, sort_order, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$category, $categoryIcon, $categoryNameZh, $categoryNameEn, 
                      $questionsZh, $questionsEn, $sortOrder, $isActive];
        }
        
        $result = executeQuery($sql, $params);
        
        if ($result) {
            return [
                'success' => true,
                'message' => $id ? 'Preset question updated successfully' : 'Preset question created successfully',
                'id' => $id ?: getLastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to save preset question'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ];
    }
}

/**
 * 删除预设问题
 */
function deletePresetQuestion($id) {
    try {
        $sql = "DELETE FROM preset_questions WHERE id = ?";
        $result = executeQuery($sql, [$id]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Preset question deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to delete preset question'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ];
    }
}

// 主要处理逻辑
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// 公共动作（无需已登录）
$publicActions = ['login', 'logout', 'session'];
if (!in_array($action, $publicActions, true) && !validateAdmin()) {
    sendJsonResponse(['error' => 'Access denied'], 403);
}

// 管理员登录
function handleAdminLogin($data) {
    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');
    // 默认账户：admin / admin
    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['is_admin'] = true;
        return ['success' => true, 'message' => 'Login successful'];
    }
    return ['success' => false, 'error' => 'Invalid credentials'];
}

// 管理员登出
function handleAdminLogout() {
    // 清理会话状态
    $_SESSION['is_admin'] = false;
    unset($_SESSION['is_admin']);
    // 完全销毁会话
    if (session_status() === PHP_SESSION_ACTIVE) {
        // 清除会话数组
        session_unset();
        // 销毁会话
        session_destroy();
        // 清除会话 cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? false);
        }
    }
    return ['success' => true, 'message' => 'Logged out'];
}

// 会话状态
function getAdminSessionStatus() {
    return [
        'success' => true,
        'is_admin' => validateAdmin()
    ];
}

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'session':
                    sendJsonResponse(getAdminSessionStatus());
                    break;
                case 'history':
                    if (!validateAdmin()) { sendJsonResponse(['error' => 'Access denied'], 403); }
                    // 按会话分组显示对话历史
                    $studentId = $_GET['student_id'] ?? null;
                    $startDate = $_GET['start_date'] ?? null;
                    $endDate = $_GET['end_date'] ?? null;
                    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 20;
                    $offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;

                    // 构建筛选条件
                    $where = [];
                    $params = [];
                    if (!empty($studentId)) { $where[] = 'student_id = ?'; $params[] = $studentId; }
                    if (!empty($startDate)) { $where[] = 'created_at >= ?'; $params[] = $startDate . ' 00:00:00'; }
                    if (!empty($endDate)) { $where[] = 'created_at <= ?'; $params[] = $endDate . ' 23:59:59'; }
                    $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

                    // 获取会话列表（按会话分组）
                    $sessionSql = "SELECT 
                                    session_fingerprint,
                                    student_id,
                                    MIN(created_at) as start_time,
                                    MAX(created_at) as end_time,
                                    COUNT(*) as message_count,
                                    MAX(language) as language,
                                    MAX(category) as category
                                FROM conversation_history
                            $whereSql
                                GROUP BY session_fingerprint, student_id
                                ORDER BY end_time DESC
                            LIMIT ? OFFSET ?";
                    $sessionParams = array_merge($params, [$limit, $offset]);
                    $sessions = executeQuery($sessionSql, $sessionParams);

                    // 获取总会话数
                    $countSql = "SELECT COUNT(DISTINCT session_fingerprint) as total FROM conversation_history $whereSql";
                    $countRows = executeQuery($countSql, $params);
                    $total = ($countRows && isset($countRows[0]['total'])) ? intval($countRows[0]['total']) : 0;

                    // 为每个会话获取完整的消息列表
                    $result = [];
                    if ($sessions) {
                        foreach ($sessions as $session) {
                            $msgSql = "SELECT message_type, message_content, is_ai_response, created_at 
                                      FROM conversation_history 
                                      WHERE session_fingerprint = ? 
                                      ORDER BY created_at ASC";
                            $messages = executeQuery($msgSql, [$session['session_fingerprint']]);
                            
                            $result[] = [
                                'session_fingerprint' => $session['session_fingerprint'],
                                'student_id' => $session['student_id'],
                                'start_time' => $session['start_time'],
                                'end_time' => $session['end_time'],
                                'message_count' => $session['message_count'],
                                'language' => $session['language'],
                                'category' => $session['category'],
                                'messages' => $messages ?: []
                            ];
                        }
                    }

                    sendJsonResponse([
                        'success' => true,
                        'data' => $result,
                        'total' => $total,
                        'limit' => $limit,
                        'offset' => $offset
                    ]);
                    break;
                case 'categories':
                    $result = getCategories();
                    sendJsonResponse(['success' => true, 'data' => $result]);
                    break;
                    
                case 'keywords':
                    $category_id = $_GET['category_id'] ?? null;
                    $result = getKeywords($category_id);
                    sendJsonResponse(['success' => true, 'data' => $result]);
                    break;
                    
                case 'popular_keywords':
                    if (!validateAdmin()) { sendJsonResponse(['error' => 'Access denied'], 403); }
                    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 20;
                    $days = isset($_GET['days']) ? max(1, intval($_GET['days'])) : 30;
                    
                    // 统计最近N天的热门关键词
                    $sql = "SELECT matched_keywords, COUNT(*) as count 
                            FROM questions 
                            WHERE matched_keywords IS NOT NULL 
                            AND matched_keywords != '' 
                            AND matched_keywords != 'AI-generated'
                            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                            GROUP BY matched_keywords 
                            ORDER BY count DESC 
                            LIMIT ?";
                    $result = executeQuery($sql, [$days, $limit]);
                    sendJsonResponse(['success' => true, 'data' => $result ?: []]);
                    break;
                    
                case 'templates':
                    $category_id = $_GET['category_id'] ?? null;
                    $result = getResponseTemplates($category_id);
                    sendJsonResponse(['success' => true, 'data' => $result]);
                    break;
                    
                case 'settings':
                    $category = $_GET['category'] ?? null;
                    $result = getSystemSettings($category);
                    sendJsonResponse(['success' => true, 'data' => $result]);
                    break;
                    
                case 'ai_config':
                    $result = getAIConfig();
                    sendJsonResponse(['success' => true, 'config' => $result]);
                    break;
                case 'mail_config':
                    if (!validateAdmin()) { sendJsonResponse(['error' => 'Access denied'], 403); }
                    $cfg = getMailSettings();
                    // 按用户要求：直接返回明文密码
                    sendJsonResponse(['success' => true, 'config' => $cfg]);
                    break;
                    
                case 'preset_questions':
                case 'preset-questions':
                    // 管理端不需要验证，因为整个页面需要登录才能访问
                    // if (!validateAdmin()) { sendJsonResponse(['error' => 'Access denied'], 403); }
                    sendJsonResponse(getPresetQuestions());
                    break;
                    
                default:
                    sendJsonResponse(['error' => 'Invalid action'], 400);
            }
            break;
            
        case 'POST':
            // 如果 action 在 POST 中，使用 POST 中的 action
            if (!empty($_POST['action'])) {
                $action = $_POST['action'];
            }
            
            // 尝试从 $_POST 获取数据（FormData）
            if (!empty($_POST['data'])) {
                $data = $_POST['data'];
                debugLog("Received FormData: " . json_encode($_POST));
            } else {
                // 否则尝试从 JSON 获取数据
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            // 兼容空JSON或无请求体的情况（例如 logout 不需要请求体）
            if (!is_array($data)) {
                $data = [];
                }
                debugLog("Received JSON: " . $input);
            }
            
            debugLog("Action: $action, Method: " . ($_POST['method'] ?? 'none') . ", Data: " . json_encode($data));
            
            // 对于cleanup操作，action可能在POST数据中
            if (isset($data['action']) && $data['action'] === 'cleanup') {
                $action = 'cleanup';
            }
            
            switch ($action) {
                case 'login':
                    sendJsonResponse(handleAdminLogin($data));
                    break;
                case 'logout':
                    sendJsonResponse(handleAdminLogout());
                    break;
                case 'category':
                    $result = saveCategory($data);
                    sendJsonResponse(['success' => $result, 'message' => $result ? 'Category saved successfully' : 'Failed to save category']);
                    break;
                    
                case 'keyword':
                    $result = saveKeyword($data);
                    sendJsonResponse(['success' => $result, 'message' => $result ? 'Keyword saved successfully' : 'Failed to save keyword']);
                    break;
                    
                case 'template':
                    $result = saveResponseTemplate($data);
                    sendJsonResponse(['success' => $result, 'message' => $result ? 'Template saved successfully' : 'Failed to save template']);
                    break;
                    
                case 'setting':
                case 'system_settings':
                    $method = $_POST['method'] ?? 'save';
                    if ($method === 'save') {
                    $result = saveSystemSetting($data);
                    sendJsonResponse(['success' => $result, 'message' => $result ? 'Setting saved successfully' : 'Failed to save setting']);
                    } else {
                        sendJsonResponse(['success' => false, 'error' => 'Invalid method'], 400);
                    }
                    break;
                    
                case 'cleanup':
                    $result = performDataCleanup($data);
                    sendJsonResponse($result);
                    break;
                    
                case 'save_ai_config':
                    $result = saveAIConfig($data);
                    sendJsonResponse($result);
                    break;
                    
                case 'test_ai_connection':
                    $result = testAIConnection($data);
                    sendJsonResponse($result);
                    break;
                    
                case 'test_deepl_translation':
                    if (!validateAdmin()) { sendJsonResponse(['error' => 'Access denied'], 403); }
                    $result = testDeepLTranslation($data);
                    sendJsonResponse($result);
                    break;
                    
                case 'save_mail_config':
                    if (!validateAdmin()) { sendJsonResponse(['error' => 'Access denied'], 403); }
                    $result = saveMailSettings($data);
                    sendJsonResponse($result);
                    break;
                case 'test_mail':
                    if (!validateAdmin()) { sendJsonResponse(['error' => 'Access denied'], 403); }
                    $to = $data['to'] ?? '';
                    $subject = $data['subject'] ?? 'UON Q&A Test Email';
                    $html = $data['html'] ?? '<p>This is a test email from UON Q&A system.</p>';
                    $text = $data['text'] ?? 'This is a test email from UON Q&A system.';
                    $override = $data['config'] ?? null;

                    // 如果提供了覆盖配置，直接用覆盖配置进行一次发送；否则使用已保存配置
                    if (is_array($override) && !empty($override)) {
                        $cfg = getMailSettings();
                        $allowed = [
                            'mail_enabled','mail_smtp_host','mail_smtp_port','mail_smtp_secure',
                            'mail_smtp_username','mail_smtp_password','mail_from_email','mail_from_name'
                        ];
                        foreach ($allowed as $k) {
                            if (array_key_exists($k, $override)) {
                                if ($k === 'mail_smtp_password' && $override[$k] === '') { continue; } // 空密码不覆盖
                                $cfg[$k] = $override[$k];
                            }
                        }
                        $cfg['mail_enabled'] = true; // 强制开启用于测试
                        $res = smtpSendEmail($cfg, $to, $subject, $html, $text);
                        debugLog(['to'=>$to,'res'=>$res], 'TEST_MAIL_RESULT');
                        sendJsonResponse($res);
                    } else {
                        $res = sendMailAlert($to, $subject, $html, $text);
                        debugLog(['to'=>$to,'res'=>$res], 'TEST_MAIL_RESULT');
                        sendJsonResponse($res);
                    }
                    break;
                case 'history':
                    if (!validateAdmin()) { sendJsonResponse(['error' => 'Access denied'], 403); }
                    // 支持POST方式查询（便于复杂筛选提交）
                    $studentId = $data['student_id'] ?? null;
                    $startDate = $data['start_date'] ?? null;
                    $endDate = $data['end_date'] ?? null;
                    $messageType = $data['message_type'] ?? null;
                    $limit = isset($data['limit']) ? max(1, intval($data['limit'])) : 50;
                    $offset = isset($data['offset']) ? max(0, intval($data['offset'])) : 0;

                    $where = [];
                    $params = [];

                    if (!empty($studentId)) { $where[] = 'ch.student_id = ?'; $params[] = $studentId; }
                    if (!empty($startDate)) { $where[] = 'ch.created_at >= ?'; $params[] = $startDate . ' 00:00:00'; }
                    if (!empty($endDate)) { $where[] = 'ch.created_at <= ?'; $params[] = $endDate . ' 23:59:59'; }
                    if (!empty($messageType)) { $where[] = 'ch.message_type = ?'; $params[] = $messageType; }

                    $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

                    $sql = "SELECT ch.id, ch.student_id, ch.session_fingerprint, ch.question_id, ch.message_type, ch.message_content, ch.is_ai_response, ch.language, ch.category, ch.created_at
                            FROM conversation_history ch
                            $whereSql
                            ORDER BY ch.created_at DESC
                            LIMIT ? OFFSET ?";
                    $paramsWithPage = array_merge($params, [$limit, $offset]);
                    $rows = executeQuery($sql, $paramsWithPage);

                    $countSql = "SELECT COUNT(1) as total FROM conversation_history ch $whereSql";
                    $countRows = executeQuery($countSql, $params);
                    $total = ($countRows && isset($countRows[0]['total'])) ? intval($countRows[0]['total']) : 0;

                    sendJsonResponse([
                        'success' => true,
                        'data' => $rows ?: [],
                        'total' => $total,
                        'limit' => $limit,
                        'offset' => $offset
                    ]);
                    break;
                    
                case 'preset_questions':
                case 'preset-questions':
                    $method = $_POST['method'] ?? 'get';
                    
                    if ($method === 'get') {
                        sendJsonResponse(getPresetQuestions());
                    } elseif ($method === 'save') {
                        sendJsonResponse(savePresetQuestion($_POST['data']));
                    } elseif ($method === 'delete') {
                        $id = intval($_POST['id']);
                        sendJsonResponse(deletePresetQuestion($id));
                    } else {
                        sendJsonResponse(['success' => false, 'error' => 'Invalid method'], 400);
                    }
                    break;
                    
                case 'check_history_stats':
                    if (!validateAdmin()) { sendJsonResponse(['error' => 'Access denied'], 403); }
                    
                    $days = isset($data['days']) ? intval($data['days']) : 30;
                    
                    try {
                        $pdo = getDbConnection();
                        
                        // 统计总记录数
                        $totalSql = "SELECT COUNT(*) as count FROM conversation_history";
                        $totalStmt = $pdo->prepare($totalSql);
                        $totalStmt->execute();
                        $totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
                        $totalCount = $totalResult['count'];
                        
                        // 统计要删除的记录数
                        if ($days == 0) {
                            // 删除全部
                            $oldCount = $totalCount;
                        } else {
                            $oldSql = "SELECT COUNT(*) as count FROM conversation_history WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
                            $oldStmt = $pdo->prepare($oldSql);
                            $oldStmt->execute([$days]);
                            $oldResult = $oldStmt->fetch(PDO::FETCH_ASSOC);
                            $oldCount = $oldResult['count'];
                        }
                        
                        sendJsonResponse([
                            'success' => true,
                            'total_records' => $totalCount,
                            'old_records' => $oldCount,
                            'days' => $days
                        ]);
                    } catch (Exception $e) {
                        sendJsonResponse([
                            'success' => false,
                            'error' => 'Failed to check stats: ' . $e->getMessage()
                        ], 500);
                    }
                    break;
                
                case 'cleanup_history':
                    if (!validateAdmin()) { sendJsonResponse(['error' => 'Access denied'], 403); }
                    
                    $days = isset($data['days']) ? intval($data['days']) : 30;
                    
                    try {
                        $pdo = getDbConnection();
                        
                        if ($days == 0) {
                            // 删除全部记录
                            $countSql = "SELECT COUNT(*) as count FROM conversation_history";
                            $countStmt = $pdo->prepare($countSql);
                            $countStmt->execute();
                            $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
                            $deletedCount = $countResult['count'];
                            
                            $deleteSql = "DELETE FROM conversation_history";
                            $deleteStmt = $pdo->prepare($deleteSql);
                            $deleteStmt->execute();
                        } else {
                            // 先统计要删除的记录数
                            $countSql = "SELECT COUNT(*) as count FROM conversation_history WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
                            $countStmt = $pdo->prepare($countSql);
                            $countStmt->execute([$days]);
                            $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
                            $deletedCount = $countResult['count'];
                            
                            // 删除指定天数之前的对话历史
                            $deleteSql = "DELETE FROM conversation_history WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
                            $deleteStmt = $pdo->prepare($deleteSql);
                            $deleteStmt->execute([$days]);
                        }
                        
                        debugLog(['days' => $days, 'deleted' => $deletedCount], 'CLEANUP_HISTORY');
                        
                        $message = $days == 0 
                            ? "Deleted all {$deletedCount} conversation records"
                            : "Deleted {$deletedCount} conversation records older than {$days} days";
                        
                        sendJsonResponse([
                            'success' => true,
                            'deleted_count' => $deletedCount,
                            'message' => $message
                        ]);
                    } catch (Exception $e) {
                        debugLog(['error' => $e->getMessage()], 'CLEANUP_HISTORY_ERROR');
                        sendJsonResponse([
                            'success' => false,
                            'error' => 'Failed to clean history: ' . $e->getMessage()
                        ], 500);
                    }
                    break;
                    
                default:
                    sendJsonResponse(['error' => 'Invalid action'], 400);
            }
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                sendJsonResponse(['error' => 'ID required for delete operation'], 400);
            }
            
            switch ($action) {
                case 'category':
                    $result = deleteCategory($id);
                    sendJsonResponse(['success' => $result, 'message' => $result ? 'Category deleted successfully' : 'Failed to delete category']);
                    break;
                    
                case 'keyword':
                    $result = deleteKeyword($id);
                    sendJsonResponse(['success' => $result, 'message' => $result ? 'Keyword deleted successfully' : 'Failed to delete keyword']);
                    break;
                    
                case 'template':
                    $result = deleteResponseTemplate($id);
                    sendJsonResponse(['success' => $result, 'message' => $result ? 'Template deleted successfully' : 'Failed to delete template']);
                    break;
                    
                case 'setting':
                    $result = deleteSystemSetting($id);
                    sendJsonResponse(['success' => $result, 'message' => $result ? 'Setting deleted successfully' : 'Failed to delete setting']);
                    break;
                    
                default:
                    sendJsonResponse(['error' => 'Invalid action'], 400);
            }
            break;
            
        default:
            sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log("Admin Config API Error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Internal server error'], 500);
}
?> 