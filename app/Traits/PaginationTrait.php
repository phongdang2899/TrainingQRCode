<?php
namespace App\Traits;

trait PaginationTrait {

    /**
     * Calculating offset by perPage and currentPage
     * @param integer $perPage
     * @param integer $currentPage
     * @return integer
     */
    public function calOffset($perPage, $currentPage)
    {
        $maxPerPage = config('constants.pagination.max_per_page');
        if($perPage > $maxPerPage || $perPage < 1 || $currentPage < 1){
            return false;
        }
        $offset = ($currentPage - 1) * $perPage;
        return $offset;
    }
}
?>