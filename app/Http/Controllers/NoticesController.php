<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Http\Requests\PrepareNoticeRequest;
use App\Notice;
use App\Provider;
use Auth;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NoticesController extends Controller {

    /**
     * Create a new notices controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');

        parent::__construct();
    }

    /**
     * Show all notices.
     *
     * @return string
     */
    public function index()
    {
        $notices = $this->user->notices()->where('content_removed', 0)->get();

        return view('notices.index', compact('notices'));
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
     * @return \Response
     */
    public function confirm(PrepareNoticeRequest $request)
    {
        $template = $this->compileDmcaTemplate($data = $request->all());

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
        $notice = $this->createNotice($request);

        Mail::queue(['text' => 'emails.dmca'], compact('notice'), function ($message) use ($notice) {
            $message->from($notice->getOwnerEmail())
                ->to($notice->getRecipientEmail())
                ->subject('DMCA Notice');
        });

        flash('Your DMCA notice has been delivered!');

        return redirect('notices');
    }

    public function update($noticeId, Request $request)
    {
        $isRemoved = $request->has('content_removed');

        Notice::findOrFail($noticeId)
            ->update(['content_removed' => $isRemoved]);
    }

    /**
     * Compile the DMCA template from the form data.
     *
     * @param $data
     * @return mixed
     */
    public function compileDmcaTemplate($data)
    {
        $data = $data + [
            'name'  => $this->user->name,
            'email' => $this->user->email
        ];

        return view()->file(app_path('Http/Templates/dmca.blade.php'), $data);
    }

    /**
     * Create and persist a new DMCA notice.
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function createNotice(Request $request)
    {
        $data = session()->get('dmca');

        $notice = Notice::open($data)->useTemplate($request->input('template'));

        $notice = $this->user->notices()->save($notice);

        return $notice;
    }

}
