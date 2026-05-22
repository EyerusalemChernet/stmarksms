<?php

namespace App\Http\Controllers;

use App\Helpers\Qs;
use App\Mail\MessageNotification;
use App\Models\Announcement;
use App\Models\Message;
use App\Repositories\UserRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        // Map user_type to the audience value used when posting
        // e.g. user_type 'hr_manager' matches audience 'hr_managers'
        $audienceKey = $userType . 's'; // teacher→teachers, parent→parents, student→students, admin→admins, hr_manager→hr_managers

        $d['announcements'] = Announcement::where('active', true)
            ->where(function ($q) use ($audienceKey) {
                $q->where('audience', 'all')->orWhere('audience', $audienceKey);
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
                            ->where('archived', false)
                            ->with('sender')->orderByDesc('created_at')->paginate(20);
        return view('pages.communication.inbox', $d);
    }

    public function markRead(Message $message)
    {
        $this->authorizeMessage($message);
        $message->update(['read' => true]);
        return back()->with('flash_success', 'Message marked as read.');
    }

    public function markUnread(Message $message)
    {
        $this->authorizeMessage($message);
        $message->update(['read' => false]);
        return back()->with('flash_success', 'Message marked as unread.');
    }

    public function archiveMessage(Message $message)
    {
        $this->authorizeMessage($message);
        $message->update(['archived' => true]);
        return back()->with('flash_success', 'Message archived.');
    }

    public function deleteMessage(Message $message)
    {
        $this->authorizeMessage($message);
        $message->delete();
        return back()->with('flash_success', 'Message deleted.');
    }

    private function authorizeMessage(Message $message)
    {
        if ($message->receiver_id !== Auth::id() && $message->sender_id !== Auth::id()) {
            abort(403);
        }
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
            $classIds = \App\Models\Subject::forTeacher($uid)->pluck('my_class_id')->unique();
            $parentIds = \App\Models\StudentRecord::whereIn('my_class_id', $classIds)
                ->whereNotNull('my_parent_id')->pluck('my_parent_id')->unique();
            $d['users'] = \App\User::whereIn('id', $parentIds)->where('id', '!=', $uid)->orderBy('name')->get();
            $d['label'] = 'Send to parents of your students';
        }
        // Parent — can only message teachers of their children
        elseif (Qs::userIsParent()) {
            $classIds = \App\Models\StudentRecord::where('my_parent_id', $uid)->pluck('my_class_id')->unique();
            $teacherIds = \App\Models\Subject::teacherUserIdsForClasses($classIds);
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

        $message = Message::create([
            'sender_id'   => Auth::id(),
            'receiver_id' => $req->receiver_id,
            'subject'     => $req->subject,
            'body'        => $req->body,
        ]);

        // Send email notification to the recipient if they have an email address
        $message->load('sender', 'receiver');
        Log::info('MAIL_DEBUG: receiver=' . ($message->receiver->email ?? 'NULL') . ' host=' . config('mail.host'));
        if ($message->receiver && $message->receiver->email) {
            try {
                Mail::to($message->receiver->email, $message->receiver->name)
                    ->send(new MessageNotification($message));
                Log::info('MAIL_DEBUG: sent OK to ' . $message->receiver->email);
            } catch (\Exception $e) {
                Log::error('MAIL_DEBUG: FAILED - ' . $e->getMessage());
            }
        }

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
