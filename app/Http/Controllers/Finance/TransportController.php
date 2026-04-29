<?php
namespace App\Http\Controllers\Finance;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\TransportRoute;
use App\Models\TransportRecord;
use App\Models\StudentRecord;
use Illuminate\Http\Request;

class TransportController extends Controller
{
    public function index()
    {
        $d['routes'] = TransportRoute::where('year', Qs::getCurrentSession())->get();
        $d['year'] = Qs::getCurrentSession();
        return view('pages.finance.transport.index', $d);
    }

    public function create()
    {
        return view('pages.finance.transport.create');
    }

    public function store(Request $req)
    {
        $req->validate([
            'name' => 'required|string|max:100',
            'area' => 'required|string|max:100',
            'fee'  => 'required|numeric|min:0',
        ]);
        $data = $req->only(['name', 'area', 'fee', 'description']);
        $data['year'] = Qs::getCurrentSession();
        TransportRoute::create($data);
        return redirect()->route('finance.transport.index')->with('flash_success', 'Route created successfully.');
    }

    public function edit($id)
    {
        $d['route'] = TransportRoute::findOrFail($id);
        return view('pages.finance.transport.edit', $d);
    }

    public function update(Request $req, $id)
    {
        $req->validate(['name' => 'required', 'area' => 'required', 'fee' => 'required|numeric']);
        TransportRoute::findOrFail($id)->update($req->only(['name', 'area', 'fee', 'description']));
        return redirect()->route('finance.transport.index')->with('flash_success', 'Route updated.');
    }

    public function destroy($id)
    {
        TransportRoute::findOrFail($id)->delete();
        return redirect()->route('finance.transport.index')->with('flash_success', 'Route deleted.');
    }

    public function records($route_id)
    {
        $d['route'] = TransportRoute::findOrFail($route_id);
        $d['records'] = TransportRecord::where('route_id', $route_id)
            ->with('student')->get();
        return view('pages.finance.transport.records', $d);
    }

    public function assignStudent(Request $req, $route_id)
    {
        $req->validate(['student_id' => 'required|exists:users,id']);
        $route = TransportRoute::findOrFail($route_id);
        TransportRecord::firstOrCreate(
            ['student_id' => $req->student_id, 'route_id' => $route_id, 'year' => Qs::getCurrentSession()],
            ['balance' => $route->fee]
        );
        return back()->with('flash_success', 'Student assigned to route.');
    }

    public function payNow(Request $req, $record_id)
    {
        $req->validate(['amt_paid' => 'required|numeric|min:1']);
        $record = TransportRecord::findOrFail($record_id);
        $newPaid = $record->amt_paid + $req->amt_paid;
        $balance = $record->route->fee - $newPaid;
        $record->update([
            'amt_paid' => $newPaid,
            'balance'  => max(0, $balance),
            'paid'     => $balance <= 0 ? 1 : 0,
        ]);
        return back()->with('flash_success', 'Payment recorded.');
    }
}
