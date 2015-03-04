<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Http\Requests\PrepareNoticeRequest;
use App\Provider;
use Illuminate\Http\Request;

class NoticesController extends Controller {

    /**
     * Create a new notices controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show all notices.
     *
     * @return string
     */
    public function index()
    {
        return 'all notices';
    }

    /**
     * Show a page to create a new notice.
     *
     * @return \Response
     */
    public function create()
    {
        $providers = Provider::lists('name', 'id');

        return view('notices.create', compact('providers'));
    }

    public function confirm(PrepareNoticeRequest $request)
    {
        return $request->all();
    }

}
