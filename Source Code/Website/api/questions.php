<?php
/**
 * 学生问答系统 API - UTF-8支持
 * Student Q&A System API with UTF-8 Support
 */

// 确保输出UTF-8编码
header('Content-Type: application/json; charset=UTF-8');
ini_set('default_charset', 'UTF-8');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';
require_once __DIR__ . '/mail.php';

/**
 * 生成用户指纹
 */
function generateUserFingerprint($ip, $userAgent, $additionalData = []) {
    // 每次生成唯一的会话指纹（包含时间戳和随机数）
    // 这样每次刷新页面都会创建新的会话
    $baseData = $ip . '|' . $userAgent . '|' . microtime(true) . '|' . bin2hex(random_bytes(16));
    
    // 添加额外的浏览器信息（如果有）
    if (!empty($additionalData)) {
        $baseData .= '|' . json_encode($additionalData);
    }
    
    // 生成SHA256哈希作为指纹
    return hash('sha256', $baseData);
}

/**
 * 获取或创建用户会话
 */
function getOrCreateUserSession($fingerprint, $ip, $userAgent, $browserInfo = []) {
    try {
        // 检查是否已存在会话
        $sql = "SELECT * FROM user_sessions WHERE session_fingerprint = ?";
        $session = executeQuery($sql, [$fingerprint]);
        
        if ($session && count($session) > 0) {
            // 更新最后访问时间
            $updateSql = "UPDATE user_sessions SET last_seen = CURRENT_TIMESTAMP WHERE session_fingerprint = ?";
            executeQuery($updateSql, [$fingerprint]);
            return $session[0];
        } else {
            // 创建新会话
            $userAgentHash = hash('sha256', $userAgent);
            $browserInfoJson = !empty($browserInfo) ? json_encode($browserInfo) : null;
            
            $insertSql = "INSERT INTO user_sessions (session_fingerprint, user_ip, user_agent_hash, browser_info) VALUES (?, ?, ?, ?)";
            executeQuery($insertSql, [$fingerprint, $ip, $userAgentHash, $browserInfoJson]);
            
            // 返回新创建的会话
            return [
                'session_fingerprint' => $fingerprint,
                'user_ip' => $ip,
                'user_agent_hash' => $userAgentHash,
                'browser_info' => $browserInfoJson,
                'total_questions' => 0,
                'ai_questions' => 0,
                'psychology_questions' => 0,
                'is_active' => true
            ];
        }
    } catch (Exception $e) {
        debugLog("获取用户会话失败: " . $e->getMessage(), 'ERROR');
        return null;
    }
}

/**
 * 保存对话到历史记录
 */
