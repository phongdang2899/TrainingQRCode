<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('roles')->truncate();
        DB::table('roles')->insert([
            [
                'type' => config('constants.roles.admin.name'),
                'name' => 'Quản trị viên',
                'created_at' => Carbon::now()
            ],
            [
                'type' => config('constants.roles.manager.name'),
                'name' => 'Điều hành viên',
                'created_at' => Carbon::now()
            ],
            [
                'type' => config('constants.roles.member.name'),
                'name' => 'Thành viên',
                'created_at' => Carbon::now()
            ],
            [
                'type' => config('constants.roles.system.name'),
                'name' => 'Hệ thống',
                'created_at' => Carbon::now()
            ]
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
