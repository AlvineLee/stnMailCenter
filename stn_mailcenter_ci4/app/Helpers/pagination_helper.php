<?php

if (!function_exists('calculatePagination')) {
    /**
     * 페이징 정보 계산 (공통 함수)
     * 
     * @param int $totalCount 전체 항목 수
     * @param int $page 현재 페이지
     * @param int $perPage 페이지당 항목 수
     * @return array 페이징 정보 배열
     */
    function calculatePagination($totalCount, $page, $perPage = 20)
    {
        $totalPages = ceil($totalCount / $perPage);
        
        return [
            'current_page' => $page,
            'per_page' => $perPage,
            'total_count' => $totalCount,
            'total_pages' => $totalPages,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages
        ];
    }
}

