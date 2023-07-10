<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerRepository extends EloquentRepository
{
    /**
     * get model
     * @return string
     */
    public function getModel()
    {
        return Customer::class;
    }

    /**
     * get customer by phone
     * @param string $phoneNumber
     * @param bool $checkActive
     * @return mixed
     */
    public function checkPhoneEndUser(string $phoneNumber, $checkActive = true)
    {
        $customer = $this->_model->select('*')->where('phone_number', $phoneNumber);
        if ($checkActive) {
            $customer = $customer->where('status', Customer::STATUS_ACTIVE);
        }
        return $customer->first();
    }

    public function getCustomerResource(array $params)
    {
        $customers = $this->_model->select('*')->where('status', Customer::STATUS_PENDING);
        if ($params['search']) {
            $customers = $customers->where(function ($query) use ($params) {
                $query->where('first_name', 'LIKE', '%' . $params['search'] . '%')
                    ->orWhere('last_name', 'LIKE', '%' . $params['search'] . '%')
                    ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE '%{$params['search']}%'")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE '%{$params['search']}%'")
                    ->orWhere('phone_number', 'LIKE', '%' . $params['search'] . '%');
            });
        }
        if (isset($params['status'])) {
            $customers = $customers->where('status', $params['status']);
        }
        if (isset($params['start_date'])) {
            $customers = $customers->whereDate('created_at', '>=', $params['start_date']);
        }
        if (isset($params['end_date'])) {
            $customers = $customers->whereDate('created_at', '<=', $params['end_date']);
        }
        if ($params['export']) {
            $customers = $customers->latest()->get();
        } else {
            $customers = $customers->latest()->paginate($params['per_page']);
        }
        return $customers;
    }

    public function getImageByIdCard($idCardNumber, int $customerId)
    {
        return $this->_model->select('id', 'first_name', 'last_name', 'image', 'phone_number')
            ->where('id', '!=', $customerId)
            ->where('id_card_number', $idCardNumber)
            ->get()->toArray();
    }

    public function processUploadFile($file, string $pathStr)
    {
        $id = DB::select("SHOW TABLE STATUS LIKE 'customers'");
        $lastId = $id[0]->Auto_increment;
        if ($file == null) return false;
        $fileExt = $file->getClientOriginalExtension();
        $name = $lastId . '.' . $fileExt;
        $file->move($pathStr, $name);
        $path = $pathStr . $name;
        return $path;
    }

    public function createNewCustomer($input, string $pathStr = 'images/customers/id_card_number/')
    {
        if (isset($input['image'])) {
            $path = $this->processUploadFile($input['image'], $pathStr);
            $input['image'] = $path;
        }
        $customerCreated = $this->_model->create($input);
        if (!$customerCreated) {
            return false;
        }
        return $customerCreated;
    }

    public function getCustomerReport($params)
    {
        $campaignId = ($params['campaign_id'] > 0) ? $params['campaign_id'] : (new CampaignRepository())->getCampaignDefault();
        $customers = $this->_model->select('customers.*', 'codes.campaign_id',
            DB::raw('COUNT(codes.id) as num_code_activated'),
            DB::raw('SUM(codes.value) as value'))
            ->join('codes', 'codes.customer_id', 'customers.id');
        if ((int)$params['campaign_id'] != 0) {
            $customers->where('codes.campaign_id', $campaignId);
        }
        if (isset($params['search'])) {
            $customers = $customers->where(function ($query) use ($params) {
                $query->where('customers.first_name', 'LIKE', '%' . $params['search'] . '%')
                    ->orWhere('customers.last_name', 'LIKE', '%' . $params['search'] . '%')
                    ->orWhereRaw("CONCAT(customers.last_name, ' ', customers.first_name) LIKE '%{$params['search']}%'")
                    ->orWhereRaw("CONCAT(customers.first_name, ' ', customers.last_name) LIKE '%{$params['search']}%'")
                    ->orWhere('customers.phone_number', 'LIKE', '%' . $params['search'] . '%');
            });
        }
        if ($params['sort'] == config('constants.sort_by.asc')) {
            $customers = $customers->orderBy('num_code_activated');
        } elseif ($params['sort'] == config('constants.sort_by.desc')) {
            $customers = $customers->orderByDesc('num_code_activated');
        }
        if ($params['export']) {
            $customers = $customers->groupBy('customers.id')->latest()->get();
        } else {
            $customers = $customers->groupBy('customers.id')->latest()->paginate($params['per_page']);
        }
        return $customers;
    }

    public function getById(int $id)
    {
        return $this->_model->select('*')->where('id', $id)->first();
    }

    public function updateStatus($params)
    {
        return $this->_model->whereIn('id', $params['ids'])->update(['status' => $params['status']]);
    }
}
