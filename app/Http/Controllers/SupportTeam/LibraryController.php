<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\BookRequest;
use App\Repositories\MyClassRepo;
use App\Services\RulesEngine;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LibraryController extends Controller
{
    protected $my_class;

    public function __construct(MyClassRepo $my_class)
    {
        $this->my_class = $my_class;
        $this->middleware('teamSA', ['only' => [
            'store', 'update', 'destroy', 'approve', 'reject',
            'bulkImport', 'bulkTemplate', 'isbnLookup',
        ]]);
    }

    // ── DASHBOARD / INDEX ────────────────────────────────────────────────────

    public function index(Request $req)
    {
        $search     = trim($req->get('search', ''));
        $typeFilter = $req->get('book_type', '');
        $classFilter = $req->get('my_class_id', '');
        $availFilter = $req->get('available', '');

        $query = Book::with('my_class')
            ->when($search, fn($q) => $q->where(fn($i) =>
                $i->where('name',   'like', "%{$search}%")
                  ->orWhere('author','like', "%{$search}%")
                  ->orWhere('isbn',  'like', "%{$search}%")
                  ->orWhere('publisher', 'like', "%{$search}%")
            ))
            ->when($typeFilter,  fn($q) => $q->where('book_type', $typeFilter))
            ->when($classFilter, fn($q) => $q->where('my_class_id', $classFilter))
            ->when($availFilter === 'available', fn($q) => $q->whereRaw('total_copies > issued_copies'))
            ->when($availFilter === 'unavailable', fn($q) => $q->whereRaw('total_copies <= issued_copies'))
            ->orderBy('name');

        // Stats for dashboard cards
        $stats = [
            'total_books'     => Book::count(),
            'total_copies'    => Book::sum('total_copies'),
            'issued_copies'   => Book::sum('issued_copies'),
            'pending_requests'=> BookRequest::where('status', 'pending')->count(),
            'overdue'         => BookRequest::where('status', 'approved')
                                    ->whereNotNull('due_date')
                                    ->where('due_date', '<', now()->toDateString())
                                    ->count(),
        ];
        $stats['available_copies'] = $stats['total_copies'] - $stats['issued_copies'];

        $books      = $query->paginate(15)->withQueryString();
        $my_classes = $this->my_class->all();
        $bookTypes  = ['Textbook', 'Reference', 'Novel', 'Magazine', 'Other'];

        return view('pages.support_team.library.index', compact(
            'books', 'stats', 'my_classes', 'bookTypes',
            'search', 'typeFilter', 'classFilter', 'availFilter'
        ));
    }

    // ── CREATE / STORE ───────────────────────────────────────────────────────

    public function create()
    {
        $my_classes = $this->my_class->all();
        $bookTypes  = ['Textbook', 'Reference', 'Novel', 'Magazine', 'Other'];
        return view('pages.support_team.library.create', compact('my_classes', 'bookTypes'));
    }

    public function store(Request $req)
    {
        $req->validate([
            'name'         => 'required|string|max:200',
            'total_copies' => 'required|integer|min:1',
            'isbn'         => 'nullable|string|max:20',
            'due_days'     => 'nullable|integer|min:1|max:365',
        ]);

        $data = $req->only([
            'name', 'isbn', 'my_class_id', 'description', 'author',
            'publisher', 'published_year', 'book_type', 'subject_area',
            'url', 'location', 'total_copies', 'due_days',
        ]);
        $data['issued_copies'] = 0;
        $data['due_days']      = $data['due_days'] ?? 14;

        // Handle cover image upload
        if ($req->hasFile('cover_image')) {
            $path = $req->file('cover_image')->store('library', 'public');
            $data['cover_image'] = basename($path);
        }

        $book = Book::create($data);
        AuditLog::log('created', 'library', "Book added: {$book->name}");
        return redirect()->route('library.index')->with('flash_success', "Book \"{$book->name}\" added successfully.");
    }

    // ── ISBN LOOKUP (Open Library API) ───────────────────────────────────────

    public function isbnLookup(Request $req)
    {
        $isbn = preg_replace('/[^0-9X]/', '', strtoupper(trim($req->get('isbn', ''))));
        if (strlen($isbn) < 10) {
            return response()->json(['ok' => false, 'msg' => 'Invalid ISBN.']);
        }

        try {
            $client   = new Client(['timeout' => 8]);
            $response = $client->get("https://openlibrary.org/api/books?bibkeys=ISBN:{$isbn}&format=json&jscmd=data");
            $data     = json_decode($response->getBody()->getContents(), true);
            $key      = "ISBN:{$isbn}";

            if (empty($data[$key])) {
                return response()->json(['ok' => false, 'msg' => 'Book not found in Open Library database.']);
            }

            $book = $data[$key];
            return response()->json([
                'ok'             => true,
                'title'          => $book['title'] ?? '',
                'author'         => collect($book['authors'] ?? [])->pluck('name')->implode(', '),
                'publisher'      => collect($book['publishers'] ?? [])->pluck('name')->first() ?? '',
                'published_year' => substr($book['publish_date'] ?? '', -4),
                'cover'          => $book['cover']['medium'] ?? '',
                'description'    => is_string($book['notes'] ?? '') ? ($book['notes'] ?? '') : '',
            ]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => 'Could not reach Open Library. Fill in manually.']);
        }
    }

    // ── EDIT / UPDATE ────────────────────────────────────────────────────────

    public function edit($id)
    {
        $book       = Book::findOrFail($id);
        $my_classes = $this->my_class->all();
        $bookTypes  = ['Textbook', 'Reference', 'Novel', 'Magazine', 'Other'];
        return view('pages.support_team.library.edit', compact('book', 'my_classes', 'bookTypes'));
    }

    public function update(Request $req, $id)
    {
        $req->validate([
            'name'         => 'required|string|max:200',
            'total_copies' => 'required|integer|min:1',
        ]);

        $book = Book::findOrFail($id);
        $data = $req->only([
            'name', 'isbn', 'my_class_id', 'description', 'author',
            'publisher', 'published_year', 'book_type', 'subject_area',
            'url', 'location', 'total_copies', 'due_days',
        ]);

        if ($req->hasFile('cover_image')) {
            $path = $req->file('cover_image')->store('library', 'public');
            $data['cover_image'] = basename($path);
        }

        $book->update($data);
        AuditLog::log('updated', 'library', "Book updated: {$book->name}");
        return redirect()->route('library.index')->with('flash_success', "Book \"{$book->name}\" updated.");
    }

    public function destroy($id)
    {
        $book = Book::findOrFail($id);
        AuditLog::log('deleted', 'library', "Book deleted: {$book->name}");
        $book->delete();
        return back()->with('flash_success', 'Book deleted.');
    }

    // ── BORROW REQUESTS ──────────────────────────────────────────────────────

    public function requests(Request $req)
    {
        $status = $req->get('status', 'pending');
        $search = trim($req->get('search', ''));

        $query = BookRequest::with(['book', 'user'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($search, fn($q) => $q->whereHas('book', fn($i) =>
                $i->where('name', 'like', "%{$search}%")
            )->orWhereHas('user', fn($i) =>
                $i->where('name', 'like', "%{$search}%")
            ))
            ->orderByDesc('created_at');

        $statusCounts = array_merge(
            ['pending' => 0, 'approved' => 0, 'returned' => 0, 'rejected' => 0],
            BookRequest::selectRaw('status, count(*) as total')
                ->groupBy('status')->pluck('total', 'status')->toArray()
        );
        $overdueCount = BookRequest::where('status', 'approved')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        $requests = $query->paginate(20)->withQueryString();
        return view('pages.support_team.library.requests', compact(
            'requests', 'status', 'statusCounts', 'overdueCount', 'search'
        ));
    }

    public function requestBook(Request $req)
    {
        $req->validate(['book_id' => 'required|exists:books,id']);

        $validation = RulesEngine::validateBookBorrow($req->book_id, Auth::id());
        if (!$validation['valid']) {
            return back()->with('pop_error', $validation['message']);
        }

        BookRequest::updateOrCreate(
            ['book_id' => $req->book_id, 'user_id' => Auth::id(), 'status' => 'pending'],
            ['requested_at' => now()]
        );

        return back()->with('flash_success', 'Borrow request submitted. Awaiting approval.');
    }

    public function approve($id)
    {
        $br   = BookRequest::findOrFail($id);
        $book = Book::findOrFail($br->book_id);

        if ($book->issued_copies >= $book->total_copies) {
            return back()->with('pop_error', "No copies of \"{$book->name}\" are available.");
        }

        $dueDate = now()->addDays($book->due_days ?? 14)->toDateString();
        $br->update(['status' => 'approved', 'issued_at' => now(), 'due_date' => $dueDate]);
        $book->increment('issued_copies');
        AuditLog::log('approved', 'library', "Book '{$book->name}' issued to user #{$br->user_id}. Due: {$dueDate}");
        return back()->with('flash_success', "Approved. Due date: {$dueDate}.");
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

        // Calculate overdue fine (2 ETB per day)
        $fine = 0;
        if ($br->due_date && $br->due_date->isPast()) {
            $fine = $br->due_date->diffInDays(now()) * 2;
        }

        $br->update(['status' => 'returned', 'returned_at' => now(), 'overdue_fine' => $fine]);
        $book->decrement('issued_copies');
        AuditLog::log('returned', 'library', "Book '{$book->name}' returned by user #{$br->user_id}" . ($fine > 0 ? ". Fine: {$fine} ETB" : ''));
        $msg = 'Book returned successfully.';
        if ($fine > 0) $msg .= " Overdue fine: {$fine} ETB.";
        return back()->with('flash_success', $msg);
    }

    // ── HISTORY ──────────────────────────────────────────────────────────────

    public function history(Request $req)
    {
        $search = trim($req->get('search', ''));
        $history = BookRequest::with(['book', 'user'])
            ->whereIn('status', ['returned', 'approved'])
            ->when($search, fn($q) => $q->whereHas('book', fn($i) =>
                $i->where('name', 'like', "%{$search}%")
            )->orWhereHas('user', fn($i) =>
                $i->where('name', 'like', "%{$search}%")
            ))
            ->orderByDesc('updated_at')
            ->paginate(20)->withQueryString();

        return view('pages.support_team.library.history', compact('history', 'search'));
    }

    // ── BULK IMPORT ──────────────────────────────────────────────────────────

    public function bulkTemplate()
    {
        $headers = ['title', 'author', 'isbn', 'publisher', 'published_year', 'book_type', 'total_copies', 'due_days', 'location', 'description'];
        $example = ['Mathematics Grade 5', 'Abebe Girma', '9780000000000', 'MoE Ethiopia', '2022', 'Textbook', '10', '14', 'Shelf A-1', 'Grade 5 math textbook'];
        $csv = implode(',', $headers) . "\n" . implode(',', $example) . "\n";
        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="books_bulk_template.csv"',
        ]);
    }

    public function bulkImport(Request $req)
    {
        $req->validate(['csv_file' => 'required|file|mimes:csv,txt|max:5120']);

        $handle  = fopen($req->file('csv_file')->getRealPath(), 'r');
        $headers = array_map('trim', fgetcsv($handle));

        $imported = 0;
        $errors   = [];
        $row      = 1;

        while (($line = fgetcsv($handle)) !== false) {
            $row++;
            if (count($line) < 1 || implode('', $line) === '') continue;
            $data = array_combine(
                array_slice($headers, 0, count($line)),
                array_map('trim', $line)
            );

            $title = $data['title'] ?? ($data['name'] ?? '');
            if (empty($title)) {
                $errors[] = "Row {$row}: Title is required — skipped.";
                continue;
            }

            $copies = max(1, intval($data['total_copies'] ?? 1));

            Book::create([
                'name'           => $title,
                'author'         => $data['author']         ?? null,
                'isbn'           => $data['isbn']           ?? null,
                'publisher'      => $data['publisher']      ?? null,
                'published_year' => intval($data['published_year'] ?? 0) ?: null,
                'book_type'      => $data['book_type']      ?? null,
                'total_copies'   => $copies,
                'issued_copies'  => 0,
                'due_days'       => max(1, intval($data['due_days'] ?? 14)),
                'location'       => $data['location']       ?? null,
                'description'    => $data['description']    ?? null,
            ]);
            $imported++;
        }

        fclose($handle);
        AuditLog::log('bulk_import', 'library', "Bulk imported {$imported} book(s).");

        $msg = "{$imported} book(s) imported successfully.";
        if ($errors) $msg .= ' ' . count($errors) . ' row(s) skipped.';
        return response()->json(['ok' => $imported > 0, 'msg' => $msg, 'errors' => $errors]);
    }
}
