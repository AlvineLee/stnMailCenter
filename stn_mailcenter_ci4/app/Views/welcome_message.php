<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STN Network - 로그인</title>
    <style>
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-logo .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            margin: 0 auto 15px;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .login-form .form-group {
            margin-bottom: 20px;
        }
        
        .login-form input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        
        .login-form input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }
        
        .error-message {
            background: #dbeafe;
            color: #1e40af;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .demo-info {
            background: #e6f3ff;
            color: #1e40af;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <div class="logo-icon">STN</div>
                <h1>STN Network</h1>
                <p style="color: #718096; margin-top: 5px;">ONE'CALL</p>
            </div>
            
            <?php if (session()->getFlashdata('error')): ?>
                <div class="error-message"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>
            
            <form class="login-form" method="POST" action="/auth/processLogin" autocomplete="off">
                <!-- 크롬 비밀번호 저장 방지를 위한 더미 필드 -->
                <input type="text" name="fake_username" style="display: none;" tabindex="-1" autocomplete="off">
                <input type="password" name="fake_password" style="display: none;" tabindex="-1" autocomplete="off">
                
                <div class="form-group">
                    <label for="username">아이디</label>
                    <input type="text" id="username" name="username" value="<?= old('username') ?>" required autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label for="password">비밀번호</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password">
                </div>
                
                <button type="submit" class="login-btn">로그인</button>
            </form>
            
            <div class="demo-info">
                <strong>데모 계정:</strong><br>
                아이디: admin<br>
                비밀번호: admin
            </div>
        </div>
    </div>
</body>
</html>