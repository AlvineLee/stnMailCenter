<?php

namespace App\Helpers;

class IpHelper
{
    /**
     * 클라이언트의 실제 공인 IP 주소를 가져옵니다.
     * 프록시, 로드밸런서 등을 고려하여 가장 정확한 IP를 반환합니다.
     */
    public static function getRealIpAddress()
    {
        // 1. X-Forwarded-For 헤더 확인 (가장 우선순위)
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
            if (self::isValidIp($ip)) {
                return $ip;
            }
        }

        // 2. X-Real-IP 헤더 확인 (Nginx 등에서 사용)
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = trim($_SERVER['HTTP_X_REAL_IP']);
            if (self::isValidIp($ip)) {
                return $ip;
            }
        }

        // 3. X-Client-IP 헤더 확인
        if (!empty($_SERVER['HTTP_X_CLIENT_IP'])) {
            $ip = trim($_SERVER['HTTP_X_CLIENT_IP']);
            if (self::isValidIp($ip)) {
                return $ip;
            }
        }

        // 4. CF-Connecting-IP 헤더 확인 (Cloudflare)
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
            if (self::isValidIp($ip)) {
                return $ip;
            }
        }

        // 5. 일반적인 REMOTE_ADDR
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = trim($_SERVER['REMOTE_ADDR']);
            if (self::isValidIp($ip)) {
                return $ip;
            }
        }

        return '127.0.0.1'; // 기본값
    }

    /**
     * IP 주소가 유효한지 확인합니다.
     */
    private static function isValidIp($ip)
    {
        // IPv4와 IPv6 모두 확인
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }

        // 사설 IP도 허용 (내부망에서 사용할 경우)
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        return false;
    }

    /**
     * 모든 IP 관련 헤더 정보를 반환합니다.
     */
    public static function getAllIpHeaders()
    {
        return [
            'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            'HTTP_X_REAL_IP' => $_SERVER['HTTP_X_REAL_IP'] ?? null,
            'HTTP_X_CLIENT_IP' => $_SERVER['HTTP_X_CLIENT_IP'] ?? null,
            'HTTP_CF_CONNECTING_IP' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
            'SERVER_ADDR' => $_SERVER['SERVER_ADDR'] ?? null,
            'HTTP_X_FORWARDED' => $_SERVER['HTTP_X_FORWARDED'] ?? null,
            'HTTP_X_CLUSTER_CLIENT_IP' => $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ?? null,
            'HTTP_FORWARDED_FOR' => $_SERVER['HTTP_FORWARDED_FOR'] ?? null,
            'HTTP_FORWARDED' => $_SERVER['HTTP_FORWARDED'] ?? null,
        ];
    }

    /**
     * 외부 서비스를 통해 공인 IP를 확인합니다.
     */
    public static function getPublicIpFromService()
    {
        $services = [
            'https://api.ipify.org',
            'https://ipinfo.io/ip',
            'https://icanhazip.com',
            'https://ifconfig.me/ip',
            'https://ipecho.net/plain'
        ];

        foreach ($services as $service) {
            try {
                $ip = trim(file_get_contents($service));
                if (self::isValidIp($ip)) {
                    return $ip;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * IP 주소가 사설 IP인지 확인합니다.
     */
    public static function isPrivateIp($ip)
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * IP 주소의 위치 정보를 가져옵니다 (선택사항)
     */
    public static function getIpLocation($ip)
    {
        try {
            $response = file_get_contents("http://ip-api.com/json/{$ip}");
            $data = json_decode($response, true);
            
            if ($data && $data['status'] === 'success') {
                return [
                    'country' => $data['country'] ?? '',
                    'region' => $data['regionName'] ?? '',
                    'city' => $data['city'] ?? '',
                    'isp' => $data['isp'] ?? '',
                    'org' => $data['org'] ?? ''
                ];
            }
        } catch (\Exception $e) {
            // 에러 무시
        }

        return null;
    }
}
