<?php

// ملف اختبار للتسجيل
// يمكن تشغيله من سطر الأوامر: php test_registration.php

$url = 'http://localhost:8000/api/register';
$data = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'institution' => 'Test Institution'
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "خطأ في الاتصال بالخادم\n";
} else {
    echo "النتيجة:\n";
    echo $result . "\n";
}
