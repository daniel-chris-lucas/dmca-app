<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Http\Requests\PrepareNoticeRequest;
use App\Notice;
use App\Provider;
use Auth;
use Illuminate\Contracts\Auth\Guard;
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
        return Auth::user()->notices;
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

    /**
     * Ask the user to confirm the DMCA that will be delivered.
     *
     * @param PrepareNoticeRequest $request
     * @param Guard $auth
     * @return \Response
     */
    public function confirm(PrepareNoticeRequest $request, Guard $auth)
    {
        $template = $this->compileDmcaTemplate($data = $request->all(), $auth);

        session()->flash('dmca', $data);

        return view('notices.confirm', compact('template'));
    }

    /**
     * Store a new DMCA notice.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        $this->createNotice($request);

        return redirect('notices');
    }

    /**
     * Compile the DMCA template from the form data.
     *
     * @param $data
     * @param Guard $auth
     * @return mixed
     */
    public function compileDmcaTemplate($data, Guard $auth)
    {
        $data = $data + [
            'name'  => $auth->user()->name,
            'email' => $auth->user()->email
        ];

        return view()->file(app_path('Http/Templates/dmca.blade.php'), $data);
    }

    /**
     * Create and persist a new DMCA notice.
     *
     * @param Request $request
     */
    private function createNotice(Request $request)
    {
        $data = session()->get('dmca');

        $notice = Notice::open($data)->useTemplate($request->input('template'));

        Auth::user()->notices()->save($notice);
    }

}
