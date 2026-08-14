<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Inertia\Inertia;


class UserController extends Controller
{
    public function index()
    {
        // Logic to retrieve users and pass them to the view
        $users = User::query()
            ->select('id', 'name', 'email', 'created_at')
            ->latest()
            ->paginate(10);
        // return view('admin.users.index', compact('users'));
        return Inertia::render('admin/users/Index', [
            'users' => $users,
        ]);
    }
}
