<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
// use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * MULTI TENANCY
 */

use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Hyn\Tenancy\Repositories\HostnameRepository;
use Hyn\Tenancy\Repositories\WebsiteRepository;

class InquilinosController extends Controller
{

    // use RegistersUsers;

    protected function validator(array $data)
    {
        $fqdn = sprintf( '%s.%s', $data['fqdn'], env('APP_DOMAIN'));
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'fdqn' => ['required', 'string', 'max:20', Rule::unique('hostnames')->where(function ($query) use ($fdqn) {
                return $query->where('fdqn', $fqdn);
            })],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    protected function registered(Request $request, $inquilino)
    {
        $fqdn = sprintf('%s.%s', request('fqdn'), env('APP_DOMAIN'));
        $website = new Website;
        $website->uuid = String::random(10);
        app( WebsiteRepository::class)->create($website);

        $hostname = new Hostname;
        $hostname->fqdn = $fqdn;
        $hostname = app( HostnameRepository::class)->create($website);
        app( HostnameRepository::class )->attach($hostname, $website);

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
