<?php


namespace App\Services;

use App\Repositories\ConfigRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class CodeService
{
    /**
     * Generate Uuid value
     *
     * @param string $input
     *
     * @return \Ramsey\Uuid\UuidInterface
     */
    public static function getNamespace(string $input = 'http://localhost:8383')
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, $input);
    }

    /**
     * Generate reward code database
     *
     * @param Model $model
     * @param array $rewardInfo
     *
     * @return int
     */
    public static function generatesCode(Model $model, array $rewardInfo)
    {
        $configRepo = new ConfigRepository();
        $siteKey = $configRepo->getConfigByEntity('code', 'site_key');
        if (!$siteKey || empty($siteKey->value)) {
            return  false;
        }
        $quantity = (int)array_sum(array_column($rewardInfo, 'qty'));
        if ($quantity < 1) {
            return false;
        }
        $blockSave = 1000;
        $i = $count = 0;
        $codePool = $modelData = [];
        foreach ($rewardInfo as $info) {
            $model->value = $info['type'];
            for (; $i < $quantity; $i++) {
                $item = Uuid::uuid4()->toString();
                $code = Uuid::uuid5($siteKey->value, $item)->toString();
                $code = str_replace('-', '', $code);
                $code = substr($code, 0, 12);
                if (in_array($code, $codePool)) {
                    $i--;
                    continue;
                }
                $codePool[] = $model->code = $code;
                $modelData[] = $model->toArray();
                if (($i % $blockSave) == 0) {
                    DB::table($model->getTable())->insert($modelData);
                    $modelData = [];
                }
                if ($count == ($info['qty'] - 1)) {
                    $count = 0;
                    break;
                }
                $count++;
            }
        }
        DB::table($model->getTable())->insert($modelData);
        return count($codePool);
    }
}