function saveConversationHistory($fingerprint, $messageType, $content, $questionId = null, $isAi = false, $language = 'zh', $category = null, $metadata = null, $studentId = null) {
    try {
        $metadataJson = $metadata ? json_encode($metadata) : null;
        
        $sql = "INSERT INTO conversation_history (session_fingerprint, student_id, question_id, message_type, message_content, is_ai_response, language, category, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $fingerprint,
            $studentId,
            $questionId,
            $messageType,
            $content,
            $isAi ? 1 : 0,
            $language,
            $category,
            $metadataJson
        ];
        
        return executeQuery($sql, $params);
    } catch (Exception $e) {
        debugLog("保存对话历史失败: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * 获取用户的对话历史（用于AI上下文）
 */
function getConversationHistory($fingerprint, $limit = 10) {
    try {
        // 获取最近的对话记录，只包含用户问题和AI回复
        $sql = "SELECT message_type, message_content, is_ai_response, language, category, created_at 
                FROM conversation_history 
                WHERE session_fingerprint = ? 
                AND message_type IN ('user', 'assistant')
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $results = executeQuery($sql, [$fingerprint, $limit]);
        
        if ($results) {
            // 按时间正序排列（最早的在前）
            return array_reverse($results);
        }
        
        return [];
    } catch (Exception $e) {
        debugLog("获取对话历史失败: " . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * 更新用户会话统计
 */
function updateSessionStats($fingerprint, $isAi = false, $isPsychology = false) {
    try {
        $sql = "UPDATE user_sessions SET 
                total_questions = total_questions + 1,
                ai_questions = ai_questions + ?,
                psychology_questions = psychology_questions + ?
                WHERE session_fingerprint = ?";
        
        return executeQuery($sql, [
            $isAi ? 1 : 0,
            $isPsychology ? 1 : 0,
            $fingerprint
        ]);
    } catch (Exception $e) {
        debugLog("更新会话统计失败: " . $e->getMessage(), 'ERROR');
        return false;
    }
}



/**
 * 检测语言
 */
function detectLanguage($text) {
    // 检查是否包含中文字符
    if (preg_match('/[\x{4e00}-\x{9fff}]/u', $text)) {
        return 'zh';
    }
    return 'en';
}

/**
 * 检查是否为心理健康相关问题
 */
function isPsychologyRelated($question, $keywords = null) {
    // 如果没有传入关键词，从数据库加载心理健康关键词
    if ($keywords === null) {
        $sql = "SELECT keyword FROM keywords WHERE is_psychology = 1 AND is_active = 1";
        $results = executeQuery($sql);
        $keywords = [];
        if ($results) {
            foreach ($results as $row) {
                $keywords[] = $row['keyword'];
            }
        }
    }
    
    $question_lower = mb_strtolower($question, 'UTF-8');
    
    foreach ($keywords as $keyword) {
        if (mb_strpos($question_lower, mb_strtolower($keyword, 'UTF-8')) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * 从数据库加载关键词和预设回答
 */
function loadDynamicKnowledgeBase() {
    try {
        // 获取活跃的关键词
        $keywords_sql = "
            SELECT k.keyword, k.weight, k.language, c.name as category_name 
            FROM keywords k 
            JOIN categories c ON k.category_id = c.id 
            WHERE k.is_active = 1 AND c.is_active = 1
            ORDER BY k.weight DESC
        ";
        $keywords = executeQuery($keywords_sql);
        
        // 获取活跃的预设回答模板（包含链接）
        $templates_sql = "
            SELECT rt.content_zh, rt.content_en, rt.keywords, rt.priority, rt.links, c.name as category_name
            FROM response_templates rt 
            JOIN categories c ON rt.category_id = c.id 
            WHERE rt.is_active = 1 AND c.is_active = 1
            ORDER BY rt.priority DESC
        ";
        $templates = executeQuery($templates_sql);
        
        $knowledgeBase = [];
        
        // 构建关键词映射
        if ($keywords) {
            foreach ($keywords as $kw) {
                $category = $kw['category_name'];
                if (!isset($knowledgeBase[$category])) {
                    $knowledgeBase[$category] = [
                        'keywords' => [],
                        'responses' => ['zh' => [], 'en' => []]
                    ];
                }
                
                // 添加关键词（不重复添加）
                if (!in_array($kw['keyword'], $knowledgeBase[$category]['keywords'])) {
                    $knowledgeBase[$category]['keywords'][] = $kw['keyword'];
                }
            }
        }
        
        // 构建预设回答（包含链接）
        if ($templates) {
            foreach ($templates as $tpl) {
                $category = $tpl['category_name'];
                if (!isset($knowledgeBase[$category])) {
                    $knowledgeBase[$category] = [
                        'keywords' => [],
                        'responses' => ['zh' => [], 'en' => []]
                    ];
                }
                
                // 解析链接（如果有）
                $links = null;
                if (!empty($tpl['links'])) {
                    $links = is_string($tpl['links']) ? json_decode($tpl['links'], true) : $tpl['links'];
                }
                
                if (!empty($tpl['content_zh'])) {
                    $knowledgeBase[$category]['responses']['zh'][] = [
                        'content' => $tpl['content_zh'],
                        'links' => $links
                    ];
                }
                if (!empty($tpl['content_en'])) {
                    $knowledgeBase[$category]['responses']['en'][] = [
                        'content' => $tpl['content_en'],
                        'links' => $links
                    ];
                }
            }
        }
        
        return $knowledgeBase;
        
    } catch (Exception $e) {
        debugLog("加载知识库失败: " . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * 匹配关键词并返回回复 - 改进版
 */
function matchKeywordsAndRespond($question, $language, $knowledgeBase = null) {
    // 如果没有传入知识库，从数据库动态加载
    if ($knowledgeBase === null) {
        $knowledgeBase = loadDynamicKnowledgeBase();
    }
    
    // 预处理问题文本
    $question_processed = preprocessQuestion($question);
    $matched_keywords = [];
    $category_scores = [];
    
    // 使用加权匹配算法
    foreach ($knowledgeBase as $category => $data) {
        $category_score = 0;
        $category_matched = [];
        
        foreach ($data['keywords'] as $keyword) {
            $match_result = smartKeywordMatch($question_processed, $keyword);
            if ($match_result['matched']) {
                $weight = getKeywordWeight($keyword, $category);
                $category_score += $match_result['score'] * $weight;
                $category_matched[] = $keyword;
            }
        }
        
        if ($category_score > 0) {
            $category_scores[$category] = [
                'score' => $category_score,
                'keywords' => $category_matched
            ];
        }
    }
    
    // 选择得分最高的分类
    if (!empty($category_scores)) {
        // 按得分排序
        uasort($category_scores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        $best_category = array_key_first($category_scores);
        $matched_keywords = $category_scores[$best_category]['keywords'];
        
        // 检查是否有有效的回复
        if (isset($knowledgeBase[$best_category]['responses'][$language]) 
            && !empty($knowledgeBase[$best_category]['responses'][$language])) {
            $responses = $knowledgeBase[$best_category]['responses'][$language];
            $selectedResponse = $responses[array_rand($responses)];
            
            // 提取内容和链接
            $responseContent = is_array($selectedResponse) ? $selectedResponse['content'] : $selectedResponse;
            $responseLinks = is_array($selectedResponse) && isset($selectedResponse['links']) ? $selectedResponse['links'] : null;
            
            // 如果是心理健康相关，优先使用系统设置生成回复
            if ($best_category === 'psychology' || $best_category === '心理健康' || 
                strpos(mb_strtolower($best_category, 'UTF-8'), 'psychology') !== false ||
                strpos(mb_strtolower($best_category, 'UTF-8'), '心理') !== false) {
                $psychology_response = getPsychologyResponseWithSettings($language);
                if (!empty($psychology_response)) {
                    $responseContent = $psychology_response;
                    // 心理健康回复可能没有链接
                }
            }
            
            debugLog([
                'question' => $question,
                'category' => $best_category,
                'score' => $category_scores[$best_category]['score'],
                'keywords' => $matched_keywords,
                'has_links' => !empty($responseLinks),
                'response_type' => ($best_category === 'psychology' || strpos(mb_strtolower($best_category, 'UTF-8'), '心理') !== false) ? 'psychology_settings' : 'template'
            ], 'KEYWORD_MATCH_SUCCESS');
            
            return [
                'response' => $responseContent,
                'links' => $responseLinks,
                'keywords' => $matched_keywords,
                'category' => $best_category
            ];
        }
    }
    
    // 如果没有匹配到或者得分太低，使用默认回复
    debugLog([
        'question' => $question,
        'scores' => $category_scores,
        'reason' => 'No match or low score'
    ], 'KEYWORD_MATCH_FAILED');
    
    // 从系统设置获取动态回复
    $default_response = getDefaultResponseWithSettings($language);
    
    return [
        'response' => $default_response,
        'keywords' => [],
        'category' => 'general'
    ];
}

/**
 * 预处理问题文本
 */
function preprocessQuestion($question) {
    // 转为小写
    $processed = mb_strtolower($question, 'UTF-8');
    
    // 简单的清理：只保留字母、数字、中文字符
    $processed = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $processed);
    
    // 移除多余空格
    $processed = preg_replace('/\s+/', ' ', $processed);
    $processed = trim($processed);
    
    return $processed;
}

/**
 * 智能关键词匹配 - 修复版本
 */
function smartKeywordMatch($question, $keyword) {
    $keyword_lower = mb_strtolower($keyword, 'UTF-8');
    $keyword_processed = preprocessQuestion($keyword);
    $question_lower = mb_strtolower($question, 'UTF-8');
    
    // 1. 完全匹配（最高分）
    if (mb_strpos($question_lower, $keyword_lower) !== false) {
        return ['matched' => true, 'score' => 1.0];
    }
    
    // 2. 处理后的完整匹配
    if (mb_strpos($question, $keyword_processed) !== false) {
        return ['matched' => true, 'score' => 0.95];
    }
    
    // 3. 单词边界匹配（英文）- 避免部分单词匹配
    if (preg_match('/^[a-z\s]+$/', $keyword_processed)) {
        $pattern = '/\b' . preg_quote($keyword_processed, '/') . '\b/i';
        if (preg_match($pattern, $question)) {
            return ['matched' => true, 'score' => 0.9];
        }
        
        // 英文单词完整匹配
        $question_words = preg_split('/[\s\p{P}]+/u', $question_lower);
        $keyword_words = preg_split('/[\s\p{P}]+/u', $keyword_lower);
        
        if (count($keyword_words) == 1) {
            // 单个关键词：必须完全匹配
            if (in_array($keyword_lower, $question_words)) {
                return ['matched' => true, 'score' => 0.85];
            }
        } else {
            // 多个关键词：检查所有关键词是否都存在
            $found_words = 0;
            foreach ($keyword_words as $word) {
                if (in_array($word, $question_words)) {
                    $found_words++;
                }
            }
            $word_ratio = $found_words / count($keyword_words);
            if ($word_ratio >= 0.8) { // 80%的词都匹配
                return ['matched' => true, 'score' => $word_ratio * 0.8];
            }
        }
    }
    
    // 4. 中文精确匹配
    if (preg_match('/[\x{4e00}-\x{9fff}]/u', $keyword_processed)) {
        // 中文关键词长度检查
        $keyword_length = mb_strlen($keyword_processed, 'UTF-8');
        
        if ($keyword_length >= 2) {
            // 对于较长的中文关键词，要求更严格的匹配
            if ($keyword_length >= 3) {
                // 3个字符以上：必须完全匹配
                if (mb_strpos($question, $keyword_processed) !== false) {
                    return ['matched' => true, 'score' => 0.9];
                }
            } else {
                // 2个字符：检查是否作为完整词出现
                $chars = mb_str_split($keyword_processed, 1, 'UTF-8');
                $found_consecutive = false;
                
                // 检查是否连续出现
                for ($i = 0; $i <= mb_strlen($question, 'UTF-8') - count($chars); $i++) {
                    $substring = mb_substr($question, $i, count($chars), 'UTF-8');
                    if ($substring === $keyword_processed) {
                        $found_consecutive = true;
                        break;
                    }
                }
                
                if ($found_consecutive) {
                    return ['matched' => true, 'score' => 0.8];
                }
            }
        }
    }
    
    return ['matched' => false, 'score' => 0];
}

/**
 * 从系统设置获取默认回复
 */
function getDefaultResponseWithSettings($language) {
    try {
        // 获取系统设置
        $settings_sql = "SELECT setting_key, setting_value FROM system_settings WHERE category = 'contact' AND is_active = 1";
        $settings_result = executeQuery($settings_sql);
        
        $settings = [];
        if ($settings_result) {
            foreach ($settings_result as $setting) {
                $settings[$setting['setting_key']] = $setting['setting_value'];
            }
        }
        
        // 获取默认值
        $hotline = $settings['contact_hotline'] ?? '010-12345678';
        $email = $settings['contact_email'] ?? 'help@university.edu';
        $hours = $settings['service_hours'] ?? '9:00-17:00';
        
        if ($language === 'zh') {
            return "很抱歉，我暂时无法理解您的问题。请尝试重新描述，或联系相关部门获取帮助。\n\n" .
                   "📞 24小时服务热线：{$hotline}\n" .
                   "📧 邮箱：{$email}\n" .
                   "🕐 服务时间：{$hours}";
        } else {
            return "Sorry, I cannot understand your question at the moment. Please try to rephrase it or contact the relevant department.\n\n" .
                   "📞 24/7 Hotline: {$hotline}\n" .
                   "📧 Email: {$email}\n" .
                   "🕐 Service Hours: {$hours}";
        }
        
    } catch (Exception $e) {
        debugLog("获取默认回复失败: " . $e->getMessage(), 'ERROR');
        
        // 降级回复
        if ($language === 'zh') {
            return '很抱歉，我暂时无法理解您的问题。请尝试重新描述，或联系相关部门获取帮助。';
        } else {
            return 'Sorry, I cannot understand your question at the moment. Please try to rephrase it or contact the relevant department.';
        }
    }
}

/**
 * 获取心理健康回复（使用系统设置）
 */
function getPsychologyResponseWithSettings($language) {
    try {
        // 获取心理健康相关的系统设置
        $settings_sql = "SELECT setting_key, setting_value FROM system_settings WHERE category IN ('contact', 'psychology') AND is_active = 1";
        $settings_result = executeQuery($settings_sql);
        
        $settings = [];
        if ($settings_result) {
            foreach ($settings_result as $setting) {
                $settings[$setting['setting_key']] = $setting['setting_value'];
            }
        }
        
        // 获取设置值
        $campus_counseling = $settings['campus_counseling'] ?? '1300 653 007';
        $emergency_hotline = $settings['emergency_hotline'] ?? '4921 6622';
        $psychology_email = $settings['psychology_email'] ?? $settings['contact_email'] ?? 'counseling@university.edu';
        $psychology_center = $settings['psychology_center_name'] ?? ($language === 'zh' ? '学校心理健康中心' : 'school mental health center');
        
        if ($language === 'zh') {
            return "如果您需要心理健康支持，请联系{$psychology_center}。\n\n" .
                   "📞 校园咨询：{$campus_counseling}\n" .
                   "🚨 24小时危机热线：{$emergency_hotline}\n\n" .
                   "寻求帮助是力量的表现。";
        } else {
            return "If you need mental health support, please contact the {$psychology_center}.\n\n" .
                   "📞 Campus counseling: {$campus_counseling}\n" .
                   "🚨 24/7 Crisis hotline: {$emergency_hotline}\n\n" .
                   "Seeking help is a sign of strength.";
        }
        
    } catch (Exception $e) {
        debugLog("获取心理健康回复失败: " . $e->getMessage(), 'ERROR');
        
        // 降级回复
        if ($language === 'zh') {
            return "如果您需要心理健康支持，请联系学校心理健康中心。\n\n" .
                   "📞 校园咨询：1300 653 007\n" .
                   "🚨 24小时危机热线：4921 6622\n\n" .
                   "寻求帮助是力量的表现。";
        } else {
            return "If you need mental health support, please contact the school mental health center.\n\n" .
                   "📞 Campus counseling: 1300 653 007\n" .
                   "🚨 24/7 Crisis hotline: 4921 6622\n\n" .
                   "Seeking help is a sign of strength.";
        }
    }
}

/**
 * 获取关键词权重
 */
function getKeywordWeight($keyword, $category) {
    static $weight_cache = [];
    
    if (!isset($weight_cache[$keyword])) {
        $sql = "SELECT weight FROM keywords WHERE keyword = ? AND is_active = 1 LIMIT 1";
        $result = executeQuery($sql, [$keyword]);
        $weight_cache[$keyword] = $result ? $result[0]['weight'] : 1.0;
    }
    
    return $weight_cache[$keyword];
}

/**
 * 提取 Suggestions 内容
 */
function extractSuggestionsContent($fullResponse) {
    // 匹配多种 Suggestions 格式
    $patterns = [
        '/\*\*3\.\s*Provide suggestions\.\*\*\s*\n(.*)/is',
        '/\*\*Suggestions:\*\*\s*\n(.*)/is',
        '/\*\*建议:\*\*\s*\n(.*)/is',
        '/3\.\s*Provide suggestions\.\s*\n(.*)/is',
        '/Suggestions:\s*\n(.*)/is',
        '/建议:\s*\n(.*)/is'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $fullResponse, $matches)) {
            return trim($matches[1]);
        }
    }
    
    // 如果没找到，返回完整内容
    return $fullResponse;
}

/**
 * 使用 DeepL API 翻译文本
 */
function translateWithDeepL($text, $targetLang = 'ZH') {
    try {
        // 从系统设置获取 DeepL 配置
        $sql = "SELECT setting_key, setting_value FROM system_settings 
                WHERE setting_key IN ('deepl_api_key', 'deepl_api_type') 
                AND is_active = 1";
        $settings = executeQuery($sql);
        
        $apiKey = null;
        $apiType = 'free'; // 默认使用免费版
        
        if ($settings) {
            foreach ($settings as $setting) {
                if ($setting['setting_key'] === 'deepl_api_key') {
                    $apiKey = $setting['setting_value'];
                }
                if ($setting['setting_key'] === 'deepl_api_type') {
                    $apiType = $setting['setting_value'];
                }
            }
        }
        
        // 如果没有配置 API Key，使用备用翻译
        if (empty($apiKey)) {
            debugLog('DeepL API key not configured, using fallback translation', 'TRANSLATION');
            return translateFallback($text);
        }
        
        // 选择 API 端点
        $apiUrl = ($apiType === 'pro') 
            ? 'https://api.deepl.com/v2/translate'
            : 'https://api-free.deepl.com/v2/translate';
        
        // 构建请求数据
        $postData = http_build_query([
            'auth_key' => $apiKey,
            'text' => $text,
            'target_lang' => $targetLang,
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
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            debugLog(['error' => $error], 'DEEPL_CURL_ERROR');
            return translateFallback($text);
        }
        
        if ($httpCode !== 200) {
            debugLog(['http_code' => $httpCode, 'response' => $response], 'DEEPL_API_ERROR');
            return translateFallback($text);
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['translations'][0]['text'])) {
            $translatedText = $result['translations'][0]['text'];
            debugLog(['original_length' => strlen($text), 'translated_length' => strlen($translatedText)], 'DEEPL_SUCCESS');
            return $translatedText;
        }
        
        debugLog(['result' => $result], 'DEEPL_UNEXPECTED_RESPONSE');
        return translateFallback($text);
        
    } catch (Exception $e) {
        debugLog(['error' => $e->getMessage()], 'DEEPL_EXCEPTION');
        return translateFallback($text);
    }
}

/**
 * 备用翻译方法（当 DeepL 不可用时）
 */
function translateFallback($text) {
    // 简单的关键词替换作为备用
    $keyTranslations = [
        'I suggest' => '我建议',
        'taking a moment to' => '花点时间',
        'reflect on' => '反思',
        'what might be causing these feelings' => '可能导致这些感受的原因',
        'Is it related to' => '这是否与',
        'a specific situation or person' => '特定的情况或人',
        'Are there any underlying concerns or worries' => '是否有潜在的担忧或顾虑',
        'that are contributing to' => '导致了',
        'this sense of difficulty' => '这种困难感',
        'Please feel free to share more about' => '请随时分享更多关于',
        'the context surrounding' => '相关的背景',
        'your situation' => '您的情况',
        'and I\'ll do my best to help you process them' => '我会尽力帮助您处理这些问题'
    ];
    
    $translated = $text;
    foreach ($keyTranslations as $en => $zh) {
        $translated = str_ireplace($en, $zh, $translated);
    }
    
    return $translated;
}

/**
 * 翻译 Suggestions 内容为简体中文
 */
function translateSuggestionsToZh($text) {
    // 使用 DeepL API 进行专业翻译
    return translateWithDeepL($text, 'ZH');
}

/**
 * 处理AI聊天请求
 */
function handleAIChat($data) {
    // 获取AI配置
    $aiConfig = getAIConfiguration();
    
    if (!$aiConfig['ai_enabled']) {
        return [
            'success' => false,
            'error' => 'AI integration is not enabled'
        ];
    }
    
    $question = sanitizeInput($data['question']);
    $student_id = sanitizeInput($data['student_id'] ?? null);
    $category = $data['category'] ?? 'general';
    $language = $data['language'] ?? 'en';
    $session_id = $data['session_id'] ?? generateSessionId();
    
    // 获取用户信息生成指纹
    $userIp = getClientIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $browserInfo = $data['browser_info'] ?? [];
    $userFingerprint = generateUserFingerprint($userIp, $userAgent, $browserInfo);
    
    // 获取或创建用户会话
    $userSession = getOrCreateUserSession($userFingerprint, $userIp, $userAgent, $browserInfo);
    
    // 获取对话历史（用于上下文）
    $conversationHistory = getConversationHistory($userFingerprint, 8); // 获取最近8轮对话
    
    // 保存用户问题到对话历史
    saveConversationHistory($userFingerprint, 'user', $question, null, false, $language, $category, null, $student_id);
    
    // 构建系统提示
    $systemPrompt = buildSystemPrompt($question, $category, $language, $aiConfig);
    
    // 调用AI API（包含对话历史和当前问题）
    $aiResponse = callAIAPI($systemPrompt, $aiConfig, $conversationHistory, $question);
    
    if (!$aiResponse['success']) {
        return [
            'success' => false,
            'error' => 'AI API call failed: ' . $aiResponse['error']
        ];
    }
    
    $response_text = $aiResponse['response'];
    
    // 提取 Suggestions 内容（用户可见部分）
    $suggestions_content = extractSuggestionsContent($response_text);
    
    // 如果是中文页面，翻译 Suggestions 内容
    $user_visible_response = $suggestions_content;
    if ($language === 'zh') {
        $user_visible_response = translateSuggestionsToZh($suggestions_content);
    }
    
    // 保存到数据库（保存完整内容）
    $is_psychology = ($category === 'psychology');
    $question_id = saveQuestionToDatabase(
        $question,
        $response_text, // 保存完整内容
        ['AI-generated'],
        $language,
        $is_psychology,
        $category,
        $student_id
    );
    
    // 保存AI回复到对话历史（保存完整内容）
    $links = generateRelevantLinks($question, $category, $language);
    $metadata = ['links' => $links, 'full_response' => $response_text];
    saveConversationHistory($userFingerprint, 'assistant', $response_text, $question_id, true, $language, $category, $metadata, $student_id);
    
    // 更新会话统计
    updateSessionStats($userFingerprint, true, $is_psychology);
    
    return [
        'success' => true,
        'response' => $user_visible_response, // 返回提取并翻译的内容给用户
        'full_response' => $response_text, // 完整内容（用于管理员查看）
        'language' => $language,
        'category' => $category,
        'is_psychology_related' => $is_psychology,
        'is_ai_response' => true,
        'links' => $links,
        'question_id' => $question_id,
        'user_fingerprint' => $userFingerprint,
        'conversation_turns' => count($conversationHistory) / 2, // 对话轮数
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * 生成相关链接
 */
function generateRelevantLinks($question, $category, $language) {
    $links = [];
    
    // 根据分类和问题内容生成相关链接
    if ($category === 'psychology') {
        if ($language === 'zh') {
            $links[] = [
                'title' => '心理健康服务中心',
                'url' => 'https://askuon.newcastle.edu.au/'
            ];
            $links[] = [
                'title' => '学生心理咨询预约',
                'url' => 'https://askuon.newcastle.edu.au/'
            ];
        } else {
            $links[] = [
                'title' => 'Mental Health Services',
                'url' => 'https://askuon.newcastle.edu.au/'
            ];
            $links[] = [
                'title' => 'Student Counseling Appointment',
                'url' => 'https://askuon.newcastle.edu.au/'
            ];
        }
    } elseif (stripos($question, '宿舍') !== false || stripos($question, 'dormitory') !== false) {
        if ($language === 'zh') {
            $links[] = [
                'title' => '宿舍管理规定',
                'url' => 'https://example.com/dormitory-rules'
            ];
            $links[] = [
                'title' => '宿舍设施报修',
                'url' => 'https://example.com/dormitory-repair'
            ];
        } else {
            $links[] = [
                'title' => 'Dormitory Rules',
                'url' => 'https://example.com/dormitory-guidelines'
            ];
            $links[] = [
                'title' => 'Facility Maintenance',
                'url' => 'https://example.com/maintenance-request'
            ];
        }
    } elseif (stripos($question, '图书馆') !== false || stripos($question, 'library') !== false) {
        if ($language === 'zh') {
            $links[] = [
                'title' => '图书馆官网',
                'url' => 'https://example.com/library'
            ];
            $links[] = [
                'title' => '数字资源',
                'url' => 'https://example.com/digital-resources'
            ];
        } else {
            $links[] = [
                'title' => 'Library Website',
                'url' => 'https://example.com/library-en'
            ];
            $links[] = [
                'title' => 'Digital Resources',
                'url' => 'https://example.com/digital-resources-en'
            ];
        }
    } elseif (stripos($question, '注册') !== false || stripos($question, 'registration') !== false) {
        if ($language === 'zh') {
            $links[] = [
                'title' => '学生注册系统',
                'url' => 'https://example.com/student-registration'
            ];
            $links[] = [
                'title' => '选课指南',
                'url' => 'https://example.com/course-selection-guide'
            ];
        } else {
            $links[] = [
                'title' => 'Student Registration',
                'url' => 'https://example.com/registration-en'
            ];
            $links[] = [
                'title' => 'Course Selection Guide',
                'url' => 'https://example.com/course-guide-en'
            ];
        }
    }
    
    // 如果没有特定链接，添加通用帮助链接
    if (empty($links)) {
        if ($language === 'zh') {
            $links[] = [
                'title' => '学生服务中心',
                'url' => 'https://example.com/student-services'
            ];
            $links[] = [
                'title' => '常见问题解答',
                'url' => 'https://example.com/faq'
            ];
        } else {
            $links[] = [
                'title' => 'Student Services',
                'url' => 'https://example.com/student-services-en'
            ];
            $links[] = [
                'title' => 'FAQ',
                'url' => 'https://example.com/faq-en'
            ];
        }
    }
    
    return $links;
}

/**
 * 获取AI配置
 */
function getAIConfiguration() {
    $sql = "SELECT setting_key, setting_value FROM system_settings WHERE category = 'ai' AND is_active = 1";
    $results = executeQuery($sql);
    
    $config = [
        'ai_enabled' => false,
        'ai_provider' => 'openai',
        'ai_api_endpoint' => 'https://api.openai.com/v1/chat/completions',
        'ai_api_key' => '',
        'ai_model' => 'gpt-3.5-turbo',
        'ai_max_tokens' => 1000,
        'ai_temperature' => 0.7,
        'ai_psychology_prompt_zh' => '你是一位专业的心理健康咨询助手。请提供支持性、富有同理心的回复，同时始终强调寻求专业帮助的重要性。切勿进行诊断或提供医疗建议。',
        'ai_psychology_prompt_en' => 'You are a professional mental health counselor assistant. Provide supportive, empathetic responses while always emphasizing the importance of seeking professional help. Never diagnose or provide medical advice.'
    ];
    
    if ($results) {
        foreach ($results as $setting) {
            $config[$setting['setting_key']] = $setting['setting_value'];
        }
    }
    
    // 转换布尔值和数值
    $config['ai_enabled'] = ($config['ai_enabled'] === '1');
    $config['ai_max_tokens'] = intval($config['ai_max_tokens']);
    $config['ai_temperature'] = floatval($config['ai_temperature']);
    
    return $config;
}

/**
 * 构建AI提示词（已废弃，使用 buildSystemPrompt 代替）
 */
function buildAIPrompt($question, $category, $language, $config) {
    // 根据语言选择对应的 prompt
    if ($category === 'psychology') {
        $system_prompt = ($language === 'zh') ? 
            ($config['ai_psychology_prompt_zh'] ?? $config['ai_psychology_prompt'] ?? '') : 
            ($config['ai_psychology_prompt_en'] ?? $config['ai_psychology_prompt'] ?? '');
    } else {
        // 非心理咨询问题不使用 AI
        $system_prompt = '';
    }
    
    return $system_prompt . "\n\nUser question: " . $question;
}

/**
 * 构建包含上下文的AI提示（已废弃）
 */
function buildAIPromptWithContext($question, $category, $language, $config, $conversationHistory = []) {
    // 根据语言选择对应的 prompt
    if ($category === 'psychology') {
        $system_prompt = ($language === 'zh') ? 
            ($config['ai_psychology_prompt_zh'] ?? '') : 
            ($config['ai_psychology_prompt_en'] ?? '');
    } else {
        $system_prompt = '';
    }
    
    // 构建上下文信息
    $context_section = '';
    if (!empty($conversationHistory)) {
        $context_section = "\n\n=== Previous Conversation Context ===\n";
        
        foreach ($conversationHistory as $entry) {
            $role = $entry['is_ai_response'] ? 'Assistant' : 'User';
            $time = date('H:i', strtotime($entry['created_at']));
            $context_section .= "[$time] $role: " . $entry['message_content'] . "\n";
        }
        
        $context_section .= "=== End of Context ===\n\n";
        
        // 添加上下文指导
        if ($language === 'zh') {
            $context_section .= "请基于以上对话历史来回答用户的新问题，保持对话的连贯性和一致性。\n\n";
        } else {
            $context_section .= "Please answer the user's new question based on the conversation history above, maintaining coherence and consistency.\n\n";
        }
    }
    
    return $system_prompt . $context_section . "Current question: " . $question;
}

/**
 * 构建系统提示（用于messages格式）
 */
function buildSystemPrompt($question, $category, $language, $config) {
    // 只支持心理咨询类别
    if ($category === 'psychology') {
        // 根据语言选择对应的 prompt
        $system_prompt = ($language === 'zh') ? 
            ($config['ai_psychology_prompt_zh'] ?? '你是一位专业的心理健康咨询助手。') : 
            ($config['ai_psychology_prompt_en'] ?? 'You are a professional mental health counselor assistant.');
    } else {
        // 非心理咨询问题使用通用提示
        $system_prompt = ($language === 'zh') ? 
            '你是一位友好的大学助手。请提供准确、友好的回复。' : 
            'You are a friendly university assistant. Provide accurate and friendly responses.';
    }
    
    // 添加对话历史指导
    $context_instruction = ($language === 'zh') ? 
        ' 请基于对话历史保持回复的连贯性和一致性。' : 
        ' Please maintain coherence and consistency based on conversation history.';
    
    return $system_prompt . $context_instruction;
}

/**
 * 调用AI API
 */
function callAIAPI($systemPrompt, $config, $conversationHistory = [], $currentQuestion = '') {
    $endpoint = $config['ai_api_endpoint'];
    $api_key = $config['ai_api_key'];
    $model = $config['ai_model'];
    $provider = $config['ai_provider'];
    
    if (empty($endpoint) || ($provider !== 'ollama' && empty($api_key))) {
        return [
            'success' => false,
            'error' => 'AI API configuration is incomplete'
        ];
    }
    
    // 构建消息数组，包含上下文
    $messages = [];
    
    // 添加系统提示
    $messages[] = [
        'role' => 'system',
        'content' => $systemPrompt
    ];
    
    // 添加历史对话（如果有）
    if (!empty($conversationHistory)) {
        foreach ($conversationHistory as $entry) {
            $role = $entry['is_ai_response'] ? 'assistant' : 'user';
            $messages[] = [
                'role' => $role,
                'content' => $entry['message_content']
            ];
        }
    }
    
    // 添加当前用户问题
    if (!empty($currentQuestion)) {
        $messages[] = [
            'role' => 'user',
            'content' => $currentQuestion
        ];
    }
    
    // 构建请求数据
    $request_data = [
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => intval($config['ai_max_tokens']),
        'temperature' => floatval($config['ai_temperature'])
    ];
    
    // 设置请求头
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];
    
    // 根据不同提供商调整格式
    if ($provider === 'claude') {
        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $api_key,
            'anthropic-version: 2023-06-01'
        ];
        
        $request_data = [
            'model' => $model,
            'max_tokens' => intval($config['ai_max_tokens']),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $currentQuestion
                ]
            ]
        ];
    } elseif ($provider === 'ollama') {
        // Ollama API
        $headers = [ 'Content-Type: application/json' ];
        
        // 检测使用哪种 API 格式
        if (strpos($endpoint, '/api/generate') !== false) {
            // 旧版 /api/generate 格式
            // 将 messages 转换为单个 prompt
            $prompt = '';
            foreach ($messages as $msg) {
                if ($msg['role'] === 'system') {
                    $prompt .= "System: " . $msg['content'] . "\n\n";
                } elseif ($msg['role'] === 'user') {
                    $prompt .= "User: " . $msg['content'] . "\n";
                } elseif ($msg['role'] === 'assistant') {
                    $prompt .= "Assistant: " . $msg['content'] . "\n";
                }
            }
            
            $request_data = [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false
            ];
        } else {
            // 新版 /api/chat 格式
            $request_data = [
                'model' => $model,
                'messages' => $messages,
                'stream' => false
            ];
        }
    }
    
    // 发送请求
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => 'cURL error: ' . $error
        ];
    }
    
    if ($http_code < 200 || $http_code >= 300) {
        return [
            'success' => false,
            'error' => "HTTP error: $http_code. Response: " . substr($response, 0, 200)
        ];
    }
    
    $response_data = json_decode($response, true);
    
    if (!$response_data) {
        return [
            'success' => false,
            'error' => 'Invalid JSON response from AI provider'
        ];
    }
    
    // 提取回复内容
    $response_text = '';
    
    if ($provider === 'openai' || $provider === 'custom') {
        if (isset($response_data['choices'][0]['message']['content'])) {
            $response_text = $response_data['choices'][0]['message']['content'];
        }
    } elseif ($provider === 'claude') {
        if (isset($response_data['content'][0]['text'])) {
            $response_text = $response_data['content'][0]['text'];
        }
    } elseif ($provider === 'gemini') {
        if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
            $response_text = $response_data['candidates'][0]['content']['parts'][0]['text'];
        }
    } elseif ($provider === 'ollama') {
        if (isset($response_data['message']['content'])) {
            $response_text = $response_data['message']['content'];
        } elseif (isset($response_data['response'])) {
            $response_text = $response_data['response'];
        }
    }
    
    if (empty($response_text)) {
        return [
            'success' => false,
            'error' => 'No response content found in AI API response'
        ];
    }
    
    return [
        'success' => true,
        'response' => $response_text
    ];
}

/**
 * 保存问题到数据库
 */
function saveQuestionToDatabase($question, $response, $keywords, $language, $is_psychology, $category, $student_id = null) {
    try {
        $sql = "INSERT INTO questions (user_question, student_id, matched_keywords, response_text, language, is_psychology_related, user_ip, user_agent, session_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $question,
            $student_id,
            implode(',', $keywords),
            $response,
            $language,
            $is_psychology ? 1 : 0,
            getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            generateSessionId()
        ];
        
        $result = executeQuery($sql, $params);
        $question_id = getLastInsertId();
        
        if ($result && $question_id) {
            // 保存关键词匹配记录
            if (!empty($keywords)) {
                foreach ($keywords as $keyword) {
                    $sql_keyword = "INSERT INTO keyword_matches (question_id, keyword, category) VALUES (?, ?, ?)";
                    executeQuery($sql_keyword, [$question_id, $keyword, $category]);
                }
            }
            
            // 如果是心理健康相关，创建关注记录
            if ($is_psychology) {
                $attention_level = 'medium';
                // 简单的风险评估
                $high_risk_keywords = ['自杀', '轻生', '死', 'suicide', 'die', 'death'];
                foreach ($high_risk_keywords as $risk_keyword) {
                    if (mb_strpos(mb_strtolower($question, 'UTF-8'), mb_strtolower($risk_keyword, 'UTF-8')) !== false) {
                        $attention_level = 'urgent';
                        break;
                    }
                }
                
                $sql_attention = "INSERT INTO attention_records (question_id, attention_level, keywords_triggered) VALUES (?, ?, ?)";
                executeQuery($sql_attention, [$question_id, $attention_level, implode(',', $keywords)]);

                // 发送风险邮件告警（基于配置的触发层级与收件人）
                try {
                    $mailCfg = getMailSettings();
                    if ($mailCfg['mail_enabled']) {
                        $levels = $mailCfg['__levels_array'];
                        if (in_array(strtolower($attention_level), $levels, true)) {
                            $recipients = $mailCfg['__recipients_array'];
                            if (!empty($recipients)) {
                                $subject = '[Risk Alert] ' . strtoupper($attention_level) . ' level detected';
                                $studentStr = $student_id ? $student_id : 'Unknown';
                                $html = '<h3>Risk Alert (' . htmlspecialchars(strtoupper($attention_level)) . ')</h3>' .
                                        '<p><strong>Student ID:</strong> ' . htmlspecialchars($studentStr) . '</p>' .
                                        '<p><strong>Question ID:</strong> ' . intval($question_id) . '</p>' .
                                        '<p><strong>Category:</strong> ' . htmlspecialchars((string)$category) . '</p>' .
                                        '<p><strong>Keywords:</strong> ' . htmlspecialchars(implode(", ", $keywords)) . '</p>' .
                                        '<p><strong>Question:</strong><br>' . nl2br(htmlspecialchars($question)) . '</p>' .
                                        '<p><em>Sent at ' . date('Y-m-d H:i:s') . '</em></p>';
                                $text = 'Risk Alert (' . strtoupper($attention_level) . ")\n" .
                                        'Student ID: ' . $studentStr . "\n" .
                                        'Question ID: ' . $question_id . "\n" .
                                        'Category: ' . (string)$category . "\n" .
                                        'Keywords: ' . implode(', ', $keywords) . "\n\n" .
                                        'Question: ' . $question . "\n" .
                                        'Sent at ' . date('Y-m-d H:i:s');
                                sendMailAlert($recipients, $subject, $html, $text);
                            }
                        }
                    }
                } catch (Exception $e) {
                    debugLog('Mail alert failed: ' . $e->getMessage(), 'MAIL_ALERT_ERROR');
                }
            }
            
            return $question_id;
        }
        
        return false;
        
    } catch (Exception $e) {
        debugLog("保存问题失败: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

// 主要处理逻辑
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取POST数据
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['question'])) {
        sendJsonResponse(['error' => 'Invalid request data'], 400);
    }
    
    // 检查是否是AI聊天请求
    $action = $_GET['action'] ?? '';
    if ($action === 'ai_chat') {
        try {
            $response = handleAIChat($data);
            sendJsonResponse($response);
        } catch (Exception $e) {
            debugLog("AI聊天处理失败: " . $e->getMessage(), 'ERROR');
            sendJsonResponse(['error' => 'AI chat processing failed'], 500);
        }
        return;
    }
    
    if ($action === 'get_context_debug') {
        // 仅在调试模式开放
        if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
            sendJsonResponse(['success' => false, 'error' => 'Debug endpoint disabled'], 403);
        }
        // 调试用：获取用户对话历史
        $userFingerprint = $data['user_fingerprint'] ?? null;
        if (!$userFingerprint) {
            sendJsonResponse(['success' => false, 'error' => 'User fingerprint required']);
        } else {
            $history = getConversationHistory($userFingerprint, 20);
            sendJsonResponse([
                'success' => true,
                'history' => $history,
                'user_fingerprint' => $userFingerprint
            ]);
        }
        return;
    }
    
    $question = sanitizeInput($data['question']);
    $student_id = sanitizeInput($data['student_id'] ?? null);
    $user_language = isset($data['language']) ? $data['language'] : 'en';
    
    // 检测问题语言
    $detected_language = detectLanguage($question);
    $language = $detected_language === 'zh' ? 'zh' : 'en';
    
    debugLog(['question' => $question, 'language' => $language], 'RECEIVED_QUESTION');
    
    // 获取用户信息生成指纹
    $userIp = getClientIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $browserInfo = $data['browser_info'] ?? [];
    $userFingerprint = generateUserFingerprint($userIp, $userAgent, $browserInfo);
    
    // 获取或创建用户会话
    $userSession = getOrCreateUserSession($userFingerprint, $userIp, $userAgent, $browserInfo);
    
    // 保存用户问题到对话历史
    saveConversationHistory($userFingerprint, 'user', $question, null, false, $language, null, null, $student_id);
    
    // 检查是否为心理健康相关
    $is_psychology = isPsychologyRelated($question);
    
    // 匹配关键词并生成回复
    $result = matchKeywordsAndRespond($question, $language);
    
    // 如果检测到心理健康相关但没有匹配到心理健康类别，使用专门的心理健康回复
    if ($is_psychology && $result['category'] !== 'psychology' && 
        strpos(mb_strtolower($result['category'], 'UTF-8'), '心理') === false &&
        strpos(mb_strtolower($result['category'], 'UTF-8'), 'psychology') === false) {
        
        $psychology_response = getPsychologyResponseWithSettings($language);
        if (!empty($psychology_response)) {
            $result['response'] = $psychology_response;
            $result['category'] = 'psychology';
            debugLog([
                'question' => $question,
                'original_category' => $result['category'],
                'override_reason' => 'psychology_detected_but_not_categorized'
            ], 'PSYCHOLOGY_OVERRIDE');
        }
    }
    
    // 保存到数据库
    $question_id = saveQuestionToDatabase(
        $question,
        $result['response'],
        $result['keywords'],
        $language,
        $is_psychology,
        $result['category'],
        $student_id
    );
    
    // 保存助手回复到对话历史
    $metadata = ['category' => $result['category'], 'keywords' => $result['keywords']];
    saveConversationHistory($userFingerprint, 'assistant', $result['response'], $question_id, false, $language, $result['category'], $metadata, $student_id);
    
    // 更新用户会话统计
    updateSessionStats($userFingerprint, false, $is_psychology);
    
    $response_data = [
        'success' => true,
        'response' => $result['response'],
        'links' => $result['links'] ?? null,
        'language' => $language,
        'category' => $result['category'],
        'is_psychology_related' => $is_psychology,
        'question_id' => $question_id,
        'user_fingerprint' => $userFingerprint,
        'session_info' => [
            'total_questions' => ($userSession['total_questions'] ?? 0) + 1,
            'is_new_session' => ($userSession['total_questions'] ?? 0) === 0
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    debugLog($response_data, 'RESPONSE_DATA');
    sendJsonResponse($response_data);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'stats':
            // 获取统计数据
            $stats_sql = "
                SELECT 
                    COUNT(*) as total_questions,
                    COUNT(DISTINCT session_id) as unique_sessions,
                    SUM(CASE WHEN is_psychology_related = 1 THEN 1 ELSE 0 END) as psychology_questions,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_questions
                FROM questions 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ";
            
            $stats = executeQuery($stats_sql);
            
            if ($stats) {
                sendJsonResponse(['success' => true, 'stats' => $stats[0]]);
            } else {
                sendJsonResponse(['error' => 'Failed to get statistics'], 500);
            }
            break;
            
        case 'recent':
            // 获取最近的问题
            $limit = intval($_GET['limit'] ?? 10);
            $recent_sql = "SELECT id, user_question, student_id, response_text, language, is_psychology_related, created_at FROM questions ORDER BY created_at DESC LIMIT ?";
            
            $recent_questions = executeQuery($recent_sql, [$limit]);
            
            if ($recent_questions !== false) {
                sendJsonResponse(['success' => true, 'questions' => $recent_questions]);
            } else {
                sendJsonResponse(['error' => 'Failed to get recent questions'], 500);
            }
            break;
            
        case 'psychology_alerts':
            // 获取心理健康预警
            $alerts_sql = "
                SELECT 
                    q.id,
                    q.user_question as question,
                    q.created_at,
                    ar.attention_level,
                    ar.keywords_triggered
                FROM questions q
                JOIN attention_records ar ON q.id = ar.question_id
                WHERE q.is_psychology_related = 1
                ORDER BY 
                    CASE ar.attention_level 
                        WHEN 'urgent' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        ELSE 4
                    END,
                    q.created_at DESC
                LIMIT 50
            ";
            
            $alerts = executeQuery($alerts_sql);
            
            if ($alerts !== false) {
                sendJsonResponse(['success' => true, 'alerts' => $alerts]);
            } else {
                sendJsonResponse(['error' => 'Failed to get psychology alerts'], 500);
            }
            break;
            
        case 'test':
            // 测试连接
            $test_result = testDatabaseConnection();
            sendJsonResponse($test_result);
            break;
            
        case 'get_context_debug':
            // 仅在调试模式开放
            if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
                sendJsonResponse(['success' => false, 'error' => 'Debug endpoint disabled'], 403);
                break;
            }
            // 调试用：获取用户对话历史
            $userFingerprint = $_GET['user_fingerprint'] ?? null;
            if (!$userFingerprint) {
                sendJsonResponse(['success' => false, 'error' => 'User fingerprint required']);
            } else {
                $history = getConversationHistory($userFingerprint, 20);
                sendJsonResponse([
                    'success' => true,
                    'history' => $history,
                    'user_fingerprint' => $userFingerprint
                ]);
            }
            break;
            
        case 'preset_questions':
            // 获取预设问题（只返回 active 的分类）
            try {
                // 只返回 preset_questions 和 categories 都是 active 的记录
                $sql = "SELECT pq.* FROM preset_questions pq 
                        LEFT JOIN categories c ON pq.category = c.name 
                        WHERE pq.is_active = 1 AND c.is_active = 1
                        ORDER BY pq.sort_order ASC, pq.category ASC";
                $result = executeQuery($sql);
                
                if ($result !== false) {
                    // 返回数组格式（与 admin-config.php 保持一致）
                    $formattedQuestions = [];
                    foreach ($result as $question) {
                        $formattedQuestions[] = [
                            'id' => $question['id'],
                            'category' => $question['category'],
                            'category_icon' => $question['category_icon'],
                            'category_name_zh' => $question['category_name_zh'],
                            'category_name_en' => $question['category_name_en'],
                            'questions_zh' => json_decode($question['questions_zh'], true),
                            'questions_en' => json_decode($question['questions_en'], true),
                            'sort_order' => $question['sort_order'],
                            'is_active' => $question['is_active']
                        ];
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $formattedQuestions
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Failed to fetch preset questions'
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Database error: ' . $e->getMessage()
                ]);
            }
            exit;
            
        case 'public_settings':
            // 获取公共系统设置（如欢迎文字）
            try {
                $sql = "SELECT setting_key, setting_value FROM system_settings 
                        WHERE category = 'general' AND is_active = 1 
                        AND setting_key IN ('welcome_text_zh', 'welcome_text_en')";
                $result = executeQuery($sql);
                
                if ($result !== false) {
                    echo json_encode([
                        'success' => true,
                        'data' => $result
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Failed to fetch settings'
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Database error: ' . $e->getMessage()
                ]);
            }
            exit;
            
        default:
            sendJsonResponse(['error' => 'Invalid action'], 400);
    }
    
} else {
    sendJsonResponse(['error' => 'Method not allowed'], 405);
}
?> 