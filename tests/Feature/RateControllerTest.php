<?php

namespace Tests\Feature;

use App\Product;
use App\Rate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RateControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function testLoadProductRates()
    {
        /** @var \App\Product $p */
        $p = factory(Product::class)->create();

        $p->rates()->saveMany(
            factory(Rate::class, 13)->make()
        );

        $res = $this->get('/api/p/' . $p->slug . '/rates')
            ->assertOk()
            ->assertJsonCount(7, 'data') // pagination returns 7
            ->decodeResponseJson();

        // test pagination next page
        $this->get($res['next_page_url'])
            ->assertOk()
            ->assertJsonCount(6, 'data');
    }

    public function testOnlyLoggedInUserCanRateProduct()
    {
        /** @var \App\Product $p */
        $p = factory(Product::class)->create();

        $this->post('/api/p/' . $p->slug . '/rates')
            ->assertStatus(302);
    }

    public function testUserCanNotRateProductWithInvalidData()
    {
        // $this->withoutExceptionHandling();
        /** @var \App\Product $p */
        $p = factory(Product::class)->create();

        /** @var \App\User $user */
        $user = $this->signIn();

        $url = '/api/p/' . $p->slug . '/rates';

        $this->post($url, [])
            ->assertStatus(302)
            ->assertSessionHasErrors('rate')
            ->assertSessionDoesntHaveErrors('message');

        $this->post($url, [
            'rate' => 'a'
        ])->assertSessionHasErrors('rate');

        $this->post($url, [
            'rate' => 2.5,
            'message' => 'asd'
        ])->assertStatus(302)
            ->assertSessionHasErrors('message');

        $this->post($url, ['rate' => 6])
            ->assertSessionHasErrors('rate');
    }

    public function testUserCanRateProduct()
    {
        // $this->withoutExceptionHandling();
        /** @var \App\Product $p */
        $p = factory(Product::class)->create();

        /** @var \App\User $user */
        $user = $this->signIn();

        $old = factory(Rate::class)->make([
            'product_id' => $p->id
        ]);

        $rate = $old->only(['rate', 'message']);

        $this->post('/api/p/' . $p->slug . '/rates', $rate)
            ->assertOk()
            ->assertJsonPath('obj.message', $old->message);

        $this->assertDatabaseHas('rates', $rate);
    }

    public function testOnlyRateOwnerCanUpdate()
    {
        $this->signIn();
        /** @var \App\Product $p */
        $p = factory(Product::class)->create();
        $rate = $p->rates()->save(factory(Rate::class)->make());

        $this->post(
            '/api/rates/up/' . $rate->id,
            $rate->only(['user_id', 'rate', 'message'])
        )->assertStatus(403);
    }

    public function testUserCanNotUpdateRateWithInvalidData()
    {
        $this->signIn();
        /** @var \App\Product $p */
        $p = factory(Product::class)->create();
        $rate = $p->rates()->save(factory(Rate::class)->make([
            'message' => 'asd'
        ]));

        $this->post(
            '/api/rates/up/' . $rate->id,
            $rate->only(['user_id', 'rate', 'message'])
        )->assertStatus(302)
            ->assertSessionHasErrors('message');
    }

    public function testRateCanBeUpdated()
    {
        /** @var \App\User $user */
        $user = $this->signIn();

        /** @var \App\Product $p */
        $p = factory(Product::class)->create();

        $newMessage = $this->faker->sentence;

        /** @var \App\Rate $rate */
        $rate = $p->rates()->save(
            factory(Rate::class)->make([
                'message' => 'wsadasdsad',
                'user_id' => $user->id
            ])
        );

        $this->assertDatabaseHas('rates', $rate->only('user_id'));

        $rate->message = $newMessage;

        $this->post(
            '/api/rates/up/' . $rate->id,
            $rate->only(['user_id', 'rate', 'message'])
        )->assertOk()
            ->assertJsonPath('obj.message', $rate->message);
    }
}
