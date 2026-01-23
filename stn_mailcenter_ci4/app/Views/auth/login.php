<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <!-- Google reCAPTCHA v3 + v2 하이브리드 (v3 스크립트로 v2 위젯도 렌더링) -->
    <?php $recaptchaV3SiteKey = getenv('RECAPTCHA_V3_SITE_KEY') ?: ''; ?>
    <?php $recaptchaV2SiteKey = getenv('RECAPTCHA_V2_SITE_KEY') ?: ''; ?>
    <?php if ($recaptchaV3SiteKey): ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= esc($recaptchaV3SiteKey) ?>"></script>
    <?php endif; ?>
</head>
<body class="login-page">
    <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-gray-300 to-gray-400">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
            <div class="text-center mb-6 relative">
                <!-- 로고 - 정 가운데 배치 -->
                <?php if (!empty($subdomain['logo_path'])): ?>
                    <!-- 로고 이미지가 있는 경우 -->
                    <img src="<?= base_url($subdomain['logo_path']) ?>" alt="<?= esc($subdomain['name']) ?>" class="h-16 mx-auto mb-3 object-contain">
                <?php else: ?>
                    <!-- 로고가 없을 경우 기본 DaumData 로고 -->
                    <img src="<?= base_url('assets/images/logo/daumdata_logo_2.png') ?>" alt="<?= esc($subdomain['name']) ?>" class="h-16 mx-auto mb-3 object-contain">
                <?php endif; ?>
                <!-- <p class="text-gray-500 text-sm"><?= esc($subdomain['description']) ?></p> -->
                <!-- <?php if (!empty($subdomain['contact'])): ?>
                    <p class="text-gray-600 text-xs mt-2">
                        <span class="font-semibold">문의:</span> <?= esc($subdomain['contact']) ?>
                        <?php if (!empty($subdomain['email'])): ?>
                            | <?= esc($subdomain['email']) ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?> -->
            </div>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mb-5">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-semibold text-red-800 mb-1"><?= esc($error) ?></h3>
                            <?php if (isset($error_detail)): ?>
                                <div class="text-sm text-red-700 mt-2 leading-relaxed">
                                    <?= esc($error_detail) ?>
                                </div>
                            <?php endif; ?>
                            <div class="mt-3 text-xs text-red-600">
                                <strong>해결 방법:</strong>
                                <ul class="list-disc list-inside mt-1 space-y-1">
                                    <?php if (strpos($error, '아이디 또는 비밀번호') !== false): ?>
                                        <li>아이디와 비밀번호를 다시 확인해주세요.</li>
                                        <li>대소문자와 특수문자를 정확히 입력했는지 확인하세요.</li>
                                        <li>비밀번호를 잊으셨다면 시스템 관리자에게 문의하세요.</li>
                                    <?php elseif (strpos($error, '서브도메인') !== false || strpos($error, '접근 권한') !== false): ?>
                                        <li>올바른 서브도메인 주소로 접속했는지 확인하세요.</li>
                                        <li>계정이 해당 서브도메인에 속해있는지 확인하세요.</li>
                                        <li>접근 권한이 필요하다면 시스템 관리자에게 요청하세요.</li>
                                    <?php elseif (strpos($error, '고객사 정보') !== false): ?>
                                        <li>계정에 고객사 정보가 등록되어 있는지 확인하세요.</li>
                                        <li>시스템 관리자에게 계정 정보 확인을 요청하세요.</li>
                                    <?php else: ?>
                                        <li>문제가 지속되면 시스템 관리자에게 문의하세요.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?= form_open('auth/processLogin', ['class' => 'space-y-3', 'id' => 'loginForm', 'data-ajax' => 'true']) ?>
                <!-- reCAPTCHA v3 토큰 -->
                <input type="hidden" name="recaptcha_token" id="recaptchaToken" value="">
                <!-- reCAPTCHA v2 토큰 -->
                <input type="hidden" name="recaptcha_v2_token" id="recaptchaV2Token" value="">

                <!-- reCAPTCHA v2 위젯 (5회 이상 실패 시 표시) -->
                <div id="recaptchaV2Wrapper" class="hidden mb-3">
                    <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-2">
                        <p class="text-xs text-yellow-800 mb-2">로그인 시도가 여러 번 실패했습니다. 아래 체크박스를 클릭해주세요.</p>
                        <div id="recaptchaV2Widget"></div>
                    </div>
                </div>

                <?php if (!$is_subdomain && !empty($api_list)): ?>
                <!-- 메인도메인에서 회사 선택 (api_list 사용) -->
                <div class="flex gap-2 mb-3">
                    <input type="text" name="selected_api_idx" id="selectedApiIdx" 
                           class="bg-white text-gray-700 border border-gray-300 px-3 py-2 rounded text-sm font-semibold focus:outline-none focus:ring-1 focus:ring-gray-500 transition-colors flex-1" 
                           placeholder="회사 코드 입력">
                    <button type="button" id="customerSearchButtonMain" onclick="openCustomerSearchPopupMain()" class="bg-gray-100 text-gray-700 border border-gray-300 py-2 px-4 rounded text-sm font-semibold hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500 transition-colors whitespace-nowrap">
                        고객확인
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($api_list) && $is_subdomain): ?>
                <!-- 서브도메인에서 API 선택 + 고객검색 버튼 (기존 기능) -->
                <div class="flex gap-2 mb-3">
                    <input type="text" id="apiSelect" 
                           class="bg-white text-gray-700 border border-gray-300 px-3 py-2 rounded text-sm font-semibold focus:outline-none focus:ring-1 focus:ring-gray-500 transition-colors flex-1" 
                           placeholder="회사 코드 입력" 
                           value="<?= esc(isset($api_idx) ? $api_idx : '') ?>">
                    <button type="button" id="customerSearchButton" onclick="openCustomerSearchPopupMain()" class="bg-gray-100 text-gray-700 border border-gray-300 py-2 px-4 rounded text-sm font-semibold hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500 transition-colors whitespace-nowrap">
                        고객확인
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- 로그인 타입 선택 (모두 다음데이터 로그인으로 통일, 숨김 처리) -->
                <input type="hidden" name="login_type" value="daumdata">
                
                <div class="flex gap-2 items-stretch" id="loginFieldsContainer">
                    <div class="flex-1 space-y-3">
                        <div>
                            <input type="text" id="username" name="username" value="<?= old('username') ?>" placeholder="아이디" required class="w-full px-3 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <input type="password" id="password" name="password" placeholder="비밀번호" required class="w-full px-3 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <button type="submit" id="loginButton" class="bg-gray-100 text-gray-700 border border-gray-300 px-4 rounded text-sm font-semibold hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500 transition-colors whitespace-nowrap h-full">
                        로그인
                    </button>
                </div>
            <?= form_close() ?>
            
            <!-- 개인정보처리방침 링크 -->
            <div class="text-center mt-4 pt-4 border-t border-gray-200">
                <a href="#" onclick="openPrivacyPopup(); return false;" class="text-xs text-gray-500 hover:text-gray-700 underline">
                    개인정보처리방침
                </a>
            </div>
            
            <!-- <div class="bg-blue-50 text-blue-800 p-3 rounded mt-4 text-xs">
                <strong>데모 계정:</strong><br>
                아이디: admin<br>
                비밀번호: admin
            </div> -->
        </div>
        
        <!-- QR코드 - 로그인 패널 바로 밑에 떠있는 형태 -->
        <?php if (!empty($qr_code)): ?>
        <div class="mt-6 flex justify-center">
            <img src="<?= esc($qr_code) ?>" alt="QR Code" style="width: 150px; height: 150px; display: block; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1); border-radius: 8px; background: white; padding: 8px;">
        </div>
        <?php endif; ?>
    </div>

    <!-- 개인정보처리방침 레이어팝업 -->
    <div id="privacyPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 overflow-y-auto p-4">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] overflow-hidden my-auto flex flex-col">
            <!-- 헤더 -->
            <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                <h2 class="text-lg font-semibold text-gray-800">개인정보처리방침</h2>
                <button onclick="closePrivacyPopup()" class="text-gray-500 hover:text-gray-700 text-xl font-bold">&times;</button>
            </div>
            
            <!-- 본문 -->
            <div class="p-6 bg-white overflow-y-auto flex-1">
                <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">
