<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currentTimestamp = Carbon::now();
        DB::table('clients')->insert([
            'client_id' => 'client-a',
            'client_secret' => 'aB3$eF7!hJ9@kL2#mN5^',
            'created_at' => $currentTimestamp,
            'updated_at' => $currentTimestamp,
        ]);
    }
}
