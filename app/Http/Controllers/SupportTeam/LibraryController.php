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
        $this->middleware('teamSA', ['only' => ['store', 'update', 'destroy', 'approve', 'reject', 'bulkImport', 'bulkTemplate']]);
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

    /** Bulk import books from CSV */
    public function bulkTemplate()
    {
        $headers = ['title', 'author', 'book_type', 'total_copies', 'location', 'description'];
        $example = ['Mathematics Grade 5', 'Abebe Girma', 'Textbook', '10', 'Shelf A-1', 'Grade 5 math textbook'];
        $csv = implode(',', $headers) . "\n" . implode(',', $example) . "\n";
        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="books_bulk_template.csv"',
        ]);
    }

    public function bulkImport(Request $req)
    {
        $req->validate(['csv_file' => 'required|file|mimes:csv,txt|max:5120']);

        $file    = $req->file('csv_file');
        $handle  = fopen($file->getRealPath(), 'r');
        $headers = array_map('trim', fgetcsv($handle));

        $imported = 0;
        $errors   = [];
        $row      = 1;

        while (($line = fgetcsv($handle)) !== false) {
            $row++;
            if (count($line) < 1) continue;
            $data = array_combine(
                array_slice($headers, 0, count($line)),
                array_map('trim', $line)
            );

            $title = $data['title'] ?? ($data['name'] ?? '');
            if (empty($title)) {
                $errors[] = "Row {$row}: Title is required — skipped.";
                continue;
            }

            $copies = intval($data['total_copies'] ?? 1);
            if ($copies < 1) $copies = 1;

            Book::create([
                'name'          => $title,
                'author'        => $data['author'] ?? null,
                'book_type'     => $data['book_type'] ?? null,
                'total_copies'  => $copies,
                'issued_copies' => 0,
                'location'      => $data['location'] ?? null,
                'description'   => $data['description'] ?? null,
            ]);
            $imported++;
        }

        fclose($handle);
        AuditLog::log('bulk_import', 'library', "Bulk imported {$imported} book(s).");

        $msg = "{$imported} book(s) imported successfully.";
        if ($errors) $msg .= ' ' . count($errors) . ' row(s) skipped.';

        return response()->json(['ok' => $imported > 0, 'msg' => $msg, 'errors' => $errors]);
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
