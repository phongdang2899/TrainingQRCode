<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Province;
use App\Repositories\UserRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class CustomersImport implements ToCollection
{
    public $result = [];

    // use Importable;

    /**
     * @param array $row
     */

    public function collection(Collection $rows)
    {
        $successCount = 0;
        $successArr = $errorArr = [];
        $this->result = [
            'successCount' => $successCount,
            'errorArr' => $errorArr,
            'successArr' => $successArr
        ];
        $header = $rows->shift();
        if (count($header) < 7) {
            return;
        }
        $defFormat = [
            'phone_number',
            'first_name',
            'last_name',
            'address',
            'province',
            'id_card_number',
            'brand_name'
        ];
        if (array_diff_assoc($defFormat, $header->toArray())) {
            return;
        }
        $userId = (new UserRepository())->getUserIdDefault();
//        $arProvince = Province::all()->toArray();
        $arCustomerExist = Customer::all(['phone_number'])->toArray();
        if ($arCustomerExist) $arCustomerExist = array_column($arCustomerExist, 'phone_number');
        $arCustomerToInsert = [];
        foreach ($rows as $row) {
            $record = [
                'phone_number' => htmlspecialchars(trim($row[0])),
                'first_name' => ucwords(htmlspecialchars(trim($row[1]))),
                'last_name' => ucwords(htmlspecialchars(trim($row[2]))),
                'address' => htmlspecialchars(trim($row[3])),
                'province' => htmlspecialchars(trim($row[4])),
                'id_card_number' => htmlspecialchars(trim($row[5])),
                'brand_name' => htmlspecialchars(trim($row[6])),
                'status' => Customer::STATUS_ACTIVE,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ];
            if (
                $record['phone_number'] == null
                || $record['first_name'] == null
                || $record['last_name'] == null
                || $record['province'] == null
                || in_array($record['phone_number'], array_column($successArr, 'phone_number'))
            ) {
                $errorArr[] = $record;
                continue;
            }
            $phoneFormatted = preg_replace("/^\+84/", "0", $record['phone_number']);
            preg_match('/^0[^0][0-9]*/', $phoneFormatted, $phoneFormatted);
            if (!$phoneFormatted || strlen($phoneFormatted[0]) < 10) {
                $errorArr[] = $record;
                continue;
            }
            if (in_array($phoneFormatted[0], $arCustomerExist)) {
                $errorArr[] = $record;
                continue;
            }
            $arCustomerExist[] = $phoneFormatted[0];

            preg_match('/[0-9]/', $record['first_name'], $firstNameValid);
            preg_match('/[0-9]/', $record['last_name'], $lastNameValid);
            if ($firstNameValid || $lastNameValid) {
                $errorArr[] = $record;
                continue;
            }
            if (!empty($record['id_card_number'])) {
                preg_match('/[0-9]*/', $record['id_card_number'], $idNumber);
                if (!$idNumber) {
                    $errorArr[] = $record;
                    continue;
                }
            }
            $province = Province::whereRaw('LOWER(`name`) LIKE ? ', [strtolower($record['province']) . '%'])->first();
            if (empty($province)) {
                $errorArr[] = $record;
                continue;
            }
            $successCount++;
            $successArr[] = $record;
            unset($record['province']);
            $record['province_id'] = $province->id;
            $record['phone_number'] = $phoneFormatted[0];
            $arCustomerToInsert[] = $record;
            if ($successCount && ($successCount % 1000 == 0)) {
                DB::table((new Customer)->getTable())->insert($arCustomerToInsert);
                unset($arCustomerToInsert);
            }
        }
        if ($arCustomerToInsert) {
            DB::table((new Customer)->getTable())->insert($arCustomerToInsert);
        }
        $this->result = [
            'successCount' => $successCount,
            'errorArr' => $errorArr,
            'successArr' => $successArr
        ];
    }
}
