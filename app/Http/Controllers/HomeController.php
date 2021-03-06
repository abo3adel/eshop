<?php

namespace App\Http\Controllers;

use App\Category;
use App\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use GetCategoryList;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    { }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home', [
            'cats' => $this->getList(),
        ]);
    }

    public function sendData(string $cat_slug)
    {
        // dd($cat_slug);
        // $category->load('productsMini');
        return response()->json(
            Product::with('pCat')->where('category_slug', $cat_slug)->paginate(30)
        );
    }
}
