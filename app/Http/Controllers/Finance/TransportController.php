<?php

namespace App\Http\Controllers\Finance;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\Transport;
use App\Models\TransportPayment;
use App\Models\MyClass;
use Illuminate\Http\Request;

class TransportController extends Controller
{
    public function __construct()
    {
        $this->middleware('hr_manager');
    }

    public function index()
    {
        $transports = Transport::withCount('payments')->get();
        return view('pages.finance.transport.index', compact('transports'));
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'route_name' => 'required|string|max:100',
            'vehicle_no' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:20',
            'fee' => 'required|numeric|min:0'
        ]);

        Transport::create($data);
        return back()->with('flash_success', 'Transport Route Created Successfully');
    }

    public function update(Request $req, $id)
    {
        $transport = Transport::findOrFail($id);
        $data = $req->validate([
            'route_name' => 'required|string|max:100',
            'vehicle_no' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:20',
            'fee' => 'required|numeric|min:0',
            'active' => 'required|boolean'
        ]);

        $transport->update($data);
        return back()->with('flash_success', 'Transport Route Updated Successfully');
    }

    public function destroy($id)
    {
        Transport::destroy($id);
        return back()->with('flash_success', 'Transport Route Deleted Successfully');
    }

    public function payments(Request $req)
    {
        $classes = MyClass::orderBy('name')->get();
        $transports = Transport::where('active', true)->get();
        $session = $req->get('session', Qs::getCurrentSession());
        $class_id = $req->get('class_id');

        $query = TransportPayment::with(['student', 'transport'])->where('session', $session);
        if ($class_id) {
            $query->whereHas('student.student_record', fn($q) => $q->where('my_class_id', $class_id));
        }

        $payments = $query->latest()->get();

        return view('pages.finance.transport.payments', compact('payments', 'classes', 'transports', 'session'));
    }

    public function storePayment(Request $req)
    {
        $data = $req->validate([
            'student_id' => 'required|exists:users,id',
            'transport_id' => 'required|exists:transports,id',
            'session' => 'required',
            'month' => 'required',
            'amount' => 'required|numeric|min:1'
        ]);

        $data['paid_at'] = now();
        TransportPayment::create($data);

        return back()->with('flash_success', 'Transport Payment Recorded Successfully');
    }
}
