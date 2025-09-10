<?php

// ملف اختبار لتعديل بيانات العضو
// يجب أولاً تسجيل الدخول للحصول على التوكن

$baseUrl = 'http://localhost:8000/api';

// بيانات تسجيل الدخول
$loginData = [
    'email' => 'test@example.com',
    'password' => 'password123'
];

// تسجيل الدخول
$loginOptions = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($loginData)
    ]
];

$loginContext = stream_context_create($loginOptions);
$loginResult = file_get_contents($baseUrl . '/login', false, $loginContext);

if ($loginResult === FALSE) {
    echo "خطأ في تسجيل الدخول\n";
    exit;
}

$loginResponse = json_decode($loginResult, true);
$token = $loginResponse['token'] ?? null;

if (!$token) {
    echo "لم يتم الحصول على التوكن\n";
    echo $loginResult . "\n";
    exit;
}

echo "تم تسجيل الدخول بنجاح\n";

// بيانات التحديث
$updateData = [
    'name' => 'Updated Name',
    'institution' => 'Updated Institution',
    'phone' => '1234567890',
    'dob' => '1990-01-01'
];

// تحديث البيانات
$updateOptions = [
    'http' => [
        'header' => "Content-type: application/json\r\nAuthorization: Bearer " . $token . "\r\n",
        'method' => 'PUT',
        'content' => json_encode($updateData)
    ]
];

$updateContext = stream_context_create($updateOptions);
$updateResult = file_get_contents($baseUrl . '/user', false, $updateContext);

if ($updateResult === FALSE) {
    echo "خطأ في تحديث البيانات\n";
} else {
    echo "نتيجة التحديث:\n";
    echo $updateResult . "\n";
}
