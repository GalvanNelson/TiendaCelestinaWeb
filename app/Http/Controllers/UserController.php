<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $users=User::all();
        // //dd($users->links());
        // return Inertia::render('User/Index',compact('users'));
           //dd($users);
        $users=User::orderBy("id");
        $name="";
        if(request()->has("name")){
            $name=request("name");
            $users=$users->where('name','like','%'.$name.'%')
                   ->orwhere('email','like','%'.$name.'%');
        }
        $users=$users->paginate(10)->appends(request()->except("page"));
        //dd($users->links());
        return Inertia::render('User/Index',compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
