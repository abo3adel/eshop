<?php

namespace Tests\Feature;

use App\Category;
use App\Product;
use App\ProductInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Facades\Tests\Setup\CategoryFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function testItWillShowCategoryMenu()
    {
        /** @var \App\Category $c */
        $c = factory(Category::class)->create();

        /** @var \App\Category $sc */
        $sc = $c->subCat()->create(
            factory(Category::class)->raw()
        );

        $this->assertDatabaseHas('categories', $sc->only('slug'));

        $this->get($sc->path($c->slug))
            ->assertOk();
        // ->assertSeeText($c->name)
        // ->assertSeeText($sc->name);
    }

    public function testRetrivingProductList()
    {
        /** @var \App\Category $c */
        /** @var \App\Category $sc */
        /** @var \App\Product $p */
        [$c, $sc, $p] = CategoryFactory::wSub(1)->wPro()->create();

        $this->get('/api/sub/' . $sc->slug)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function testLoadProductsWithBrandFilter()
    {
        /** @var \App\Category $c */
        /** @var \App\Category $sc */
        /** @var \App\Product[] $p */
        [$c, $sc, $p] = CategoryFactory::wSub(1)->wPro(4)->create();
        $sc->load('productsMini');

        $brands = Arr::pluck($p, 'brand');
        $brands = Arr::shuffle($brands);
        $brands = implode(',', $brands);

        $this->get("/api/sub/$sc->slug/filterBrands/$brands")
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertSee($p[2]->slug);
    }

    public function testLoadProductsWithCondition()
    {
        /** @var \App\Category $c */
        /** @var \App\Category $sc */
        /** @var \App\Product[] $p */
        [$c, $sc] = CategoryFactory::wSub()->create();

        $p = $sc->products()->saveMany(
            factory(Product::class, 4)->make([
                'category_slug' => $sc->slug,
                'is_used' => false
            ])
        );

        $sc->products()->save(factory(Product::class)->make([
            'category_slug' => $sc->slug,
            'is_used' => true
        ]));

        $this->get("/api/sub/$sc->slug/filterCondition/0")
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertSee($p[2]->slug);
    }

    public function testFilterDataByPrice()
    {
        $this->withoutExceptionHandling();
        /** @var \App\Category $c */
        /** @var \App\Category $sc */
        /** @var \App\Product[] $p */
        [$c, $sc, $p] = CategoryFactory::wSub(1)->wPro(10)->create();

        $from = 1;
        $to = 1000000;

        $this->get("/api/sub/$sc->slug/priceFilter/$from/$to")
            ->assertOk();
    }

    public function testShowingProductData()
    {
        $this->withoutExceptionHandling();
        /** @var \App\Category $c */
        /** @var \App\Category $sc */
        /** @var \App\Product[] $p */
        [$c, $sc, $p] = CategoryFactory::wSub(1)->wPro(10)->create();

        $this->get(app()->getLocale() . '/p/' . $p[0]->slug)
            ->assertOk()
            ->assertSee($p[0]->name);
    }

    public function testAnyOneCanSearchForProducts()
    {
        $this->withoutExceptionHandling();
        /** @var \App\Category $c */
        /** @var \App\Category $sc */
        /** @var \App\Product[] $p */
        [$c, $sc, $p] = CategoryFactory::wSub(1)->wPro(10)->create();

        $p = $p[4];

        $q = urlencode(trim(Str::lower(substr($p->name, 4, 5))));

        $this->get(app()->getLocale() . '/p/ser/?q=' . $q)
            ->assertOk()
            ->assertSee($p->name)
            ->assertSee($p->price);

        $q = substr($p->brand, 1);

        $this->get(app()->getLocale() . '/p/ser/?q=' . $q)
            ->assertOk()
            ->assertSee($p->name)
            ->assertSee($p->price);
    }
}
