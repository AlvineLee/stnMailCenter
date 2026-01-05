<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <style>
        body {
            margin: 0;
            padding: 10px;
            font-family: "NanumSquare", sans-serif;
            font-size: 14px;
            color: #333;
            background: #fff;
        }
        .info-table {
            width: 95%;
            margin: 0 auto;
            border-collapse: collapse;
            border-bottom: 1px solid #ededed;
        }
        .info-table th {
            height: 40px;
            padding: 5px 0;
            text-align: center;
            background: #f8f8f8;
            border-top: 1px solid #ededed;
            border-left: 1px solid #ededed;
        }
        .info-table th:first-child {
            border-left: none;
        }
        .info-table td {
            height: 40px;
            padding: 0 10px 0 15px;
            text-align: center;
            background: #fff;
            border-top: 1px solid #ededed;
            border-left: 1px solid #ededed;
        }
        .info-table td:first-child {
            border-left: none;
        }
        .help-text {
            padding: 5px 0;
            border: none;
            text-align: left;
            font-size: 12px;
            color: #666;
        }
        .form-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
            border-bottom: 1px solid #ededed;
        }
        .form-table th {
            width: 140px;
            height: 40px;
            text-align: center;
            background: #f8f8f8;
            border-top: 1px solid #ededed;
            border-left: 1px solid #ededed;
        }
        .form-table th:first-child {
            border-left: none;
        }
        .form-table td {
            height: 40px;
            padding: 0 10px 0 15px;
            background: #fff;
            border-top: 1px solid #ededed;
            border-left: 1px solid #ededed;
        }
        .form-table td:first-child {
            border-left: none;
        }
        .form-table input[type="text"] {
            width: 170px;
            padding: 0 10px;
            line-height: 20px;
            border: 1px solid #ebebeb;
            border-top-color: #d9d9d9;
            border-left-color: #d9d9d9;
            background: #fff;
            vertical-align: top;
        }
        .button-area {
            height: 80px;
            text-align: center;
            padding-top: 20px;
        }
        .button-area input[type="button"] {
            padding: 4px 6px;
            cursor: pointer;
            font-size: 14px;
            border: 1px solid #ebebeb;
            border-bottom-color: #d9d9d9;
            border-right-color: #d9d9d9;
            background: #fff;
        }
        .button-area input[type="button"]:hover {
            background: #d8d8d8;
        }
        .error-message {
            color: #dc2626;
            padding: 10px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 4px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <table width="95%" border="0" align="center">
        <tr>
            <td>
                <br>
                <table class="info-table">
                    <tr>
                        <th height="40">고객명</th>
                        <th height="40">기준동</th>
                        <th height="40">전화번호 1</th>
                        <th height="40">전화번호 2</th>
                        <th height="40">부서명</th>
                        <th height="40">담당</th>
                    </tr>
                    <tr>
                        <td height="40"><?= esc($member_info['cust_name'] ?? '') ?></td>
                        <td height="40"><?= esc($member_info['dong_name'] ?? '') ?></td>
                        <td height="40"><?= esc(formatTel($member_info['tel_no1'] ?? '')) ?></td>
                        <td height="40"><?= esc(formatTel($member_info['tel_no2'] ?? '')) ?></td>
                        <td height="40"><?= esc($member_info['dept_name'] ?? '') ?></td>
                        <td height="40"><?= esc($member_info['charge_name'] ?? '') ?></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="help-text">
                선택된 위 정보로 회원가입을 원하시면 아래 아이디 패스워드를 지정하셔서 가입하시기 바랍니다.
            </td>
        </tr>
        <tr>
            <td>
                <form id="registerForm" method="post">
                    <input type="hidden" name="api_idx" value="<?= esc($api_idx) ?>">
                    <input type="hidden" name="ccode" value="<?= esc($c_code) ?>">
                    <table class="form-table">
                        <tr>
                            <th height="40">회원 아이디</th>
                            <th height="40">비밀번호</th>
                            <th height="40">비밀번호 확인</th>
                        </tr>
                        <tr>
                            <td height="40">
                                <input type="text" name="user_id" id="user_id" value="<?= esc($member_info['user_id'] ?? '') ?>" style="width:170px">
                            </td>
                            <td height="40">
                                <input type="password" name="password" id="password" value="" style="width:170px">
                            </td>
                            <td height="40">
                                <input type="password" name="password_confirm" id="password_confirm" value="" style="width:170px">
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr>
            <td class="button-area">
                <input type="button" value=" 회원등록 " onclick="submitForm()">
                &nbsp;&nbsp;
                <input type="button" value=" 돌아가기 " onclick="goBack()">
            </td>
        </tr>
    </table>

    <div id="errorMessage" class="error-message" style="display: none;"></div>

    <script>
        function submitForm() {
            const form = document.getElementById('registerForm');
            const userId = document.getElementById('user_id').value.trim();
            const password = document.getElementById('password').value.trim();
            const passwordConfirm = document.getElementById('password_confirm').value.trim();
            const errorDiv = document.getElementById('errorMessage');

            // 유효성 검사
            if (!userId) {
                errorDiv.textContent = '아이디를 입력하세요.';
                errorDiv.style.display = 'block';
                return;
            }

            if (!password || !passwordConfirm) {
                errorDiv.textContent = '비밀번호를 입력하세요.';
                errorDiv.style.display = 'block';
                return;
            }

            if (password.length < 4 || password.length > 20) {
                errorDiv.textContent = '비밀번호는 4자리 이상 20자리 이하로 입력하세요.';
                errorDiv.style.display = 'block';
                return;
            }

            if (password !== passwordConfirm) {
                errorDiv.textContent = '비밀번호와 비밀번호 확인이 일치하지 않습니다.';
                errorDiv.style.display = 'block';
                return;
            }

            errorDiv.style.display = 'none';

            // AJAX 요청
            const formData = new FormData(form);
            formData.append('user_id', userId);
            formData.append('password', password);
            formData.append('password_confirm', passwordConfirm);

            fetch('<?= base_url('search-company/doRegister') ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // 로그인 페이지로 리다이렉트 또는 팝업 닫기
                    if (window.opener) {
                        window.close();
                    } else {
                        window.location.href = '<?= base_url('auth/login') ?>';
                    }
                } else {
                    errorDiv.textContent = data.message || '회원 등록 실패';
                    errorDiv.style.display = 'block';
                }
            })
            .catch(error => {
                errorDiv.textContent = '오류가 발생했습니다: ' + error.message;
                errorDiv.style.display = 'block';
            });
        }

        function goBack() {
            const apiIdx = <?= esc($api_idx) ?>;
            window.location.href = '<?= base_url('search-company') ?>?api_idx=' + apiIdx;
        }

        function formatTel(tel) {
            if (!tel) return '';
            const cleaned = tel.replace(/[^0-9]/g, '');
            if (cleaned.length === 11) {
                return cleaned.substring(0, 3) + '-****-' + cleaned.substring(7);
            } else if (cleaned.length === 10) {
                return cleaned.substring(0, 3) + '-****-' + cleaned.substring(6);
            }
            return tel;
        }
    </script>
</body>
</html>

<?php
function formatTel($tel) {
    if (empty($tel)) return '';
    $cleaned = preg_replace('/[^0-9]/', '', $tel);
    if (strlen($cleaned) === 11) {
        return substr($cleaned, 0, 3) . '-****-' . substr($cleaned, 7);
    } else if (strlen($cleaned) === 10) {
        return substr($cleaned, 0, 3) . '-****-' . substr($cleaned, 6);
    }
    return $tel;
}
?>


