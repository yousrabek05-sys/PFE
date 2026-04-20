<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    // Get all payments
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'patient') {
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$patient) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Patient profile not found'
                ], 404);
            }

            // Patient sees their own payments with receipts
            $payments = Payment::where('patient_id', $patient->id)
                ->with('receipt') 
                ->orderBy('payment_date', 'desc')
                ->get();

        } else {
            // Assistant or doctor sees all payments
            $payments = Payment::with([
                'patient.user', 
                'receipt',      
            ])
            ->orderBy('payment_date', 'desc')
            ->get();
        }

        return response()->json([
            'status' => 'success',
            'data'   => $payments
        ]);
    }

    // Save Payment + Generate Receipt
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'assistant') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only assistants can record payments'
            ], 403);
        }

        $request->validate([
            'patient_id'     => 'required|exists:patients,id',
            'amount'         => 'required|numeric|min:0.01', 
            'payment_date'   => 'required|date',
            'payment_method' => 'required|in:cash,card,other',
            'notes'          => 'nullable|string',
        ]);

        $payment = Payment::create([
            'patient_id'     => $request->patient_id,
            'assistant_id'   => $user->id,
            'amount'         => $request->amount,
            'payment_date'   => $request->payment_date,
            'payment_method' => $request->payment_method,
            'notes'          => $request->notes,
        ]);

        $receipt = $this->generateReceipt($payment);

        return response()->json([
            'status'  => 'success',
            'message' => 'Payment saved! Receipt generated',
            'data'    => [
                'payment' => $payment,
                'receipt' => $receipt,
            ]
        ], 201);
    }

    // Get receipt for a payment
    public function getReceipt(Request $request, $id)
    {
        $user = $request->user();

        $payment = Payment::with([
            'receipt',
            'patient.user'
        ])->find($id);
        if (!$payment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Payment not found'
            ], 404);
        }

        if ($user->role === 'patient') {
            $patient = Patient::where('user_id', $user->id)->first();

            if ($payment->patient_id !== $patient->id) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Unauthorized'
                ], 403);
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => $payment->receipt,
            // Frontend uses this URL to download the PDF
            'pdf_url' => asset('storage/' . $payment->receipt->pdf_path)
        ]);
    }

    // Generate Receipt + PDF
    private function generateReceipt(Payment $payment)
    {
        $patient = Patient::with('user')->find($payment->patient_id);

        $pdf = Pdf::loadView('receipts.template', [
            'payment'       => $payment,
            'patient'       => $patient,
            'receipt_number'=> 'REC-' . strtoupper(Str::random(8)),
            'issue_date'    => now()->toDateString(),
        ]);

        $filename   = 'receipts/REC-' . $payment->id . '-' . time() . '.pdf';
        $pdfContent = $pdf->output();
        \Storage::disk('public')->put($filename, $pdfContent);

        $receipt = Receipt::create([
            'payment_id'     => $payment->id,
            'receipt_number' => 'REC-' . strtoupper(Str::random(8)),
            'issue_date'     => now()->toDateString(),
            'amount'         => $payment->amount,
            'pdf_path'       => $filename,
        ]);

        return $receipt;
    }
}