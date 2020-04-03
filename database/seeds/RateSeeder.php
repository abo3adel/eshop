<?php

use App\Product;
use App\Rate;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Faker\Factory;

class RateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        $users_ids = ((User::all())->pluck('id'))->toArray();
        $f = Factory::create();
        DB::beginTransaction();
        (Product::all())->each(function (Product $p) use ($users_ids, $f) {
            for ($i = 0; $i < mt_rand(3, 8); $i++) {
                Rate::create([
                    'user_id' => Arr::random($users_ids),
                    'product_id' => $p->id,
                    'rate' => mt_rand(0, 5),
                    'message' => $f->text(254)
                ]);
            }
        });
         DB::commit();
    }
}