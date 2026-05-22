<?php
namespace App\Http\Controllers\Finance;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\FeeCategory;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\MyClass;
use App\Models\StudentFeeInvoice;
use App\Models\StudentRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentFeeController extends Controller
{
    public function categories() {
        $categories = FeeCategory::withCount('structures')->get();
        return view('pages.finance.fees.categories', compact('categories'));
    }
    public function storeCategory(Request $req) {
        $req->validate(['name'=>'required|string|max:100','code'=>'required|string|max:10|unique:fee_categories','description'=>'nullable|string']);
        FeeCategory::create($req->only('name','code','description'));
        return back()->with('flash_success','Category created.');
    }
    public function updateCategory(Request $req, $id) {
        FeeCategory::findOrFail($id)->update($req->validate(['name'=>'required|string|max:100','description'=>'nullable|string','active'=>'required|boolean']));
        return back()->with('flash_success','Category updated.');
    }
    public function destroyCategory($id) {
        $cat = FeeCategory::withCount('structures')->findOrFail($id);
        if ($cat->structures_count > 0) return back()->with('flash_danger','Cannot delete: category has structures.');
        $cat->delete();
        return back()->with('flash_success','Category deleted.');
    }

    public function structures(Request $req) {
        $sessionFilter = $req->filled('session') ? $req->get('session') : null;
        $formSession   = Qs::getCurrentSession();
        $classes       = MyClass::orderBy('name')->get();
        $categories    = FeeCategory::where('active', true)->get();

        $query = FeeStructure::with(['category', 'my_class'])
            ->orderByDesc('session')
            ->orderBy('my_class_id');
        if ($sessionFilter) {
            $query->where('session', $sessionFilter);
        }
        $structures = $query->get();

        $sessions = FeeStructure::distinct()->pluck('session')
            ->push($formSession)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('pages.finance.fees.structures', compact(
            'classes', 'categories', 'structures', 'sessions', 'sessionFilter', 'formSession'
        ));
    }
    public function storeStructure(Request $req) {
        $req->validate(['fee_category_id'=>'required|exists:fee_categories,id','my_class_id'=>'required|exists:my_classes,id','session'=>'required|string','amount'=>'required|numeric|min:0','installments'=>'required|integer|min:1']);
        if (FeeStructure::where('fee_category_id',$req->fee_category_id)->where('my_class_id',$req->my_class_id)->where('session',$req->session)->exists()) {
            return redirect()->route('fees.structures')
                ->with('flash_danger','Structure already exists for this category/class/session.');
        }
        FeeStructure::create($req->only('fee_category_id','my_class_id','session','amount','installments'));
        return redirect()->route('fees.structures')
            ->with('flash_success','Fee structure created.');
    }
    public function updateStructure(Request $req, $id) {
        $s = FeeStructure::findOrFail($id);
        $s->update($req->validate(['amount'=>'required|numeric|min:0','installments'=>'required|integer|min:1|max:12']));
        return redirect()->route('fees.structures')
            ->with('flash_success','Fee structure updated.');
    }
    public function destroyStructure($id) {
        $s = FeeStructure::withCount('invoices')->findOrFail($id);
        if ($s->invoices_count > 0) {
            return redirect()->route('fees.structures')
                ->with('flash_danger','Cannot delete: structure has invoices.');
        }
        $s->delete();
        return redirect()->route('fees.structures')
            ->with('flash_success','Fee structure deleted.');
    }

    public function invoices(Request $req) {
        $sessionFilter = $req->filled('session_filter') ? $req->get('session_filter') : null;
        $class_id      = $req->get('class_id');
        $status        = $req->get('status');
        $search        = $req->get('search');
        $classes       = MyClass::orderBy('name')->get();

        $query = StudentFeeInvoice::with(['student', 'fee_structure.category', 'fee_structure.my_class'])
            ->latest();
        if ($sessionFilter) {
            $query->where('session', $sessionFilter);
        }
        if ($class_id) {
            $query->whereHas('fee_structure', fn($q) => $q->where('my_class_id', $class_id));
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', fn($q2) => $q2->where('name', 'like', "%$search%"))
                  ->orWhere('invoice_no', 'like', "%$search%");
            });
        }

        $invoices = $query->paginate(20)->appends($req->query());
        $feeStructures = FeeStructure::with(['category', 'my_class'])
            ->orderByDesc('session')
            ->orderBy('my_class_id')
            ->get();
        $sessions = StudentFeeInvoice::distinct()->pluck('session')
            ->push(Qs::getCurrentSession())
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('pages.finance.fees.invoices', compact(
            'invoices', 'classes', 'sessionFilter', 'feeStructures', 'sessions'
        ));
    }

    public function assignFee(Request $req) {
        $req->validate([
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'my_class_id'      => 'nullable|exists:my_classes,id',
        ]);

        $result = $this->bulkGenerateInvoices(
            (int) $req->fee_structure_id,
            $req->my_class_id ? (int) $req->my_class_id : null
        );

        if ($result['created'] === 0 && $result['students'] === 0) {
            $redirect = $req->input('redirect_to') === 'invoices'
                ? redirect()->route('fees.invoices')
                : back();
            return $redirect->with('flash_danger', 'No active students found in the selected class.');
        }

        $msg = "Generated {$result['created']} invoice(s).";
        if ($result['skipped'] > 0) {
            $msg .= " {$result['skipped']} already had an invoice.";
        }

        if ($req->input('redirect_to') === 'invoices') {
            return redirect()->route('fees.invoices')->with('flash_success', $msg);
        }

        return back()->with('flash_success', $msg);
    }

    protected function bulkGenerateInvoices(int $structureId, ?int $classId = null): array
    {
        $struct = FeeStructure::findOrFail($structureId);
        $classId = $classId ?? $struct->my_class_id;

        $studentIds = StudentRecord::where('my_class_id', $classId)
            ->where('grad', 0)
            ->pluck('user_id');

        $created = 0;
        $skipped = 0;
        foreach ($studentIds as $sid) {
            if ($this->createInvoice($sid, $struct)) {
                $created++;
            } else {
                $skipped++;
            }
        }

        return [
            'created'  => $created,
            'skipped'  => $skipped,
            'students' => $studentIds->count(),
        ];
    }

    protected function createInvoice($studentId, FeeStructure $structure): bool
    {
        if (StudentFeeInvoice::where('student_id', $studentId)
            ->where('fee_structure_id', $structure->id)
            ->where('session', $structure->session)
            ->exists()) {
            return false;
        }

        $invoice = StudentFeeInvoice::create([
            'invoice_no'      => 'INV-' . strtoupper(substr(uniqid(), -8)),
            'student_id'      => $studentId,
            'fee_structure_id'=> $structure->id,
            'session'         => $structure->session,
            'original_amount' => $structure->amount,
            'discount'        => 0,
            'fine'            => 0,
            'net_amount'      => $structure->amount,
            'amount_paid'     => 0,
            'balance'         => $structure->amount,
            'status'          => 'unpaid',
            'due_date'        => now()->addDays(30)->toDateString(),
        ]);

        \App\Services\DiscountService::applyAutomaticDiscountToInvoice($invoice);

        return true;
    }
    public function invoiceDetail($id) {
        $invoice = $this->findInvoice($id);
        $invoice->load(['student', 'fee_structure.category', 'fee_structure.my_class', 'payments.collector']);
        $installment_no = $invoice->payments()->count() + 1;
        return view('pages.finance.fees.invoice_detail', compact('invoice', 'installment_no'));
    }

    public function recordPayment(Request $req, $id) {
        $inv = $this->findInvoice($id);

        if ($inv->balance <= 0) {
            return $this->redirectToInvoice($inv)->with('flash_danger', 'This invoice is already fully paid.');
        }

        $req->validate([
            'amount'          => 'required|numeric|min:0.01|max:' . $inv->balance,
            'payment_method'  => 'required|in:cash,bank_transfer,mobile_money,chapa',
            'transaction_ref' => 'nullable|string|max:100',
            'notes'           => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($inv, $req) {
            $installmentNo = $inv->payments()->count() + 1;
            FeePayment::create([
                'receipt_no'      => 'REC-' . strtoupper(substr(uniqid(), -8)),
                'invoice_id'      => $inv->id,
                'student_id'      => $inv->student_id,
                'collected_by'    => Auth::id(),
                'amount'          => $req->amount,
                'installment_no'  => $installmentNo,
                'payment_method'  => $req->payment_method,
                'transaction_ref' => $req->transaction_ref,
                'notes'           => $req->notes,
                'paid_at'         => now(),
            ]);
            $inv->refresh()->syncStatus();
        });

        return $this->redirectToInvoice($inv)->with('flash_success', 'Payment recorded successfully.');
    }

    public function applyDiscount(Request $req, $id) {
        $inv = $this->findInvoice($id);

        $req->validate([
            'discount'        => 'required|numeric|min:0|max:' . $inv->original_amount,
            'discount_reason' => 'required|string|max:200',
        ]);

        $inv->discount        = $req->discount;
        $inv->discount_reason = $req->discount_reason;
        $inv->net_amount      = max(0, $inv->original_amount - $inv->discount + ($inv->fine ?? 0));
        $inv->save();
        $inv->syncStatus();

        return $this->redirectToInvoice($inv)->with('flash_success', 'Discount applied.');
    }

    public function applyFine(Request $req, $id) {
        $inv = $this->findInvoice($id);

        $req->validate([
            'fine'        => 'required|numeric|min:0',
            'fine_reason' => 'required|string|max:200',
        ]);

        $inv->fine        = $req->fine;
        $inv->fine_reason = $req->fine_reason;
        $inv->net_amount  = max(0, $inv->original_amount - ($inv->discount ?? 0) + $inv->fine);
        $inv->save();
        $inv->syncStatus();

        return $this->redirectToInvoice($inv)->with('flash_success', 'Fine applied.');
    }

    protected function findInvoice($id): StudentFeeInvoice
    {
        return StudentFeeInvoice::findOrFail($id);
    }

    protected function redirectToInvoice(StudentFeeInvoice $invoice)
    {
        return redirect()->route('fees.invoice', Qs::hash($invoice->id));
    }
    public function payments(Request $req) {
        $query = FeePayment::with(['student','invoice.fee_structure.category','collector'])->latest();
        if ($req->filled('search'))    $query->whereHas('student', fn($q) => $q->where('name','like','%'.$req->search.'%'));
        if ($req->filled('method'))    $query->where('payment_method',$req->method);
        if ($req->filled('date_from')) $query->whereDate('paid_at','>=',$req->date_from);
        if ($req->filled('date_to'))   $query->whereDate('paid_at','<=',$req->date_to);
        $total_today = FeePayment::whereDate('paid_at',today())->sum('amount');
        $total_month = FeePayment::whereYear('paid_at',now()->year)->whereMonth('paid_at',now()->month)->sum('amount');
        $payments = $query->paginate(20)->appends($req->query());
        return view('pages.finance.fees.payments', compact('payments','total_today','total_month'));
    }
    public function receipt($id) {
        $payment = FeePayment::with(['student','invoice.fee_structure.category','invoice.fee_structure.my_class','collector'])->findOrFail($id);
        $settings = \App\Models\Setting::pluck('description','type')->toArray();
        return view('pages.finance.fees.receipt', compact('payment','settings'));
    }
    public function pendingList(Request $req) {
        $session=$req->get('session_filter',Qs::getCurrentSession()); $class_id=$req->get('class_id');
        $classes=MyClass::orderBy('name')->get();
        $query=StudentFeeInvoice::whereIn('status',['unpaid','partial'])->with(['student','fee_structure.category','fee_structure.my_class'])->where('session',$session);
        if ($class_id) $query->whereHas('fee_structure', fn($q) => $q->where('my_class_id',$class_id));
        $invoices=$query->get(); $total_pending=$invoices->sum('balance');
        return view('pages.finance.fees.pending', compact('invoices','classes','session','total_pending'));
    }
    public function report(Request $req) {
        $session=$req->get('session',Qs::getCurrentSession()); $class_id=$req->get('class_id');
        $classes=MyClass::orderBy('name')->get();
        $base=fn()=>StudentFeeInvoice::where('session',$session)->when($class_id,fn($q)=>$q->whereHas('fee_structure',fn($q2)=>$q2->where('my_class_id',$class_id)));
        $total_invoiced=$base()->sum('net_amount'); $total_collected=$base()->sum('amount_paid'); $total_balance=$base()->sum('balance');
        $count_paid=$base()->where('status','paid')->count(); $count_partial=$base()->where('status','partial')->count(); $count_unpaid=$base()->where('status','unpaid')->count();
        $byCategory=$base()->with('fee_structure.category')->get()->groupBy(fn($i)=>optional(optional($i->fee_structure)->category)->name??'Unknown')->map(fn($g)=>['invoiced'=>$g->sum('net_amount'),'collected'=>$g->sum('amount_paid'),'balance'=>$g->sum('balance'),'count'=>$g->count()]);
        $byClass=$base()->with('fee_structure.my_class')->get()->groupBy(fn($i)=>optional(optional($i->fee_structure)->my_class)->name??'Unknown')->map(fn($g)=>['invoiced'=>$g->sum('net_amount'),'collected'=>$g->sum('amount_paid'),'balance'=>$g->sum('balance'),'count'=>$g->count()]);
        $isLite=DB::connection()->getDriverName()==='sqlite';
        $monthly=FeePayment::whereYear('paid_at',now()->year)->selectRaw($isLite?"strftime('%m',paid_at) as month,SUM(amount) as total":"MONTH(paid_at) as month,SUM(amount) as total")->groupBy('month')->orderBy('month')->pluck('total','month');
        if ($req->get('export')==='csv') return $this->exportReportCsv($session,$byCategory,$byClass);
        return view('pages.finance.fees.report', compact('session','class_id','classes','total_invoiced','total_collected','total_balance','count_paid','count_partial','count_unpaid','byCategory','byClass','monthly'));
    }
    protected function exportReportCsv($session,$byCategory,$byClass) {
        $h=['Content-Type'=>'text/csv','Content-Disposition'=>"attachment; filename=fee_report_$session.csv"];
        return response()->stream(function() use ($byCategory,$byClass) {
            $f=fopen('php://output','w');
            fputcsv($f,['Category','Invoices','Invoiced','Collected','Balance']);
            foreach ($byCategory as $n=>$r) fputcsv($f,[$n,$r['count'],$r['invoiced'],$r['collected'],$r['balance']]);
            fputcsv($f,[]);
            fputcsv($f,['Class','Invoices','Invoiced','Collected','Balance']);
            foreach ($byClass as $n=>$r) fputcsv($f,[$n,$r['count'],$r['invoiced'],$r['collected'],$r['balance']]);
            fclose($f);
        },200,$h);
    }
}
