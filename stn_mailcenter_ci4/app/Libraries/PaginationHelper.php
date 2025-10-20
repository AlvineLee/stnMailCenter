<?php

namespace App\Libraries;

class PaginationHelper
{
    protected $totalItems;
    protected $itemsPerPage;
    protected $currentPage;
    protected $baseUrl;
    protected $queryParams;

    public function __construct($totalItems, $itemsPerPage = 10, $currentPage = 1, $baseUrl = '', $queryParams = [])
    {
        $this->totalItems = $totalItems;
        $this->itemsPerPage = $itemsPerPage;
        $this->currentPage = max(1, $currentPage);
        $this->baseUrl = $baseUrl;
        $this->queryParams = $queryParams;
    }

    /**
     * 페이징 HTML 생성
     */
    public function render()
    {
        $totalPages = $this->getTotalPages();
        
        if ($totalPages <= 1) {
            return $this->renderSimplePagination();
        }

        return $this->renderFullPagination($totalPages);
    }

    /**
     * 단일 페이지용 간단한 페이징
     */
    private function renderSimplePagination()
    {
        return '
        <div class="flex justify-center">
            <div class="flex items-center gap-2">
                <button class="nav-button px-3 py-1 text-sm font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500" disabled>
                    이전
                </button>
                <span class="page-number active w-8 h-8 flex items-center justify-center text-sm font-semibold text-white bg-gray-600 rounded-full">
                    1
                </span>
                <button class="nav-button px-3 py-1 text-sm font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500" disabled>
                    다음
                </button>
            </div>
        </div>';
    }

    /**
     * 전체 페이징 렌더링
     */
    private function renderFullPagination($totalPages)
    {
        $html = '<div class="flex justify-center">';
        $html .= '<div class="flex items-center gap-2">';

        // 이전 버튼
        if ($this->currentPage > 1) {
            $prevPage = $this->currentPage - 1;
            $html .= '<button onclick="goToPage(' . $prevPage . ')" class="nav-button px-3 py-1 text-sm font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500">이전</button>';
        } else {
            $html .= '<button class="nav-button px-3 py-1 text-sm font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500" disabled>이전</button>';
        }

        // 페이지 번호들
        $startPage = max(1, $this->currentPage - 2);
        $endPage = min($totalPages, $this->currentPage + 2);

        // 첫 페이지가 1이 아니면 ... 표시
        if ($startPage > 1) {
            $html .= '<button onclick="goToPage(1)" class="page-number w-8 h-8 flex items-center justify-center text-sm font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500">1</button>';
            if ($startPage > 2) {
                $html .= '<span class="px-2 text-gray-500">...</span>';
            }
        }

        // 페이지 번호들
        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i == $this->currentPage) {
                $html .= '<span class="page-number active w-8 h-8 flex items-center justify-center text-sm font-semibold text-white bg-gray-600 rounded-full">' . $i . '</span>';
            } else {
                $html .= '<button onclick="goToPage(' . $i . ')" class="page-number w-8 h-8 flex items-center justify-center text-sm font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500">' . $i . '</button>';
            }
        }

        // 마지막 페이지가 끝이 아니면 ... 표시
        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) {
                $html .= '<span class="px-2 text-gray-500">...</span>';
            }
            $html .= '<button onclick="goToPage(' . $totalPages . ')" class="page-number w-8 h-8 flex items-center justify-center text-sm font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500">' . $totalPages . '</button>';
        }

        // 다음 버튼
        if ($this->currentPage < $totalPages) {
            $nextPage = $this->currentPage + 1;
            $html .= '<button onclick="goToPage(' . $nextPage . ')" class="nav-button px-3 py-1 text-sm font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500">다음</button>';
        } else {
            $html .= '<button class="nav-button px-3 py-1 text-sm font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500" disabled>다음</button>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * 총 페이지 수 계산
     */
    public function getTotalPages()
    {
        return ceil($this->totalItems / $this->itemsPerPage);
    }

    /**
     * 현재 페이지의 시작 인덱스
     */
    public function getOffset()
    {
        return ($this->currentPage - 1) * $this->itemsPerPage;
    }

    /**
     * 페이징 정보 반환
     */
    public function getPaginationInfo()
    {
        $startItem = $this->getOffset() + 1;
        $endItem = min($this->getOffset() + $this->itemsPerPage, $this->totalItems);
        
        return [
            'total_items' => $this->totalItems,
            'items_per_page' => $this->itemsPerPage,
            'current_page' => $this->currentPage,
            'total_pages' => $this->getTotalPages(),
            'start_item' => $startItem,
            'end_item' => $endItem,
            'has_previous' => $this->currentPage > 1,
            'has_next' => $this->currentPage < $this->getTotalPages()
        ];
    }

    /**
     * 페이지 URL 생성
     */
    public function getPageUrl($page)
    {
        $params = array_merge($this->queryParams, ['page' => $page]);
        $queryString = http_build_query($params);
        
        return $this->baseUrl . ($queryString ? '?' . $queryString : '');
    }
}
