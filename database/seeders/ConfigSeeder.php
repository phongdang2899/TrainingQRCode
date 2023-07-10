<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('configs')->truncate();
        DB::table('configs')->insert([
            // region Failed IP
            [
                'entity_id' => 'failed_ip',
                'entity_type' => 'limit',
                'value' => '5',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            [
                'entity_id' => 'failed_ip',
                'entity_type' => 'unblock_after',
                'value' => '180', // minutes
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            // endregion Failed IP

            // region OTP
            [
                'entity_id' => 'otp',
                'entity_type' => 'limit',
                'value' => '3',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            [
                'entity_id' => 'otp',
                'entity_type' => 'expired_after',
                'value' => '60', // seconds
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            [
                'entity_id' => 'otp',
                'entity_type' => 'unblock_after',
                'value' => '24', // hours
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],

            // region BrandName API
            [
                'entity_id' => 'sms_api',
                'entity_type' => 'cp_id',
                'value' => '1234',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            [
                'entity_id' => 'sms_api',
                'entity_type' => 'cp_name',
                'value' => 'abc',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            [
                'entity_id' => 'sms_api',
                'entity_type' => 'user',
                'value' => 'abc',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            [
                'entity_id' => 'sms_api',
                'entity_type' => 'pass',
                'value' => 'abc',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            [
                'entity_id' => 'sms_api',
                'entity_type' => 'brand_name',
                'value' => 'Vinh Tuong',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            [
                'entity_id' => 'sms_api',
                'entity_type' => 'msg_body',
                'value' => 'Ma OTP cua ban la: {OTP}. Ma co hieu luc trong {expired_time} phut. Vui long khong chia se ma OTP voi bat ky ai. Xin cam on!',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            // endregion BrandName API

            // region Fibo Topup API
            [
                'entity_id' => 'topup_api',
                'entity_type' => 'username',
                'value' => 'abc',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            [
                'entity_id' => 'topup_api',
                'entity_type' => 'password',
                'value' => 'abc',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            [
                'entity_id' => 'topup_api',
                'entity_type' => 'token',
                'value' => '',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            [
                'entity_id' => 'topup_api',
                'entity_type' => 'token_type',
                'value' => 'Bearer',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            [
                'entity_id' => 'topup_api',
                'entity_type' => 'token_expires_at',
                'value' => '2021-07-22 19:27:46',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            [
                'entity_id' => 'topup_api',
                'entity_type' => 'public_key_rsa',
                'value' => 'abc',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            [
                'entity_id' => 'topup_api',
                'entity_type' => 'telco',
                'value' => 'VTL', // code telco (Viettel: VTL, Mobi: VMS, Vina: VNP)
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            // endregion Fibo Topup API

            // region reCapcha (v2)
            [
                'entity_id' => 'reCapcha_v2',
                'entity_type' => 'public_key',
                'value' => '6LeIJp8bAAAAAGeU4fE25ACgheSjLXIBdriDf8he',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            [
                'entity_id' => 'reCapcha_v2',
                'entity_type' => 'private_key',
                'value' => '6LeIJp8bAAAAAMna04BesyI1XV6FAxRY1VgFibi0',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            // endregion reCapcha

            // region 3rd
            [
                'entity_id' => '3rd',
                'entity_type' => 'whitelist_ip',
                'value' => '172.19.0.1,123.25.114.56',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
            //  endregion 3rd
            [
                'entity_id' => 'code',
                'entity_type' => 'site_key',
                'value' => 'b3b5cb0a-9419-5772-ad37-55519bd76f10',
                'status' => '1',
                'created_by' => '1',
                'created_at' => Carbon::now()
            ],
        ]);
    }
}
