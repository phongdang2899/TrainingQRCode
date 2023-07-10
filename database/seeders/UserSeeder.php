<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
//        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->insert([
            [
                'username' => 'admin',
                'first_name' => 'Quản trị',
                'last_name' => 'Hệ thống',
                'gender' => 0,
                'phone_number' => '',
                'email' => 'admin@rewardpage.com',
                'role_id' => config('constants.roles.admin.key'),
                'status' => config('constants.status.user.active'),
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
                'created_at' => now(),
            ],
            [
                'username' => 'system',
                'first_name' => 'Third',
                'last_name' => 'Party',
                'gender' => 0,
                'phone_number' => '',
                'email' => 'system@rewardpage.com',
                'role_id' => config('constants.roles.system.key'),
                'status' => config('constants.status.user.active'),
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
                'created_at' => now(),
            ],
        ]);
        /*if (App::environment('local')) {
            User::factory(10)->create();
        }*/
    }
}
