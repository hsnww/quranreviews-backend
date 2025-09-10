<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #2c5530;
        }
        .header h1 {
            color: #2c5530;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            background-color: #2c5530;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #1e3a21;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>منصة مراجعة القرآن الكريم</h1>
        </div>
        
        <div class="content">
            <h2>مرحباً {{ $email }}</h2>
            
            <p>تلقينا طلباً لإعادة تعيين كلمة المرور لحسابك في منصة مراجعة القرآن الكريم.</p>
            
            <p>إذا كنت قد طلبت هذا التغيير، يرجى النقر على الرابط أدناه لإعادة تعيين كلمة المرور:</p>
            
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="button">إعادة تعيين كلمة المرور</a>
            </div>
            
            <div class="warning">
                <strong>تنبيه:</strong> هذا الرابط صالح لمدة 60 دقيقة فقط. إذا انتهت صلاحية الرابط، يرجى طلب رابط جديد.
            </div>
            
            <p>إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذا البريد الإلكتروني.</p>
        </div>
        
        <div class="footer">
            <p>هذا البريد الإلكتروني تم إرساله تلقائياً من منصة مراجعة القرآن الكريم</p>
            <p>لا ترد على هذا البريد الإلكتروني</p>
        </div>
    </div>
</body>
</html>