제1조 총칙
1.	본 사이트는 귀하의 개인정보보호를 매우 중요시하며, 『정보통신망이용촉진등에관한법률』상의 개인정보보호 규정 및 정보통신부가 제정한 『개인정보보호지침』을 준수하고 있습니다.
2.	본 사이트는 개인정보보호방침을 통하여 귀하께서 제공하시는 개인정보가 어떠한 용도와 방식으로 이용되고 있으며 개인정보보호를 위해 어떠한 조치가 취해지고 있는지 알려드립니다.
3.	본 사이트는 개인정보보호방침을 홈페이지 첫 화면 하단에 공개함으로써 귀하께서 언제나 용이하게 보실 수 있도록 조치하고 있습니다.
4.	본 사이트는 개인정보취급방침을 개정하는 경우 웹사이트 공지사항(또는 개별공지)을 통하여 공지할 것입니다.

제2조 개인정보 수집에 대한 동의
귀하께서 본 사이트의 개인정보보호방침 또는 이용약관의 내용에 대해 「동의한다」버튼 또는 「동의하지 않는다」버튼을 클릭할 수 있는 절차를 마련하여, 「동의한다」버튼을 클릭하면 개인정보 수집에 대해 동의한 것으로 봅니다.

제3조 개인정보의 수집 및 이용목적
1.	본 사이트는 다음과 같은 목적을 위하여 개인정보를 수집하고 있습니다.
•	서비스제공을 위한 계약의 성립 : 본인식별 및 본인의사 확인 등
•	서비스의 이행 : 상품배송 및 대금결제
•	회원 관리 : 회원제 서비스 이용에 따른 본인확인, 개인 식별, 연령확인, 불만처리 등 민원처리
•	기타 새로운 서비스, 신상품이나 이벤트 정보 안내
2.	단, 이용자의 기본적 인권 침해의 우려가 있는 민감한 개인정보(인종 및 민족, 사상 및 신조, 출신지 및 본적지, 정치적 성향 및 범죄기록, 건강상태 및 성생활 등)는 수집하지 않습니다.

제4조 수집하는 개인정보 항목
본 사이트는 회원가입, 상담, 서비스 신청 등등을 위해 아래와 같은 개인정보를 수집하고 있습니다.
1.	수집항목 : 이름, 로그인ID , 비밀번호 , 퀵서비스 발송자 주소, 퀵서비스 수령자 주소, 전화번호 , 접속 로그 , 접속 IP 정보 , 퀵서비스 이용내역
2.	개인정보 수집방법 : 홈페이지(회원가입)

