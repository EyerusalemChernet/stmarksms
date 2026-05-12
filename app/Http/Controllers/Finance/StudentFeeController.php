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
    public function __construct() { $this->middleware('hr_manager'); }

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
        $session = $req->get('session', Qs::getCurrentSession());
        $classes = MyClass::orderBy('name')->get();
        $categories = FeeCategory::where('active',true)->get();
        $structures = FeeStructure::with(['category','my_class'])->where('session',$session)->get();
        return view('pages.finance.fees.structures', compact('classes','categories','structures','session'));
    }
    public function storeStructure(Request $req) {
        $req->validate(['fee_category_id'=>'required|exists:fee_categories,id','my_class_id'=>'required|exists:my_classes,id','session'=>'required|string','amount'=>'required|numeric|min:0','installments'=>'required|integer|min:1']);
        if (FeeStructure::where('fee_category_id',$req->fee_category_id)->where('my_class_id',$req->my_class_id)->where('session',$req->session)->exists())
            return back()->with('flash_danger','Structure already exists for this category/class/session.');
        FeeStructure::create($req->only('fee_category_id','my_class_id','session','amount','installments'));
        return back()->with('flash_success','Fee structure created.');
    }
    public function updateStructure(Request $req, $id) {
        FeeStructure::findOrFail($id)->update($req->validate(['amount'=>'required|numeric|min:0','installments'=>'required|integer|min:1|max:12']));
        return back()->with('flash_success','Fee structure updated.');
    }
    public function destroyStructure($id) {
        $s = FeeStructure::withCount('invoices')->findOrFail($id);
        if ($s->invoices_count > 0) return back()->with('flash_danger','Cannot delete: structure has invoices.');
        $s->delete();
        return back()->with('flash_success','Fee structure deleted.');
    }

    public function invoices(Request $req) {
        $session = $req->get('session_filter', Qs::getCurrentSession());
        $class_id = $req->get('class_id'); $status = $req->get('status'); $search = $req->get('search');
        $classes = MyClass::orderBy('name')->get();
        $query = StudentFeeInvoice::with(['student','fee_structure.category','fee_structure.my_class'])->where('session',$session);
        if ($class_id) $query->whereHas('fee_structure', fn($q) => $q->where('my_class_id',$class_id));
        if ($status)   $query->where('status',$status);
        if ($search)   $query->where(function($q) use ($search) {
            $q->whereHas('student', fn($q2) => $q2->where('name','like',"%$search%"))->orWhere('invoice_no','like',"%$search%");
        });
        $invoices = $query->latest()->paginate(20)->appends($req->query());
        return view('pages.finance.fees.invoices', compact('invoices','classes','session'));
    }
    public function assignFee(Request $req) {
        $req->validate(['fee_structure_id'=>'required|exists:fee_structures,id','my_class_id'=>'nullable|exists:my_classes,id']);
        $struct = FeeStructure::findOrFail($req->fee_structure_id);
        if ($req->my_class_id)
            foreach (StudentRecord::where('my_class_id',$req->my_class_id)->where('grad',0)->pluck('user_id') as $sid)
                $this->createInvoice($sid,$struct);
        return back()->with('flash_success','Fees assigned.');
    }
    protected function createInvoice($sid, FeeStructure $s) {
        if (StudentFeeInvoice::where('student_id',$sid)->where('fee_structure_id',$s->id)->where('session',$s->session)->exists()) return;
        StudentFeeInvoice::create(['invoice_no'=>'INV-'.strtoupper(substr(md5(uniqid()),0,8)),'student_id'=>$sid,'fee_structure_id'=>$s->id,'session'=>$s->session,'original_amount'=>$s->amount,'net_amount'=>$s->amount,'balance'=>$s->amount,'status'=>'unpaid','due_date'=>now()->addDays(30)]);
    }
    public function invoiceDetail($id) {
        $invoice = StudentFeeInvoice::with(['student','fee_structure.category','fee_structure.my_class','payments.collector'])->findOrFail($id);
        $installment_no = $invoice->payments()->count() + 1;
        return view('pages.finance.fees.invoice_detail', compact('invoice','installment_no'));
    }
    public function recordPayment(Request $req, $id) {
        $inv = StudentFeeInvoice::findOrFail($id);
        $req->validate(['amount'=>'required|numeric|min:0.01|max:'.$inv->balance,'payment_method'=>'required|string','transaction_ref'=>'nullable|string','notes'=>'nullable|string']);
        DB::transaction(function() use ($inv,$req) {
            FeePayment::create(['receipt_no'=>'REC-'.strtoupper(substr(md5(uniqid()),0,8)),'invoice_id'=>$inv->id,'student_id'=>$inv->student_id,'collected_by'=>Auth::id(),'amount'=>$req->amount,'installment_no'=>$inv->payments()->count()+1,'payment_method'=>$req->payment_method,'transaction_ref'=>$req->transaction_ref,'notes'=>$req->notes,'paid_at'=>now()]);
            $inv->syncStatus();
        });
        return back()->with('flash_success','Payment recorded.');
    }
    public function applyDiscount(Request $req, $id) {
        $inv = StudentFeeInvoice::findOrFail($id);
        $req->validate(['discount'=>'required|numeric|min:0','discount_reason'=>'required|string']);
        $inv->discount=$req->discount; $inv->discount_reason=$req->discount_reason;
        $inv->net_amount=max(0,$inv->original_amount-$inv->discount+$inv->fine);
        $inv->syncStatus();
        return back()->with('flash_success','Discount applied.');
    }
    public function applyFine(Request $req, $id) {
        $inv = StudentFeeInvoice::findOrFail($id);
        $req->validate(['fine'=>'required|numeric|min:0','fine_reason'=>'required|string']);
        $inv->fine=$req->fine; $inv->fine_reason=$req->fine_reason;
        $inv->net_amount=max(0,$inv->original_amount-$inv->discount+$inv->fine);
        $inv->syncStatus();
        return back()->with('flash_success','Fine applied.');
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