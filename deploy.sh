#!/bin/bash

# 배포 스크립트
echo "🚀 STN MailCenter CI4 배포 시작..."

# 개발 서버 정보
DEV_SERVER="192.168.0.30"
DEV_USER="coder"  # 실제 사용자명으로 변경
DEV_PATH="/home/coder/php/stn_mailcenter_ci4"  # 개발 서버 경로

# 로컬 프로젝트 경로
LOCAL_PATH="/home/moogfox/php/STN_MAILCENTER/stn_mailcenter_ci4"

echo "📦 파일 복사 중..."
# SCP로 파일 복사 (제외할 파일들)
rsync -avz --exclude='.git' \
           --exclude='writable/logs/*' \
           --exclude='writable/cache/*' \
           --exclude='vendor' \
           --exclude='.env' \
           $LOCAL_PATH/ $DEV_USER@$DEV_SERVER:$DEV_PATH/

echo "🔧 개발 서버에서 설정 중..."
# 개발 서버에서 실행할 명령어들
ssh $DEV_USER@$DEV_SERVER << 'EOF'
cd /home/coder/php/stn_mailcenter_ci4

# Composer 의존성 설치
composer install --no-dev --optimize-autoloader

# 권한 설정
chmod -R 755 writable/
chown -R www-data:www-data writable/

# 캐시 클리어
php spark cache:clear

echo "✅ 배포 완료!"
EOF

echo "🎉 배포가 완료되었습니다!"
