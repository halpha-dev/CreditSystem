<?php
namespace CreditSystem\Includes\services;

use CreditSystem\domain\InstallmentPlan;
use CreditSystem\domain\Installment;
// اصلاح آدرس Repository برای هماهنگی با فایل قبلی
use CreditSystem\Includes\Database\Repositories\InstallmentRepository;

class InstallmentPlanService {

    /** @var InstallmentRepository */
    protected $installmentRepository;

    /** @var array */
    protected $allowedPlans = [6, 9];

    /**
     * اصلاح شده: دریافت ریپوزیتوری از ورودی (Dependency Injection)
     */
    public function __construct($installmentRepository = null) {
        // اگر ریپوزیتوری پاس داده نشد، خودش یکی بسازد (برای سازگاری با کدهای قبلی شما)
        $this->installmentRepository = $installmentRepository ?: new InstallmentRepository();
    }

    /**
     * @return InstallmentPlan
     */
/**
 * ایجاد یک پلن اقساطی جدید از داده‌های فرم
 */
    public function create($data) {
        // استخراج داده‌ها با مقادیر پیش‌فرض برای جلوگیری از خطای کلید ناموجود
        $title         = isset($data['title']) ? sanitize_text_field($data['title']) : '';
        $months        = isset($data['months']) ? (int)$data['months'] : 0;
        $interestRate  = isset($data['interest_rate']) ? (float)$data['interest_rate'] : 0;
        $penaltyRate   = isset($data['penalty_rate']) ? (float)$data['penalty_rate'] : 0;
        $reminderDays  = isset($data['reminder_days']) ? (int)$data['reminder_days'] : 0;
        $isActive      = isset($data['is_active']) ? 1 : 0;

    // اعتبار سنجی ساده
    if (empty($title) || $months <= 0) {
        throw new \Exception('لطفاً تمامی فیلدهای اجباری را پر کنید.');
    }

    // ذخیره در دیتابیس از طریق ریپوزیتوری
    // توجه: متد insertPlan باید در InstallmentRepository این فیلدها را بپذیرد
    return $this->installmentRepository->insertPlan([
        'title'         => $title,
        'months'        => $months,
        'interest_rate' => $interestRate,
        'penalty_rate'  => $penaltyRate,
        'reminder_days' => $reminderDays,
        'is_active'     => $isActive,
        'created_at'    => current_time('mysql')
    ]);
        // دقت کنید: متد insertPlan باید در InstallmentRepository تعریف شده باشد
        $planId = $this->installmentRepository->insertPlan($plan);
        $plan->setId($planId);

        $this->generateInstallments($plan, $startDate);

        return $plan;
    }

    protected function generateInstallments($plan, $startDate) {
        $dueDate = new \DateTime($startDate);
        $totalAmount = $plan->getTotalAmount();
        $monthlyAmount = $plan->getMonthlyAmount();
        $sumGenerated = 0;

        for ($i = 1; $i <= (int)$plan->getMonths(); $i++) {
            
            // رفع مشکل اختلاف گرد کردن در قسط آخر
            if ($i === (int)$plan->getMonths()) {
                $currentInstallmentAmount = $totalAmount - $sumGenerated;
            } else {
                $currentInstallmentAmount = $monthlyAmount;
                $sumGenerated += $currentInstallmentAmount;
            }

            $installment = new Installment(
                null,
                $plan->getId(),
                $plan->getUserId(),
                $i,
                $dueDate->format('Y-m-d'),
                $currentInstallmentAmount,
                0,
                'unpaid',
                null,
                current_time('mysql')
            );

            // دقت کنید: متد insertInstallment باید در InstallmentRepository تعریف شده باشد
            $this->installmentRepository->insertInstallment($installment);

            $dueDate->modify('+1 month');
        }
    }
    /**
 * دریافت لیست تمام برنامه‌های اقساط برای نمایش در پنل مدیریت
 */ 
    public function getAll() {
    // فرض بر این است که در ریپوزیتوری متدی برای دریافت همه دارید
    // اگر ندارید، باید در InstallmentRepository آن را بسازید
    return $this->installmentRepository->findAll(); 
    }
}