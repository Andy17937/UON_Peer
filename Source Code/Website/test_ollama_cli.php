<?php
/**
 * Ollama API 命令行测试脚本
 * 使用方法: php test_ollama_cli.php <your_ollama_url> <model_name>
 * 例如: php test_ollama_cli.php https://your-domain.com/api/chat llama2
 */

if ($argc < 2) {
    echo "使用方法: php test_ollama_cli.php <ollama_api_url> [model_name]\n";
    echo "例如: php test_ollama_cli.php https://your-domain.com/api/chat llama2\n";
    echo "\n";
    echo "或者直接运行测试默认配置:\n";
    echo "php test_ollama_cli.php\n";
    exit(1);
}

$endpoint = $argc >= 2 ? $argv[1] : 'http://localhost:11434/api/chat';
$model = $argc >= 3 ? $argv[2] : 'llama2';

echo "==========================================\n";
echo "🔍 Ollama API 连接测试\n";
echo "==========================================\n\n";

echo "📡 测试端点: {$endpoint}\n";
echo "🤖 使用模型: {$model}\n";
echo "\n";

// 准备测试数据
$testData = [
    'model' => $model,
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'Say "Hello! Connection successful!" in both English and Chinese.']
    ],
    'stream' => false
];

echo "📤 发送请求...\n";
echo "请求数据:\n";
echo json_encode($testData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 发送请求
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlInfo = curl_getinfo($ch);

curl_close($ch);

echo "==========================================\n";
echo "📥 响应结果\n";
echo "==========================================\n\n";

echo "⏱️  响应时间: {$duration} ms\n";
echo "📊 HTTP 状态码: {$httpCode}\n\n";

if (!empty($curlError)) {
    echo "❌ cURL 错误: {$curlError}\n\n";
    echo "连接信息:\n";
    echo "- 目标 URL: " . $curlInfo['url'] . "\n";
    echo "- 连接时间: " . ($curlInfo['connect_time'] * 1000) . " ms\n";
    echo "- 总时间: " . ($curlInfo['total_time'] * 1000) . " ms\n\n";
    
    echo "💡 可能的问题:\n";
    echo "1. Ollama 服务未运行 - 运行 'ollama serve' 启动服务\n";
    echo "2. 公网映射未配置或已断开\n";
    echo "3. 防火墙阻止了连接\n";
    echo "4. URL 地址不正确\n";
    exit(1);
}

if ($httpCode !== 200) {
    echo "⚠️  HTTP 错误: {$httpCode}\n\n";
    echo "响应内容:\n";
    echo substr($response, 0, 500) . "\n\n";
    
    if ($httpCode === 404) {
        echo "💡 404 错误 - 端点不存在\n";
        echo "Ollama API 端点应该是:\n";
        echo "- /api/chat (推荐，支持对话)\n";
        echo "- /api/generate (旧版本)\n";
    } elseif ($httpCode === 0) {
        echo "💡 无法连接到服务器\n";
        echo "请检查:\n";
        echo "1. URL 是否正确\n";
        echo "2. 服务器是否可访问\n";
        echo "3. 防火墙设置\n";
    }
    exit(1);
}

// 解析响应
$responseData = json_decode($response, true);

if (!$responseData) {
    echo "❌ 响应解析失败 - 不是有效的 JSON\n\n";
    echo "原始响应:\n";
    echo substr($response, 0, 500) . "\n";
    exit(1);
}

echo "✅ 连接成功！\n\n";
echo "完整响应:\n";
echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 提取 AI 回复
$aiResponse = '';
if (isset($responseData['message']['content'])) {
    $aiResponse = $responseData['message']['content'];
} elseif (isset($responseData['response'])) {
    $aiResponse = $responseData['response'];
}

if (!empty($aiResponse)) {
    echo "==========================================\n";
    echo "💬 AI 回复:\n";
    echo "==========================================\n\n";
    echo $aiResponse . "\n\n";
}

echo "✅ 测试完成！你的 Ollama API 工作正常。\n";
?>

