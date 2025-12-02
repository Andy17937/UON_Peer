<?php
/**
 * Ollama API 连接测试脚本
 * 用于测试本地映射到公网的 Ollama API 是否可以正常访问
 */

// 从数据库读取配置
require_once 'config/database.php';

echo "<h2>🔍 Ollama API 连接测试</h2>";
echo "<hr>";

// 1. 检查数据库中的配置
echo "<h3>📋 步骤 1: 检查数据库配置</h3>";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE category = 'ai' OR setting_key LIKE 'ai_%'");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>配置项</th><th>值</th></tr>";
    
    $config = [];
    foreach ($settings as $setting) {
        $key = $setting['setting_key'];
        $value = $setting['setting_value'];
        $config[$key] = $value;
        
        // 隐藏 API Key
        $displayValue = ($key === 'ai_api_key' && !empty($value)) ? '***' . substr($value, -4) : $value;
        echo "<tr><td><strong>{$key}</strong></td><td>{$displayValue}</td></tr>";
    }
    echo "</table>";
    
    $provider = $config['ai_provider'] ?? 'openai';
    $endpoint = $config['ai_api_endpoint'] ?? '';
    $model = $config['ai_model'] ?? 'llama2';
    $enabled = $config['ai_enabled'] ?? '0';
    
    echo "<p>✅ 数据库配置读取成功</p>";
    echo "<p><strong>当前 AI 提供商:</strong> {$provider}</p>";
    echo "<p><strong>API 端点:</strong> {$endpoint}</p>";
    echo "<p><strong>模型:</strong> {$model}</p>";
    echo "<p><strong>AI 功能状态:</strong> " . ($enabled === '1' ? '✅ 已启用' : '❌ 未启用') . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 数据库错误: " . $e->getMessage() . "</p>";
    exit;
}

echo "<hr>";

// 2. 测试 Ollama API 连接
echo "<h3>🔌 步骤 2: 测试 Ollama API 连接</h3>";

if (empty($endpoint)) {
    echo "<p style='color: red;'>❌ 错误: API 端点未配置！</p>";
    echo "<p>请在管理后台配置 Ollama API 端点，例如：</p>";
    echo "<ul>";
    echo "<li>本地: <code>http://localhost:11434/api/chat</code></li>";
    echo "<li>公网映射: <code>https://your-domain.com/api/chat</code></li>";
    echo "</ul>";
    exit;
}

echo "<p>📡 正在连接到: <strong>{$endpoint}</strong></p>";

// 准备测试请求
$testData = [
    'model' => $model,
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'Hello! Please respond with "Connection successful!" in both English and Chinese.']
    ],
    'stream' => false
];

echo "<p>📤 发送的请求数据:</p>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

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
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// 捕获详细错误信息
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlInfo = curl_getinfo($ch);

curl_close($ch);

// 读取详细日志
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
fclose($verbose);

echo "<hr>";
echo "<h3>📥 步骤 3: 响应结果</h3>";

echo "<p><strong>HTTP 状态码:</strong> {$httpCode}</p>";
echo "<p><strong>响应时间:</strong> {$duration} ms</p>";

if (!empty($curlError)) {
    echo "<p style='color: red;'><strong>❌ cURL 错误:</strong> {$curlError}</p>";
    echo "<details><summary>详细连接信息</summary><pre>" . print_r($curlInfo, true) . "</pre></details>";
    echo "<details><summary>详细日志</summary><pre>" . htmlspecialchars($verboseLog) . "</pre></details>";
    
    echo "<hr>";
    echo "<h3>💡 可能的解决方案:</h3>";
    echo "<ol>";
    echo "<li><strong>检查 Ollama 是否运行:</strong> 在本地运行 <code>ollama list</code> 确认服务正常</li>";
    echo "<li><strong>检查公网映射:</strong> 确认你的内网穿透工具（如 frp, ngrok）正在运行</li>";
    echo "<li><strong>检查端口:</strong> Ollama 默认端口是 11434</li>";
    echo "<li><strong>检查防火墙:</strong> 确保防火墙允许访问</li>";
    echo "<li><strong>测试端点:</strong> 在浏览器或 Postman 中访问你的公网地址</li>";
    echo "</ol>";
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    echo "<p style='color: orange;'><strong>⚠️ HTTP 错误:</strong> 状态码 {$httpCode}</p>";
    echo "<p><strong>响应内容:</strong></p>";
    echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre>";
    
    if ($httpCode === 404) {
        echo "<p style='color: red;'>❌ 404 错误 - 端点不存在</p>";
        echo "<p>请确认 API 端点是否正确。Ollama 的正确端点应该是:</p>";
        echo "<ul>";
        echo "<li><code>http://localhost:11434/api/chat</code> (本地)</li>";
        echo "<li><code>http://localhost:11434/api/generate</code> (旧版本)</li>";
        echo "</ul>";
    }
    exit;
}

// 解析响应
$responseData = json_decode($response, true);

if (!$responseData) {
    echo "<p style='color: red;'>❌ 响应解析失败 - 不是有效的 JSON</p>";
    echo "<p><strong>原始响应:</strong></p>";
    echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre>";
    exit;
}

echo "<p style='color: green;'><strong>✅ 连接成功！</strong></p>";
echo "<p><strong>完整响应:</strong></p>";
echo "<pre>" . json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

// 提取 AI 回复
$aiResponse = '';
if (isset($responseData['message']['content'])) {
    $aiResponse = $responseData['message']['content'];
} elseif (isset($responseData['response'])) {
    $aiResponse = $responseData['response'];
} elseif (isset($responseData['choices'][0]['message']['content'])) {
    $aiResponse = $responseData['choices'][0]['message']['content'];
}

if (!empty($aiResponse)) {
    echo "<hr>";
    echo "<h3>💬 AI 回复内容:</h3>";
    echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px; border-left: 4px solid #4CAF50;'>";
    echo nl2br(htmlspecialchars($aiResponse));
    echo "</div>";
}

echo "<hr>";
echo "<h3>✅ 测试完成</h3>";
echo "<p>你的 Ollama API 连接正常！可以在系统中使用了。</p>";
echo "<p><a href='admin.html'>返回管理后台</a></p>";
?>