제5조 개인정보 자동수집 장치의 설치, 운영 및 그 거부에 관한 사항
본 사이트는 귀하에 대한 정보를 저장하고 수시로 찾아내는 '쿠키(cookie)'를 사용합니다. 쿠키는 웹사이트가 귀하의 컴퓨터 브라우저(넷스케이프, 인터넷 익스플로러 등)로 전송하는 소량의 정보입니다. 귀하께서 웹사이트에 접속을 하면 본 쇼핑몰의 컴퓨터는 귀하의 브라우저에 있는 쿠키의 내용을 읽고, 귀하의 추가정보를 귀하의 컴퓨터에서 찾아 접속에 따른 성명 등의 추가 입력 없이 서비스를 제공할 수 있습니다.
쿠키는 귀하의 컴퓨터는 식별하지만 귀하를 개인적으로 식별하지는 않습니다. 또한 귀하는 쿠키에 대한 선택권이 있습니다. 웹브라우저의 옵션을 조정함으로써 모든 쿠키를 다 받아들이거나, 쿠키가 설치될 때 통지를 보내도록 하거나, 아니면 모든 쿠키를 거부할 수 있는 선택권을 가질 수 있습니다.
1.	쿠키 등 사용 목적 : 이용자의 접속 빈도나 방문 시간 등을 분석, 이용자의 취향과 관심분야를 파악 및 자취 추적, 각종 이벤트 참여 정도 및 방문 회수 파악 등을 통한 타겟 마케팅 및 개인 맞춤 서비스 제공
2.	쿠키 설정 거부 방법 : 쿠키 설정을 거부하는 방법으로는 귀하가 사용하는 웹 브라우저의 옵션을 선택함으로써 모든 쿠키를 허용하거나 쿠키를 저장할 때마다 확인을 거치거나, 모든 쿠키의 저장을 거부할 수 있습니다.
3.	설정방법 예시 : 인터넷 익스플로어의 경우 → 웹 브라우저 상단의 도구 > 인터넷 옵션 > 개인정보
4.	단, 귀하께서 쿠키 설치를 거부하였을 경우 서비스 제공에 어려움이 있을 수 있습니다.

제6조 목적 외 사용 및 제3자에 대한 제공
1.	본 사이트는 귀하의 개인정보를 "개인정보의 수집목적 및 이용목적"에서 고지한 범위 내에서 사용하며, 동 범위를 초과하여 이용하거나 타인 또는 타기업·기관에 제공하지 않습니다.
2.	그러나 보다 나은 서비스 제공을 위하여 귀하의 개인정보를 제휴사에게 제공하거나 또는 제휴사와 공유할 수 있습니다. 개인정보를 제공하거나 공유할 경우에는 사전에 귀하께 제휴사가 누구인지, 제공 또는 공유되는 개인정보항목이 무엇인지, 왜 그러한 개인정보가 제공되거나 공유되어야 하는지, 그리고 언제까지 어떻게 보호·관리되는지에 대해 개별적으로 전자우편 및 서면을 통해 고지하여 동의를 구하는 절차를 거치게 되며, 귀하께서 동의하지 않는 경우에는 제휴사에게 제공하거나 제휴사와 공유하지 않습니다.
3.	또한 이용자의 개인정보를 원칙적으로 외부에 제공하지 않으나, 아래의 경우에는 예외로 합니다.
•	이용자들이 사전에 동의한 경우
•	법령의 규정에 의거하거나, 수사 목적으로 법령에 정해진 절차와 방법에 따라 수사기관의 요구가 있는 경우

제7조 개인정보의 열람 및 정정
1.	귀하는 언제든지 등록되어 있는 귀하의 개인정보를 열람하거나 정정하실 수 있습니다. 개인정보 열람 및 정정을 하고자 할 경우에는 "회원정보수정"을 클릭하여 직접 열람 또는 정정하거나, 개인정보관리책임자에게 E-mail로 연락하시면 조치하겠습니다.
2.	귀하가 개인정보의 오류에 대한 정정을 요청한 경우, 정정을 완료하기 전까지 당해 개��정보를 이용하지 않습니다.

제8조 개인정보 수집, 이용, 제공에 대한 동의철회
1.	회원가입 등을 통해 개인정보��� 수집, 이용, 제공에 대해 귀하께서 동의하신 내용을 귀하는 언제든지 철회하실 수 있습니다. 동의철회는 "마이페이지"의 "회원탈퇴(동의철회)"를 클릭하거나 개인정보관리책임자에게 E-mail등으로 연락하시면 즉시 개인정보의 삭제 등 필요한 조치를 하겠습니다.
2.	본 사이트는 개인정보의 수집에 대한 회원탈퇴(동의���회)를 개인정보 수집시와 동등한 방법 및 절차로 행사할 수 있도록 필요한 조치를 하겠습니다.

제9조 개인정보의 보유 및 이용기간
1.	원칙적으로, 개인정보 수집 및 이용목적이 달성된 후에는 해당 정보를 지체 없이 파기합니다. 단, 다음의 정보에 대해서는 아래의 이유로 명시한 기간 동안 보존합니다.
•	보존 항목 : 회원가입정보(이름, 전화번호, 수발신자 정보(이름,전화번호,주소))
•	보존 근거 : 퀵서비스 배송 분쟁시 업무처리를 위함
•	보존 기간 : 최대 2년
2.	그리고 상법 등 관계법령의 규정에 의하여 보존할 필요가 있는 경우 회사는 아래와 같이 관계법령에서 정한 일정한 기간 동안 거래 및 회원정보를 보관합니다.
•	보존 항목 : 계약 또는 청약철회 기록, 대금 결제 및 재화공급 기록, 불만 또는 분쟁처리 기록
•	보존 근거 : 전자상거래등에서의 소비자보호에 관한 법률 제6조 거래기록의 보존
•	보존 기간 : 계약 또는 청약철회 기록(5년), 대금 결제 및 재화공급 기록(5년), 불만 또는 분쟁처리 기록(3년)
3.	위 보유기간에도 불구하고 계속 보유하여야 할 필요가 있을 경우에는 귀하의 동의를 받겠습니다.

