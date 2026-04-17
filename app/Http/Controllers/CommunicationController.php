<?php

namespace App\Http\Controllers;

use App\Helpers\Qs;
use App\Models\Announcement;
use App\Models\Message;
use App\Repositories\UserRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommunicationController extends Controller
{
    protected $user;

    public function __construct(UserRepo $user)
    {
        $this->middleware('auth');
        $this->user = $user;
    }

    /* ---- Announcements ---- */

    public function announcements()
    {
        $userType = Qs::getUserType();
        $d['announcements'] = Announcement::where('active', true)
            ->where(function ($q) use ($userType) {
                $q->where('audience', 'all')->orWhere('audience', $userType . 's');
            })
            ->with('author')->orderByDesc('created_at')->paginate(15);
        return view('pages.communication.announcements', $d);
    }

    public function storeAnnouncement(Request $req)
    {
        if (!Qs::userIsTeamSA()) return Qs::goWithDanger();

        $this->validate($req, [
            'title'    => 'required|string|max:200',
            'body'     => 'required|string',
            'audience' => 'required|string',
        ]);
        Announcement::create([
            'author_id' => Auth::id(),
            'title'     => $req->title,
            'body'      => $req->body,
            'audience'  => $req->audience,
        ]);
        return back()->with('flash_success', 'Announcement posted.');
    }

    public function deleteAnnouncement($id)
    {
        if (!Qs::userIsTeamSA()) return Qs::goWithDanger();
        Announcement::destroy($id);
        return back()->with('flash_success', 'Announcement deleted.');
    }

    /* ---- Messages ---- */

    public function inbox()
    {
        $d['messages'] = Message::where('receiver_id', Auth::id())
                            ->with('sender')->orderByDesc('created_at')->paginate(20);
        return view('pages.communication.inbox', $d);
    }

    public function compose()
    {
        $uid = Auth::id();

        // Admin/Super Admin — can message anyone
        if (Qs::userIsTeamSA()) {
            $d['users'] = $this->user->getAll()->where('id', '!=', $uid)->values();
            $d['label'] = 'Send to anyone';
        }
        // Teacher — can message parents of their students
        elseif (Qs::userIsTeacher()) {
            $classIds = \App\Models\Subject::where('teacher_id', $uid)->pluck('my_class_id')->unique();
            $parentIds = \App\Models\StudentRecord::whereIn('my_class_id', $classIds)
                ->whereNotNull('my_parent_id')->pluck('my_parent_id')->unique();
            $d['users'] = \App\User::whereIn('id', $parentIds)->where('id', '!=', $uid)->orderBy('name')->get();
            $d['label'] = 'Send to parents of your students';
        }
        // Parent — can only message teachers of their children
        elseif (Qs::userIsParent()) {
            $classIds = \App\Models\StudentRecord::where('my_parent_id', $uid)->pluck('my_class_id')->unique();
            $teacherIds = \App\Models\Subject::whereIn('my_class_id', $classIds)->pluck('teacher_id')->unique();
            $d['users'] = \App\User::whereIn('id', $teacherIds)->where('id', '!=', $uid)->orderBy('name')->get();
            $d['label'] = 'Send to your child\'s teachers';
        }
        // Everyone else — can message admins only
        else {
            $d['users'] = $this->user->getAll()
                ->whereIn('user_type', ['admin', 'super_admin'])
                ->where('id', '!=', $uid)->values();
            $d['label'] = 'Send to administration';
        }

        return view('pages.communication.compose', $d);
    }

    public function sendMessage(Request $req)
    {
        $this->validate($req, [
            'receiver_id' => 'required|exists:users,id',
            'body'        => 'required|string',
        ]);
        Message::create([
            'sender_id'   => Auth::id(),
            'receiver_id' => $req->receiver_id,
            'subject'     => $req->subject,
            'body'        => $req->body,
        ]);
        return redirect()->route('inbox')->with('flash_success', 'Message sent.');
    }

    public function readMessage(\App\Models\Message $message)
    {
        if ($message->sender_id != auth()->id() && $message->receiver_id != auth()->id()) {
            return redirect()->route('inbox')
                ->with('flash_danger', 'You do not have permission to read this message.');
        }

        if ($message->receiver_id == auth()->id() && !$message->read) {
            $message->update(['read' => true]);
        }

        $d['message'] = $message->load('sender', 'receiver');
        return view('pages.communication.read', $d);
    }
}
