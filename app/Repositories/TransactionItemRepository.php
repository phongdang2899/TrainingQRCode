<?php
namespace App\Repositories;

use App\Models\TransactionItem;
use App\Repositories\EloquentRepository;

class TransactionItemRepository extends EloquentRepository
{

    /**
     * get model
     * @return string
     */
    public function getModel()
    {
        return TransactionItem::class;
    }
}