제10조 개인정보의 파기절차 및 방법
본 사이트는 원칙적으로 개인정보 수집 및 이용목적이 달성된 후에는 해당 정보를 지체없이 파기합니다. 파기절차 및 방법은 다음과 같습니다.
1.	파기절차 : 귀하가 회원가입 등을 위해 입력하신 정보는 목적이 달성된 후 별도의 DB로 옮겨져(종이의 경우 별도의 서류함) 내부 방침 및 기타 관련 법령에 의한 정보보호 사유에 따라(보유 및 이용기간 참조) 일정 기간 저장된 후 파기되어집니다. 별도 DB로 옮겨진 개인정보는 법률에 의한 경우가 아니고서는 보유되어지는 이외의 다른 목적으로 이용되지 않습니다.
2.	파기방법 : 전자적 파일형태로 저장된 개인정보는 기록을 재생할 수 없는 기술적 방법을 사용하여 삭제합니다.

제11조 아동의 개인정보 보호
1.	본 사이트는 만14세 미만 아동의 개인정보를 수집하는 경우 법정대리인의 동의를 받습니다.
2.	만14세 미만 아동의 법정대리인은 아동의 개인정보의 열람, 정정, 동의철회를 요청할 수 있으며, 이러한 요청이 있을 경우 본 사이트는 지체없이 필요한 조치를 취합니다.

제12조 개인정보 보호를 위한 기술적 대책
본 사이트는 귀하의 개인정보를 취급함에 있어 개인정보가 분실, 도난, 누출, 변조 또는 훼손되지 않도록 안전성 확보를 위하여 다음과 같은 기술적 대책을 강구하고 있습니다.
1.	귀하의 개인정보는 비밀번호에 의해 보호되며, 파일 및 전송 데이터를 암호화하거나 파일 잠금기능(Lock)을 사용하여 중요한 데이터는 별도의 보안기능을 통해 보호되고 있습니다.
2.	본 사이트는 백신프로그램을 이용하여 컴퓨터바이러스에 의한 피해를 방지하기 위한 조치를 취하고 있습니다. 백신프로그램은 주기적으로 업데이트되며 갑작스런 바이러스가 출현할 경우 백신이 나오는 즉시 이를 제공함으로써 개인정보가 침해되는 것을 방지하고 있습니다.
3.	해킹 등에 의해 귀하의 개인정보가 유출되는 것을 방지하기 위해, 외부로부터의 침입을 차단하는 장치를 이용하고 있습니다.

제13조 개인정보의 위탁처리
본 사이트는 서비스 향상을 위해서 귀하의 개인정보를 외부에 위탁하여 처리할 수 있습니다.
1.	개인정보의 처리를 위탁하는 경우에는 미리 그 사실을 귀하에게 고지하겠습니다.
2.	개인정보의 처리를 위탁하는 경우에는 위탁계약 등을 통하여 서비스제공자의 개인정보호 관련 지시엄수, 개인정보에 관한 비밀유지, 제3자 제공의 금지 및 사고시의 책임부담 등을 명확히 규정하고 당해 계약내용을 서면 또는 전자적으로 보관하겠습니다.

제14조 의견수렴 및 불만처리
1.	본 사이트는 개인정보보호와 관련하여 귀하가 의견과 불만을 제기할 수 있는 창구를 개설하고 있습니다. 개인정보와 관련한 불만이 있으신 분은 본 쇼핑몰의 개인정보 관리책임자에게 의견을 주시면 접수 즉시 조치하여 처리결과를 통보해 드립니다.
•	개인정보 보호책임자 성명 : 김민필
•	전화번호 : 1588-9960
•	이메일 : stn5@stntotal.com
2.	또는 개인정보침해에 대한 신고나 상담이 필요하신 경우에는 아래 기관에 문의하시기 바랍니다.
•	개인정보 침해신고센터(한국인터넷진흥원 운영) : (국번없이) 118 (privacy.kisa.or.kr)
•	개인정보 분쟁조정위원회(국번없이) : 1833-6972 (www.kopico.go.kr)
•	대검찰청 사이버범죄수사단 : 02-3480-3573 (www.spo.go.kr)
•	경찰청 사이버안전국 : (국번없이) 182 (cyberbureau.police.go.kr)

