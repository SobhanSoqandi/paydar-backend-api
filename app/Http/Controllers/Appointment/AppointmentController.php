<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Owner;
use App\Models\Salon;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string', 'max:15'],
            'service_id' => ['required', 'array', 'min:1'],
            'service_id.*' => ['integer', 'exists:services,id'],
            'start_time' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'salon_id' => ['required', 'integer', 'exists:salons,id'],
            'paid_price' => ['nullable', 'numeric'],
        ]);

        /*
         * این متغیر فقط زمانی مقدار می‌گیرد که
         * مشتری برای اولین بار ساخته شده باشد.
         */
        $newCustomerSms = null;

        $appointmentData = DB::transaction(function () use ($data, &$newCustomerSms) {

            $user = User::where(
                'phone',
                $data['phone_number']
            )->first();

            if (!$user) {

                /*
                 * ساخت رمز تصادفی برای مشتری جدید
                 */
                $password = bin2hex(random_bytes(5));

                $customerUser = User::create([
                    'phone' => $data['phone_number'],
                    'password_hash' => bcrypt($password),
                    'role_id' => $this->customerRoleId(),
                    'is_active' => true,
                ]);

                $customer = Customer::create([
                    'user_id' => $customerUser->id,
                    'salon_id' => $data['salon_id'],
                ]);

                Wallet::create([
                    'customer_id' => $customer->id,
                    'balance' => 0,
                ]);

                Discount::create([
                    'customer_id' => $customer->id,
                    'max_usage' => 1,
                    'is_active' => true,
                    'percent' => 10,
                    'title' => 'welcom',
                    'start_date' => now(),
                    'end_date' => now()->addDays(30),
                ]);

                /*
                 * SMS بعد از Commit شدن تراکنش ارسال می‌شود.
                 * بنابراین اگر SMS خراب شود، ساخت مشتری rollback نمی‌شود.
                 */
                $newCustomerSms = [
                    'phone' => $data['phone_number'],
                    'password' => $password,
                ];

            } else {

                $customer = Customer::where(
                    'user_id',
                    $user->id
                )->first();

                if (!$customer) {

                    $customer = Customer::create([
                        'user_id' => $user->id,
                        'salon_id' => $data['salon_id'],
                    ]);

                    Wallet::create([
                        'customer_id' => $customer->id,
                        'balance' => 0,
                    ]);

                    Discount::create([
                        'customer_id' => $customer->id,
                        'max_usage' => 1,
                        'is_active' => true,
                        'percent' => 10,
                        'title' => 'welcom',
                        'start_date' => now(),
                        'end_date' => now()->addDays(30),
                    ]);
                }
            }

            $appointment = Appointment::create([
                'customer_id' => $customer->id,
                'description' => $data['description'] ?? null,
                'salon_id' => $data['salon_id'],
                'start_time' => $data['start_time'],
                'paid_price' => $data['paid_price'] ?? null,
                'is_paid' => false,
                'IsDeleted' => false,
            ]);

            foreach ($data['service_id'] as $serviceId) {
                AppointmentService::create([
                    'appointment_id' => $appointment->id,
                    'service_id' => $serviceId,
                ]);
            }

            return [
                'appointment_id' => $appointment->id,
            ];
        });

        /*
         * ارسال SMS رمز عبور مشتری جدید
         *
         * این بخش خارج از transaction است تا
         * خطای SMS باعث rollback دیتابیس نشود.
         */
        if ($newCustomerSms) {

            try {

                $message =
                    "رمز عبور شما: " .
                    $newCustomerSms['password'];

                $smsService = app(SMSService::class);

                $smsService->send(
                    $newCustomerSms['phone'],
                    $message
                );

            } catch (\Throwable $e) {

                Log::error('New customer password SMS failed', [
                    'phone' => $newCustomerSms['phone'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->appointmentResponse(
            $appointmentData['appointment_id'],
            'نوبت با موفقیت ایجاد شد'
        );
    }

    public function index()
    {
        $appointments = Appointment::with([
            'customer.user.role',
            'services',
            'salon',
            'appointmentServices.service',
        ])->get();

        return response()->json([
            'data' => $appointments,
            'message' => 'نوبت ها با موفقیت دریافت شد',
        ]);
    }

    public function me(Request $request)
    {
        $payload = $request->attributes->get('auth_user');

        $user = User::with('role')->find(
            $payload['sub'] ?? null
        );

        if (!$user || !$user->role) {
            return response()->json([
                'data' => null,
                'message' => 'کاربر پیدا نشد',
            ], 404);
        }

        $role = strtolower($user->role->name);

        if ($role === 'customer') {

            $customer = Customer::where(
                'user_id',
                $user->id
            )->first();

            if (!$customer) {
                return response()->json([
                    'data' => null,
                    'message' => 'مشتری پیدا نشد',
                ], 404);
            }

            $appointments = Appointment::with([
                'customer.user.role',
                'services',
                'salon',
                'appointmentServices.service',
            ])
                ->where('customer_id', $customer->id)
                ->get();

        } elseif ($role === 'owner') {

            $owner = Owner::where(
                'user_id',
                $user->id
            )->first();

            if (!$owner) {
                return response()->json([
                    'data' => null,
                    'message' => 'مالک پیدا نشد',
                ], 404);
            }

            $salon = Salon::where(
                'owner_id',
                $owner->id
            )
                ->where('IsDeleted', false)
                ->first();

            if (!$salon) {
                return response()->json([
                    'data' => null,
                    'message' => 'سالن پیدا نشد',
                ], 404);
            }

            $appointments = Appointment::with([
                'customer.user.role',
                'services',
                'salon',
                'appointmentServices.service',
            ])
                ->where('salon_id', $salon->id)
                ->get();

        } else {

            return response()->json([
                'data' => null,
                'message' => 'نقش کاربر معتبر نیست',
            ], 400);
        }

        return response()->json([
            'data' => $appointments,
            'message' => 'اطلاعات با موفقیت دریافت شد',
        ]);
    }

    public function search(Request $request)
    {
        $query = Appointment::with([
            'customer.user.role',
            'services',
            'salon',
            'appointmentServices.service',
        ]);

        if ($request->filled('customer_id')) {
            $query->where(
                'customer_id',
                $request->customer_id
            );
        }

        if ($request->filled('salon_id')) {
            $query->where(
                'salon_id',
                $request->salon_id
            );
        }

        if ($request->filled('service_id')) {
            $query->whereHas(
                'services',
                fn ($q) => $q->where(
                    'services.id',
                    $request->service_id
                )
            );
        }

        if ($request->filled('phone')) {
            $query->whereHas(
                'customer.user',
                fn ($q) => $q->where(
                    'phone',
                    $request->phone
                )
            );
        }

        if ($request->filled('start_date')) {
            $query->whereDate(
                'start_time',
                $request->start_date
            );
        }

        if ($request->filled('end_date')) {
            $query->whereDate(
                'start_time',
                $request->end_date
            );
        }

        if ($request->has('paid') && $request->paid !== null) {
            $query->where(
                'is_paid',
                filter_var(
                    $request->paid,
                    FILTER_VALIDATE_BOOLEAN
                )
            );
        }

        $page = max(
            (int) $request->input('page', 1),
            1
        );

        $pageSize = max(
            (int) $request->input('page_size', 10),
            1
        );

        $paginator = $query
            ->distinct()
            ->paginate(
                $pageSize,
                ['*'],
                'page',
                $page
            );

        return response()->json([
            'data' => [
                'appointment' => $paginator->items(),
                'user' => null,
                'page_size' => $pageSize,
                'total' => $paginator->total(),
                'page' => $page,
                'total_page' => $paginator->lastPage(),
            ],
            'message' => 'اطلاعات با موفقیت دریافت شد',
        ]);
    }

    public function show(int $appointment_id)
    {
        $appointment = Appointment::with([
            'customer.user.role',
            'services',
            'salon',
            'appointmentServices.service',
        ])->find($appointment_id);

        if (!$appointment) {
            return response()->json([
                'data' => null,
                'message' => 'Appointment not found',
            ], 404);
        }

        return response()->json([
            'data' => $appointment,
            'message' => 'نوبت پیدا شد',
        ]);
    }

    public function updateServices(Request $request)
    {
        $data = $request->validate([
            'appointment_id' => [
                'required',
                'integer',
                'exists:appointments,id',
            ],
            'appointment_services' => [
                'nullable',
                'array',
            ],
            'appointment_services.*' => [
                'integer',
                'exists:services,id',
            ],
        ]);

        $serviceIds = $data['appointment_services'] ?? [];

        AppointmentService::where(
            'appointment_id',
            $data['appointment_id']
        )->whereNotIn(
            'service_id',
            $serviceIds
        )->delete();

        foreach ($serviceIds as $serviceId) {
            AppointmentService::firstOrCreate([
                'appointment_id' => $data['appointment_id'],
                'service_id' => $serviceId,
            ]);
        }

        return response()->json([
            'data' => null,
            'message' => 'اطلاعات با موفقیت ویرایش شد',
        ]);
    }

    public function update(
        Request $request,
        int $appointment_id
    ) {
        $appointment = Appointment::find($appointment_id);

        if (!$appointment) {
            return response()->json([
                'data' => null,
                'message' => 'Appointment not found',
            ], 404);
        }

        $data = $request->validate([
            'start_time' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'paid_price' => ['nullable', 'numeric'],
        ]);

        $appointment->update(
            array_filter(
                $data,
                fn ($value) => $value !== null
            )
        );

        return $this->appointmentResponse(
            $appointment->id,
            'نوبت با موفقیت ویرایش شد'
        );
    }

    public function delete(int $appointment_id)
    {
        $appointment = Appointment::withoutGlobalScopes()
            ->find($appointment_id);

        if (!$appointment) {
            return response()->json([
                'data' => null,
                'message' => 'Appointment not found',
            ], 404);
        }

        $appointment->IsDeleted = true;
        $appointment->DeletedAt = now();
        $appointment->save();

        return response()->json([
            'data' => $appointment->fresh(),
            'message' => 'نوبت با موفقیت حذف شد',
        ]);
    }

    public function pay(Request $request)
    {

      Log::info('PAY METHOD REACHED', [
        'data' => $request->all(),
    ]);

        $data = $request->validate([
            'pay_price' => ['required', 'numeric'],
            'appointment_id' => [
                'required',
                'integer',
                'exists:appointments,id',
            ],
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
            ],
        ]);

        $paymentData = DB::transaction(function () use ($data) {

            $appointment = Appointment::find(
                $data['appointment_id']
            );

            if (!$appointment) {
                return [
                    'error' => response()->json([
                        'data' => null,
                        'message' => 'Appointment not found',
                    ], 404),
                ];
            }

            $salon = Salon::find(
                $appointment->salon_id
            );

            if (!$salon) {
                return [
                    'error' => response()->json([
                        'data' => null,
                        'message' => 'سالن پیدا نشد',
                    ], 404),
                ];
            }

            $wallet = Wallet::where(
                'customer_id',
                $data['customer_id']
            )->first();

            if (!$wallet) {
                return [
                    'error' => response()->json([
                        'data' => null,
                        'message' => 'کیف پول یافت نشد',
                    ], 400),
                ];
            }

            $customer = Customer::with('user')
                ->find($data['customer_id']);

            if (!$customer || !$customer->user) {
                return [
                    'error' => response()->json([
                        'data' => null,
                        'message' => 'اطلاعات مشتری یافت نشد',
                    ], 404),
                ];
            }

            $appointment->paid_price =
                $data['pay_price'];

            $appointment->is_paid = true;

            $appointment->save();

            $cashback = (
                (float) $data['pay_price']
                * (float) $salon->back_percent
            ) / 100;

            $wallet->balance =
                (float) $wallet->balance + $cashback;

            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'appointment_id' => $appointment->id,
                'amount' => $cashback,
                'type' => 'CASHBACK',
            ]);

            return [
                'appointment_id' => $appointment->id,
                'cashback' => $cashback,
                'phone' => $customer->user->phone,
            ];
        });

        if (isset($paymentData['error'])) {
            return $paymentData['error'];
        }

        /*
         * ارسال SMS بعد از موفقیت کامل تراکنش پرداخت
         *
         * اگر SMS شکست بخورد:
         * پرداخت و cashback همچنان موفق باقی می‌مانند.
         */
        
        Log::info('PAYMENT SMS: reached SMS section', [
    'appointment_id' => $paymentData['appointment_id'],
    'phone' => $paymentData['phone'],
    'cashback' => $paymentData['cashback'],
]);

try {

    $cashbackText = number_format(
        (float) $paymentData['cashback']
    );

    $message =
        "از پرداخت شما سپاسگزاریم.\n" .
        "مبلغ {$cashbackText} تومان به اعتبار کیف پول شما اضافه شد.";

    Log::info('PAYMENT SMS: sending', [
        'phone' => $paymentData['phone'],
        'cashback' => $paymentData['cashback'],
    ]);

    $smsService = app(SMSService::class);

    $smsService->send(
        $paymentData['phone'],
        $message
    );

    Log::info('PAYMENT SMS: sent successfully');

} catch (\Throwable $e) {

    Log::error('Payment cashback SMS failed', [
        'appointment_id' => $paymentData['appointment_id'],
        'phone' => $paymentData['phone'],
        'cashback' => $paymentData['cashback'],
        'error' => $e->getMessage(),
    ]);
}

        return $this->appointmentResponse(
            $paymentData['appointment_id'],
            'عملیات با موفقیت انجام شد'
        );
    }

    private function customerRoleId(): int
    {
        $role = \App\Models\Role::where(
            'name',
            'customer'
        )->first();

        if (!$role) {
            abort(
                404,
                'نقش customer پیدا نشد'
            );
        }

        return $role->id;
    }

    private function appointmentResponse(
        int $appointmentId,
        string $message
    ) {
        $appointment = Appointment::with([
            'customer.user.role',
            'services',
            'salon',
            'appointmentServices.service',
        ])->find($appointmentId);

        return response()->json([
            'data' => $appointment,
            'message' => $message,
        ]);
    }
}