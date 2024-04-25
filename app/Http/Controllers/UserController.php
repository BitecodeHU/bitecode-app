<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Notifications\NewUser;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Keresési feltétel
        $kw = $request->q;

        if (empty($kw)) {
            // Ha nincsen keresési feltétel, akkor az összes adat mejelenítése max 10/oldal
            $allUsers = User::paginate(10);
        } else {
            // Csak a keresési értéknek megfelelő adatok megjelítése
            $allUsers = User::where('name', 'LIKE', "%{$kw}%")
                ->orwhere('email', 'LIKE', "%{$kw}%")
                ->paginate(10)
                ->appends(['q' => "{$kw}"])
                ->withPath('/users')
                ->withQueryString();
        }
        // Oldal megjelenítése
        return view('users.index', ['allUsers' => $allUsers, 'kw' => $kw]);
    }

    /*
     * Display the user creation form
     */
    public function add(Request $request): View
    {
        return view('users.add', [
            'user' => $request->user(),
        ]);
    }

    /*
     * Create and store the new user
     */
    public function create(Request $request)
    {
        $email = strtolower($request->input('email'));

        $user = new User();
        $userExists = $user->existsWithEmail($email);

        if ($userExists) {
            // User already exists, throw an error
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['email' => __('User already exists!')]);
        }

        $password = Str::random(6);

        $user = new User([
            'name' => $request->input('name'),
            'email' => $email,
            'password' => Hash::make($password),
            'created_at' => Carbon::now(),
        ]);

        $user->save();

        $user->notify(new NewUser($email, $password));
        return redirect()->back()->with(['status' => 'User created successfully']);
    }

    public function search(Request $request)
    {
        // Keresési feltétel
        $kw = $request->q;
        if (empty($kw)) {
            // Ha nincsen keresési feltétel, akkor az összes adat mejelenítése max 10/oldal
            $allUsers = User::paginate(10);
        } else {
            // Csak a keresési értéknek megfelelő adatok megjelítése
            $allUsers = User::where('name', 'LIKE', "%{$kw}%")
                ->orwhere('email', 'LIKE', "%{$kw}%")
                ->paginate(10)
                ->appends(['q' => "{$kw}"])
                ->withPath('/users')
                ->withQueryString();
        }

        // Tömb átalakítása Laravel collection-né
        $allUsersCollection = collect($allUsers);
        // A lekérdezett adatok egyesítése oldalszámozási hivatkozásokkal HTML-ben
        $allUsersCollection = $allUsersCollection->merge(['pagination_links' => (string) $allUsers->onEachSide(2)->links()]);

        // Visszaadjuk az adatokat JSON string formában
        return collect(["allUsers" => $allUsersCollection->all()])->toJson();
    }
}