부  칙 시행일 등
1.	본 방침은 2022년 1월 1일부터 시행합니다.
                </div>
            </div>
            
            <!-- 하단 버튼 -->
            <div class="flex justify-center gap-2 p-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                <button onclick="closePrivacyPopup()" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500">
                    닫기
                </button>
            </div>
        </div>
    </div>

    <!-- 입점신청 레이어팝업 - Tailwind CSS -->
    <div id="registrationPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto mx-4">
            <!-- 헤더 -->
            <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                <h2 class="text-lg font-semibold text-gray-800">입점신청</h2>
                <button onclick="closeRegistrationPopup()" class="text-gray-500 hover:text-gray-700 text-xl font-bold">&times;</button>
            </div>
            
            <!-- 본문 -->
            <div class="p-4 bg-gray-50">
                <div id="messageContainer"></div>
                
                <?= form_open_multipart('store-registration/submit', ['class' => 'space-y-4', 'id' => 'registrationForm']) ?>
                    <!-- 기본 정보 섹션 -->
                    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm">
                        <h3 class="text-xs font-semibold text-gray-700 mb-1 py-1 px-2 bg-gray-100 rounded">기본 정보</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <div>
                                <select name="applicant_type" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" required>
                                    <option value="">신청자 타입 (예: 신규 회사)</option>
                                    <option value="new_company">신규 회사</option>
                                    <option value="existing_company_branch">기존 회사 지사</option>
                                    <option value="existing_company_agency">기존 회사 대리점</option>
                                </select>
                            </div>
                            <div>
                                <select name="hierarchy_level" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" required>
                                    <option value="">계층 레벨 (예: 본사)</option>
                                    <option value="head_office">본사</option>
                                    <option value="branch">지사</option>
                                    <option value="agency">대리점</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 회사 정보 섹션 -->
                    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm">
                        <h3 class="text-xs font-semibold text-gray-700 mb-1 py-1 px-2 bg-gray-100 rounded">회사 정보</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                            <div>
                                <input type="text" name="company_name" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="회사명 (예: DaumData)" required>
                            </div>
                            <div>
                                <input type="text" name="business_number" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="사업자등록번호 (예: 000-00-00000)" required>
                            </div>
                            <div>
                                <input type="text" name="business_type" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="업종 (예: 물류업)">
                            </div>
                            <div>
                                <input type="number" name="employee_count" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="직원 수 (예: 50명)" min="1">
                            </div>
                        </div>
                        <div>
                            <textarea name="company_address" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 min-h-[60px] resize-y" placeholder="회사 주소 (예: 서울시 강남구 테헤란로 123)" required></textarea>
                        </div>
                    </div>

                    <!-- 대표자 정보 섹션 -->
                    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm">
                        <h3 class="text-xs font-semibold text-gray-700 mb-1 py-1 px-2 bg-gray-100 rounded">대표자 정보</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <div>
                                <input type="text" name="representative_name" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="대표자명 (예: 홍길동)" required>
                            </div>
                            <div>
                                <input type="tel" name="representative_phone" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="대표자 연락처 (예: 010-0000-0000)" required>
                            </div>
                            <div>
                                <input type="email" name="representative_email" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="대표자 이메일 (예: ceo@company.com)" required>
                            </div>
                            <div>
                                <input type="number" name="annual_revenue" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="연매출액 (예: 1000000000원)" min="0">
                            </div>
                        </div>
                    </div>

                    <!-- 서비스 정보 섹션 -->
                    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm">
                        <h3 class="text-xs font-semibold text-gray-700 mb-1 py-1 px-2 bg-gray-100 rounded">서비스 정보</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                            <div>
                                <select name="primary_service_category" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" required>
                                    <option value="">주력 서비스 카테고리 (예: 퀵서비스)</option>
                                    <option value="quick">퀵서비스</option>
                                    <option value="parcel">택배서비스</option>
                                    <option value="life">생활서비스</option>
                                    <option value="general">일반서비스</option>
                                    <option value="special">특수서비스</option>
                                </select>
                            </div>
                            <div>
                                <input type="number" name="expected_monthly_orders" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="예상 월 주문량 (예: 100건)" min="1">
                            </div>
                            <div>
                                <input type="number" name="contract_period" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="희망 계약기간 (예: 12개월)" min="1">
                            </div>
                        </div>
                        <div>
                            <textarea name="special_requirements" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 min-h-[60px] resize-y" placeholder="특별 요구사항 (예: 24시간 서비스 필요)"></textarea>
                        </div>
                    </div>

                    <!-- 첨부파일 섹션 -->
                    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm">
                        <h3 class="text-xs font-semibold text-gray-700 mb-1 py-1 px-2 bg-gray-100 rounded">첨부파일</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <div class="relative">
                                <input type="file" name="business_license_file" accept=".pdf,.jpg,.jpeg,.png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <label class="flex items-center gap-1 px-2 py-1 text-xs border border-gray-300 rounded bg-gray-50 cursor-pointer hover:bg-gray-100">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    사업자등록증 (예: business_license.pdf)
                                </label>
                            </div>
                            <div class="relative">
                                <input type="file" name="company_profile_file" accept=".pdf,.jpg,.jpeg,.png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <label class="flex items-center gap-1 px-2 py-1 text-xs border border-gray-300 rounded bg-gray-50 cursor-pointer hover:bg-gray-100">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    회사 소개서 (예: company_profile.pdf)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 버튼 영역 -->
                    <div class="flex justify-center gap-2 pt-2">
                        <button type="button" onclick="closeRegistrationPopup()" class="px-3 py-1.5 text-xs font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500">
                            취소
                        </button>
                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500 flex items-center gap-1">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            신청하기
                        </button>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <!-- 서브도메인 로그인 실패 레이어팝업 -->
    <div id="loginErrorPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 overflow-y-auto p-4">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md my-auto">
            <!-- 헤더 -->
            <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-red-50 rounded-t-lg">
                <h2 class="text-lg font-semibold text-red-800">로그인 실패</h2>
                <button onclick="closeLoginErrorPopup()" class="text-red-500 hover:text-red-700 text-xl font-bold">&times;</button>
            </div>
            
            <!-- 본문 -->
            <div class="p-6 bg-white">
                <div class="flex items-start mb-4">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 id="loginErrorTitle" class="text-base font-semibold text-gray-800 mb-2"></h3>
                        <p id="loginErrorDetail" class="text-sm text-gray-700 leading-relaxed"></p>
                    </div>
                </div>
            </div>
            
            <!-- 하단 버튼 -->
            <div class="flex justify-center gap-2 p-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                <button onclick="closeLoginErrorPopup()" class="px-4 py-2 text-sm font-semibold text-white bg-red-500 rounded hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500">
                    확인
                </button>
            </div>
        </div>
    </div>

    <script>
        // reCAPTCHA v2 설정
        const recaptchaV2SiteKey = '<?= esc(getenv('RECAPTCHA_V2_SITE_KEY') ?: '') ?>';
        let recaptchaV2WidgetId = null;
        let needsV2Challenge = false;

        // reCAPTCHA v2 성공 콜백
        function onRecaptchaV2Success(token) {
            document.getElementById('recaptchaV2Token').value = token;
        }

        // reCAPTCHA v2 만료 콜백
        function onRecaptchaV2Expired() {
            document.getElementById('recaptchaV2Token').value = '';
        }

        // v2 체크 필요 여부 확인 (서버에 AJAX 요청)
        async function checkNeedsV2Challenge(username) {
            if (!recaptchaV2SiteKey || !username) {
                return false;
            }

            try {
                const response = await fetch('<?= base_url('auth/checkRecaptcha') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ username: username })
                });

                const data = await response.json();
                return data.needs_v2 === true;
            } catch (error) {
                console.warn('checkNeedsV2Challenge error:', error);
                return false;
            }
        }

        // v2 위젯 표시 (v3 스크립트의 grecaptcha.render() 사용)
        function showV2Challenge() {
            const wrapper = document.getElementById('recaptchaV2Wrapper');
            if (wrapper) {
                wrapper.classList.remove('hidden');
                needsV2Challenge = true;

                // v2 위젯이 아직 렌더링되지 않았다면 렌더링
                if (recaptchaV2WidgetId === null && recaptchaV2SiteKey && typeof grecaptcha !== 'undefined') {
                    try {
                        grecaptcha.ready(function() {
                            recaptchaV2WidgetId = grecaptcha.render('recaptchaV2Widget', {
                                'sitekey': recaptchaV2SiteKey,
                                'callback': onRecaptchaV2Success,
                                'expired-callback': onRecaptchaV2Expired
                            });
                        });
                    } catch (e) {
                        console.warn('reCAPTCHA v2 render error:', e);
                    }
                }
            }
        }

        // 쿠키 관련 함수
        function setCookie(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = name + '=' + encodeURIComponent(value) + ';expires=' + expires.toUTCString() + ';path=/';
        }
        
        function getCookie(name) {
            const nameEQ = name + '=';
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
            }
            return null;
        }
        
        // 페이지 로드 시 쿠키에서 저장된 API 선택값 복원
        document.addEventListener('DOMContentLoaded', function() {
            const savedApiIdx = getCookie('selected_api_idx');
            if (savedApiIdx) {
                // 메인 도메인: selectedApiIdx select box
                const selectedApiIdxElement = document.getElementById('selectedApiIdx');
                if (selectedApiIdxElement) {
                    selectedApiIdxElement.value = savedApiIdx;
                }
                
                // 서브도메인: apiSelect select box
                const apiSelectElement = document.getElementById('apiSelect');
                if (apiSelectElement) {
                    apiSelectElement.value = savedApiIdx;
                }
            }
        });
        
        // API 선택 변경 시 쿠키에 저장
        document.addEventListener('DOMContentLoaded', function() {
            const selectedApiIdxElement = document.getElementById('selectedApiIdx');
            if (selectedApiIdxElement) {
                selectedApiIdxElement.addEventListener('change', function() {
                    if (this.value) {
                        setCookie('selected_api_idx', this.value, 30); // 30일간 유지
                    }
                });
            }
            
            const apiSelectElement = document.getElementById('apiSelect');
            if (apiSelectElement) {
                apiSelectElement.addEventListener('change', function() {
                    if (this.value) {
                        setCookie('selected_api_idx', this.value, 30); // 30일간 유지
                    }
                });
            }
        });
        
        function openRegistrationPopup() {
            document.getElementById('registrationPopup').classList.remove('hidden');
            document.getElementById('registrationPopup').classList.add('flex');
        }
        
        function closeRegistrationPopup() {
            document.getElementById('registrationPopup').classList.add('hidden');
            document.getElementById('registrationPopup').classList.remove('flex');
        }

        function openCustomerSearchPopupMain() {
            let selectedApiIdx = null;
            let selectedApiCode = null;
            
            // 메인 도메인: selectedApiIdx에서 값 가져오기 (input type=text)
            const selectedApiIdxElement = document.getElementById('selectedApiIdx');
            if (selectedApiIdxElement) {
                selectedApiIdx = selectedApiIdxElement.value.trim();
            }
            
            // 서브도메인: apiSelect에서 값 가져오기 (input type=text)
            if (!selectedApiIdx) {
                const apiSelect = document.getElementById('apiSelect');
                if (apiSelect) {
                    selectedApiIdx = apiSelect.value.trim();
                }
            }
            
            // 서브도메인이고 apiSelect에 값이 없으면 기본 api_idx, api_code 사용
            <?php if ($is_subdomain && isset($api_idx) && $api_idx): ?>
            if (!selectedApiIdx) {
                selectedApiIdx = '<?= esc($api_idx) ?>';
                <?php if (isset($api_code) && $api_code): ?>
                selectedApiCode = '<?= esc($api_code) ?>';
                <?php endif; ?>
            }
            <?php endif; ?>
            
            if (!selectedApiIdx) {
                alert('회사 코드를 입력해주세요.');
                return;
            }
            
            let url = '<?= base_url('search-company') ?>?api_idx=' + selectedApiIdx;
            if (selectedApiCode) {
                url += '&api_code=' + encodeURIComponent(selectedApiCode);
            }
            window.open(url, 'customerSearch', 'width=850,height=650,scrollbars=yes,resizable=yes');
        }
        
        // 쿠키 저장 함수
        function setCookie(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = name + '=' + encodeURIComponent(value) + ';expires=' + expires.toUTCString() + ';path=/';
        }
        
        // 쿠키 읽기 함수
        function getCookie(name) {
            const nameEQ = name + '=';
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
            }
            return null;
        }
        
        // 입력 필드에 쿠키 저장 이벤트 추가
        document.addEventListener('DOMContentLoaded', function() {
            const selectedApiIdxInput = document.getElementById('selectedApiIdx');
            const apiSelectInput = document.getElementById('apiSelect');
            
            // 메인 도메인 input에 이벤트 추가
            if (selectedApiIdxInput) {
                // 쿠키에서 값 불러오기
                const savedValue = getCookie('last_selected_api_idx');
                if (savedValue && !selectedApiIdxInput.value) {
                    selectedApiIdxInput.value = savedValue;
                }
                
                // 입력 시 쿠키에 저장
                selectedApiIdxInput.addEventListener('blur', function() {
                    if (this.value.trim()) {
                        setCookie('last_selected_api_idx', this.value.trim(), 30); // 30일 저장
                    }
                });
            }
            
            // 서브도메인 input에 이벤트 추가
            if (apiSelectInput) {
                // 쿠키에서 값 불러오기
                const savedValue = getCookie('last_selected_api_idx');
                if (savedValue && !apiSelectInput.value) {
                    apiSelectInput.value = savedValue;
                }
                
                // 입력 시 쿠키에 저장
                apiSelectInput.addEventListener('blur', function() {
                    if (this.value.trim()) {
                        setCookie('last_selected_api_idx', this.value.trim(), 30); // 30일 저장
                    }
                });
            }
        });

        function openPrivacyPopup() {
            const popup = document.getElementById('privacyPopup');
            popup.classList.remove('hidden');
            popup.classList.add('flex');
            // 팝업이 열릴 때 스크롤을 상단으로 이동
            popup.scrollTop = 0;
            // body 스크롤 방지
            document.body.style.overflow = 'hidden';
        }
        
        function closePrivacyPopup() {
            const popup = document.getElementById('privacyPopup');
            popup.classList.add('hidden');
            popup.classList.remove('flex');
            // body 스크롤 복원
            document.body.style.overflow = '';
        }

        function openLoginErrorPopup(title, detail) {
            const popup = document.getElementById('loginErrorPopup');
            document.getElementById('loginErrorTitle').textContent = title;
            document.getElementById('loginErrorDetail').textContent = detail;
            popup.classList.remove('hidden');
            popup.classList.add('flex');
            // 팝업이 열릴 때 스크롤을 상단으로 이동
            popup.scrollTop = 0;
            // body 스크롤 방지
            document.body.style.overflow = 'hidden';
        }
        
        function closeLoginErrorPopup() {
            const popup = document.getElementById('loginErrorPopup');
            popup.classList.add('hidden');
            popup.classList.remove('flex');
            // body 스크롤 복원
            document.body.style.overflow = '';
        }

        // 로그인 폼 AJAX 처리
        const loginForm = document.getElementById('loginForm');
        let isButtonClick = false; // 버튼 클릭 여부 추적
        
        // 로그인 버튼 클릭 시 플래그 설정
        document.getElementById('loginButton').addEventListener('click', function(e) {
            isButtonClick = true;
        });
        
        loginForm.addEventListener('submit', function(e) {
            // 버튼 클릭이 아닌 경우 (엔터키 등) submit 방지
            if (!isButtonClick) {
                e.preventDefault();
                return false;
            }
            
            e.preventDefault();
            isButtonClick = false; // 플래그 리셋
            
            const form = this;
            const selectedApiIdx = document.getElementById('selectedApiIdx');
            const apiSelect = document.getElementById('apiSelect');
            
            // 입력한 값 쿠키에 저장
            if (selectedApiIdx && selectedApiIdx.value.trim()) {
                setCookie('last_selected_api_idx', selectedApiIdx.value.trim(), 30);
            } else if (apiSelect && apiSelect.value.trim()) {
                setCookie('last_selected_api_idx', apiSelect.value.trim(), 30);
            }
            
            // 메인도메인에서 회사 코드 입력 필수 체크
            <?php if (!$is_subdomain && !empty($api_list)): ?>
            if (!selectedApiIdx || !selectedApiIdx.value.trim()) {
                alert('회사 코드를 입력해주세요.');
                if (selectedApiIdx) {
                    selectedApiIdx.focus();
                }
                return;
            }
            <?php endif; ?>
            
            const loginButton = document.getElementById('loginButton');
            const originalButtonText = loginButton.innerHTML;
            const username = document.getElementById('username').value.trim();

            // 로딩 상태
            loginButton.disabled = true;
            loginButton.innerHTML = '<svg class="animate-spin h-4 w-4 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> 로그인 중...';

            // reCAPTCHA v3 토큰 생성 후 로그인 요청
            const recaptchaSiteKey = '<?= esc(getenv('RECAPTCHA_V3_SITE_KEY') ?: '') ?>';

            // 로그인 AJAX 요청 함수
            async function submitLogin(recaptchaToken) {
                try {
                    if (recaptchaToken) {
                        document.getElementById('recaptchaToken').value = recaptchaToken;
                    }

                    const formData = new FormData(form);

                    // AJAX 요청
                    const response = await fetch('<?= base_url('auth/processLogin') ?>', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        // 로그인 성공
                        window.location.href = data.redirect || '/';
                    } else {
                        // 로그인 실패
                        loginButton.disabled = false;
                        loginButton.innerHTML = originalButtonText;

                        // v2 챌린지 필요 여부 확인 (실패 횟수 5회 이상)
                        if (data.needs_v2_challenge) {
                            showV2Challenge();
                        }

                        // 모든 오류를 레이어팝업으로 표시
                        openLoginErrorPopup(data.error || '로그인 실패', data.error_detail || '알 수 없는 오류가 발생했습니다.');
                    }
                } catch (error) {
                    loginButton.disabled = false;
                    loginButton.innerHTML = originalButtonText;
                    console.error('Login error:', error);
                    alert('로그인 중 오류가 발생했습니다. 다시 시도해주세요.');
                }
            }

            // v2 챌린지 체크 및 로그인 실행
            async function executeLogin() {
                // v2가 이미 필요하다고 표시된 경우, v2 토큰 체크
                if (needsV2Challenge) {
                    const v2Token = document.getElementById('recaptchaV2Token').value;
                    if (!v2Token) {
                        loginButton.disabled = false;
                        loginButton.innerHTML = originalButtonText;
                        alert('체크박스를 클릭하여 로봇이 아님을 확인해주세요.');
                        return;
                    }
                }

                // v3 토큰 생성 후 로그인
                if (recaptchaSiteKey && typeof grecaptcha !== 'undefined') {
                    try {
                        grecaptcha.ready(function() {
                            try {
                                grecaptcha.execute(recaptchaSiteKey, { action: 'login' })
                                    .then(function(token) {
                                        submitLogin(token);
                                    })
                                    .catch(function(error) {
                                        console.warn('reCAPTCHA execute error:', error);
                                        submitLogin('');
                                    });
                            } catch (executeError) {
                                console.warn('reCAPTCHA execute exception:', executeError);
                                submitLogin('');
                            }
                        });
                    } catch (readyError) {
                        console.warn('reCAPTCHA ready error:', readyError);
                        submitLogin('');
                    }
                } else {
                    submitLogin('');
                }
            }

            // 먼저 v2 필요 여부 체크 (아직 표시되지 않은 경우에만)
            if (!needsV2Challenge && recaptchaV2SiteKey) {
                checkNeedsV2Challenge(username).then(function(needs) {
                    if (needs) {
                        showV2Challenge();
                        loginButton.disabled = false;
                        loginButton.innerHTML = originalButtonText;
                        alert('로그인 시도가 여러 번 실패했습니다. 아래 체크박스를 클릭해주세요.');
                    } else {
                        executeLogin();
                    }
                });
            } else {
                executeLogin();
            }
        });
        
        // 모든 입력 필드에서 엔터키로 submit 방지
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = loginForm.querySelectorAll('input[type="text"], input[type="password"]');
            inputs.forEach(function(input) {
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.keyCode === 13) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
        });

        // ESC 키로 레이어팝업 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const loginErrorPopup = document.getElementById('loginErrorPopup');
                if (!loginErrorPopup.classList.contains('hidden')) {
                    closeLoginErrorPopup();
                }
                closePrivacyPopup();
            }
        });

        // 오버레이 클릭 시 레이어팝업 닫기
        document.getElementById('loginErrorPopup').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLoginErrorPopup();
            }
        });

        // 폼 제출 처리
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const messageContainer = document.getElementById('messageContainer');
            
            // 로딩 상태
            submitButton.disabled = true;
            submitButton.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> 처리중...';
            
            // AJAX 요청
            fetch('<?= base_url('store-registration/submit') ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageContainer.innerHTML = '<div class="p-3 mb-4 text-sm text-blue-800 bg-blue-100 border border-blue-200 rounded-md">' + data.message + '</div>';
                    form.reset();
                    setTimeout(() => {
                        closeRegistrationPopup();
                    }, 2000);
                } else {
                    messageContainer.innerHTML = '<div class="p-3 mb-4 text-sm text-red-800 bg-red-100 border border-red-200 rounded-md">' + data.message + '</div>';
                }
            })
            .catch(error => {
                messageContainer.innerHTML = '<div class="p-3 mb-4 text-sm text-red-800 bg-red-100 border border-red-200 rounded-md">오류가 발생했습니다. 다시 시도해주세요.</div>';
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> 신청하기';
            });
        });

        // 파일 선택 시 파일명 표시
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const label = this.nextElementSibling;
                if (this.files.length > 0) {
                    label.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> ' + this.files[0].name;
                } else {
                    label.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg> 파일 선택';
                }
            });
        });

        // 오버레이 클릭 시 팝업 닫기
        document.getElementById('registrationPopup').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRegistrationPopup();
            }
        });

        // ESC 키로 팝업 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRegistrationPopup();
            }
        });
    </script>
</body>
</html>
