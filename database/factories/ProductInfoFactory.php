<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Product;
use App\ProductInfo;
use Faker\Generator as Faker;

$factory->define(ProductInfo::class, function (Faker $faker) {
    $info_arr = [
        // 'brand' => $faker->sentence,
        'package thickness' => $faker->randomFloat(4),
        'product weight' => $faker->randomFloat(4) . Arr::random(['Gram', 'Kg', 'Litre', 'Meter']),
        'package weight' => $faker->randomFloat(5) . Arr::random(['Gram', 'Kg', 'Litre', 'Meter']),
        'serial scan required' => false
    ];

    $randomVal = function () use ($faker) {
        return [
            $faker->randomDigit . Arr::random(['Gram', 'Kg', 'Litre', 'Meter']),
            $faker->unique()->sentence(4),
            $faker->boolean
        ];
    };

    for ($i = 0; $i < rand(25, 60); $i++) {
        $key = $faker->unique()->sentence(3);
        $value = Arr::random($randomVal());
        // check if this key is not already in info array
        // if (!isset($info_arr[$key])) {
        $info_arr[$key] = $value;
        // }
    }

    return [
        'product_id' => factory(Product::class),
        'info' => $info_arr
    ];
});
