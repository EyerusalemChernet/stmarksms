<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\BookRequest;
use App\Repositories\MyClassRepo;
use App\Services\RulesEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LibraryController extends Controller
{
    protected $my_class;

    public function __construct(MyClassRepo $my_class)
    {
        $this->my_class = $my_class;
        $this->middleware('teamSA', ['only' => ['store', 'update', 'destroy', 'approve', 'reject']]);
    }

    /** Book list */
    public function index()
    {
        $d['books'] = Book::with('my_class')->orderBy('name')->paginate(20);
        return view('pages.support_team.library.index', $d);
    }

    public function create()
    {
        $d['my_classes'] = $this->my_class->all();
        return view('pages.support_team.library.create', $d);
    }

    public function store(Request $req)
    {
        $this->validate($req, [
            'name'         => 'required|string|max:100',
            'total_copies' => 'required|integer|min:1',
        ]);
        $data = $req->only(['name', 'my_class_id', 'description', 'author', 'book_type', 'url', 'location', 'total_copies']);
        $data['issued_copies'] = 0;
        Book::create($data);
        return redirect()->route('library.index')->with('flash_success', 'Book added successfully.');
    }

    public function edit($id)
    {
        $d['book']       = Book::findOrFail($id);
        $d['my_classes'] = $this->my_class->all();
        return view('pages.support_team.library.edit', $d);
    }

    public function update(Request $req, $id)
    {
        $book = Book::findOrFail($id);
        $book->update($req->only(['name', 'my_class_id', 'description', 'author', 'book_type', 'url', 'location', 'total_copies']));
        return redirect()->route('library.index')->with('flash_success', 'Book updated.');
    }

    public function destroy($id)
    {
        Book::destroy($id);
        return back()->with('flash_success', 'Book deleted.');
    }

    /** Borrow requests */
    public function requests()
    {
        $d['requests'] = BookRequest::with(['book', 'user'])->orderByDesc('created_at')->paginate(20);
        return view('pages.support_team.library.requests', $d);
    }

    public function requestBook(Request $req)
    {
        $this->validate($req, ['book_id' => 'required|exists:books,id']);

        $validation = RulesEngine::validateBookBorrow($req->book_id, Auth::id());
        if (!$validation['valid']) {
            return back()->with('pop_error', $validation['message']);
        }

        BookRequest::updateOrCreate(
            ['book_id' => $req->book_id, 'user_id' => Auth::id(), 'status' => 'pending'],
            ['requested_at' => now()]
        );

        return back()->with('flash_success', 'Borrow request submitted.');
    }

    public function approve($id)
    {
        $br   = BookRequest::findOrFail($id);
        $book = Book::findOrFail($br->book_id);

        if ($book->issued_copies >= $book->total_copies) {
            return back()->with('pop_error', "No copies of \"{$book->name}\" are available. All copies are currently issued.");
        }

        $br->update(['status' => 'approved', 'issued_at' => now()]);
        $book->increment('issued_copies');
        AuditLog::log('approved', 'library', "Book '{$book->name}' issued to user #{$br->user_id}");
        return back()->with('flash_success', 'Request approved.');
    }

    public function reject($id)
    {
        BookRequest::findOrFail($id)->update(['status' => 'rejected']);
        return back()->with('flash_success', 'Request rejected.');
    }

    public function returnBook($id)
    {
        $validation = RulesEngine::validateBookReturn($id);
        if (!$validation['valid']) {
            return back()->with('pop_error', $validation['message']);
        }

        $br   = BookRequest::findOrFail($id);
        $book = Book::findOrFail($br->book_id);
        $br->update(['status' => 'returned', 'returned_at' => now()]);
        $book->decrement('issued_copies');
        AuditLog::log('returned', 'library', "Book '{$book->name}' returned by user #{$br->user_id}");
        return back()->with('flash_success', 'Book returned.');
    }

    public function history()
    {
        $d['history'] = BookRequest::with(['book', 'user'])
                            ->whereIn('status', ['returned', 'approved'])
                            ->orderByDesc('updated_at')->paginate(20);
        return view('pages.support_team.library.history', $d);
    }
}
