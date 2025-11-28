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
     * 페이징 HTML 생성 (현재 스타일 유지)
     * list-pagination, pagination, nav-button, page-number, active 클래스 사용
     */
    public function render()
    {
        $totalPages = $this->getTotalPages();
        
        if ($totalPages <= 1) {
            return '';
        }

        return $this->renderFullPagination($totalPages);
    }
    
    /**
     * 페이징 HTML 생성 (현재 프로젝트 스타일)
     */
    public function renderWithCurrentStyle()
    {
        $totalPages = $this->getTotalPages();
        
        if ($totalPages <= 1) {
            return '';
        }
        
        $html = '<div class="list-pagination">';
        $html .= '<div class="pagination">';
        
        // 처음 버튼
        if ($this->currentPage > 1) {
            $firstUrl = $this->getPageUrl(1);
            $html .= '<a href="' . $firstUrl . '" class="nav-button">처음</a>';
        } else {
            $html .= '<span class="nav-button" style="opacity: 0.5; cursor: not-allowed;">처음</span>';
        }
        
        // 이전 버튼
        if ($this->currentPage > 1) {
            $prevUrl = $this->getPageUrl($this->currentPage - 1);
            $html .= '<a href="' . $prevUrl . '" class="nav-button">이전</a>';
        } else {
            $html .= '<span class="nav-button" style="opacity: 0.5; cursor: not-allowed;">이전</span>';
        }
        
        // 항상 5개 페이지 번호를 표시하도록 계산
        $showPages = 5;
        $halfPages = floor($showPages / 2);
        
        // 시작 페이지 계산
        if ($this->currentPage <= $halfPages + 1) {
            $startPage = 1;
        } elseif ($this->currentPage >= $totalPages - $halfPages) {
            $startPage = max(1, $totalPages - $showPages + 1);
        } else {
            $startPage = $this->currentPage - $halfPages;
        }
        
        // 끝 페이지 계산
        $endPage = min($totalPages, $startPage + $showPages - 1);
        
        // 실제 표시할 페이지 범위 재조정 (총 페이지가 5개 미만인 경우)
        if ($totalPages < $showPages) {
            $startPage = 1;
            $endPage = $totalPages;
        }
        
        // 페이지 번호들
        for ($i = $startPage; $i <= $endPage; $i++) {
            $isActive = $i == $this->currentPage;
            $pageUrl = $this->getPageUrl($i);
            $html .= '<a href="' . $pageUrl . '" class="page-number ' . ($isActive ? 'active' : '') . '">' . $i . '</a>';
        }
        
        // 다음 버튼
        if ($this->currentPage < $totalPages) {
            $nextUrl = $this->getPageUrl($this->currentPage + 1);
            $html .= '<a href="' . $nextUrl . '" class="nav-button">다음</a>';
        } else {
            $html .= '<span class="nav-button" style="opacity: 0.5; cursor: not-allowed;">다음</span>';
        }
        
        // 마지막 버튼
        if ($this->currentPage < $totalPages) {
            $lastUrl = $this->getPageUrl($totalPages);
            $html .= '<a href="' . $lastUrl . '" class="nav-button">마지막</a>';
        } else {
            $html .= '<span class="nav-button" style="opacity: 0.5; cursor: not-allowed;">마지막</span>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
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

        // 처음 버튼
        if ($this->currentPage > 1) {
            $html .= '<button onclick="goToPage(1)" class="nav-button px-3 py-1 text-sm font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500">처음</button>';
        } else {
            $html .= '<button class="nav-button px-3 py-1 text-sm font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500" disabled>처음</button>';
        }

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

        // 마지막 버튼
        if ($this->currentPage < $totalPages) {
            $html .= '<button onclick="goToPage(' . $totalPages . ')" class="nav-button px-3 py-1 text-sm font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500">마지막</button>';
        } else {
            $html .= '<button class="nav-button px-3 py-1 text-sm font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500" disabled>마지막</button>';
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
