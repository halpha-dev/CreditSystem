<?php
/**
 * Installment Plans Management - Admin
 */

use CreditSystem\Includes\services\InstallmentPlanService;
use CreditSystem\Includes\security\PermissionPolicy;

if (!defined('ABSPATH')) exit;

// اصلاح فراخوانی استاتیک (طبق تغییرات قبلی در PermissionPolicy)
PermissionPolicy::adminOnly();

// نمونه‌سازی صحیح
$planService = new InstallmentPlanService();

/**
 * handle form submit
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        try {
            // اصلاح نام متغیر از $service به $planService
            // و ارسال داده‌ها به صورت آرایه (با فرض اینکه متد create را در مرحله قبل برای پذیرش آرایه اصلاح کردیم)
            $planService->create($_POST);
            echo '<div class="updated"><p>پلن با موفقیت ایجاد شد.</p></div>';
        } catch (\Exception $e) {
            echo '<div class="error"><p>خطا: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    if ($action === 'update') {
        $planService->update(intval($_POST['id']), [
            'title' => sanitize_text_field($_POST['title']),
            'months' => intval($_POST['months']),
            'interest_rate' => floatval($_POST['interest_rate']),
            'penalty_rate' => floatval($_POST['penalty_rate']),
            'reminder_days' => intval($_POST['reminder_days']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ]);
    }

    if ($action === 'toggle') {
        $planService->toggleStatus(intval($_POST['id']));
    }
}

// دریافت لیست پلن‌ها
$plans = $planService->getAll();
?>

<div class="wrap">
    <h1>مدیریت پلن‌های اقساط</h1>

    <!-- create plan -->
    <h2>ایجاد پلن جدید</h2>

    <form method="post" class="cs-form">
        <input type="hidden" name="action" value="create">

        <table class="form-table">
            <tr>
                <th>عنوان پلن</th>
                <td><input type="text" name="title" required></td>
            </tr>
            <tr>
                <th>تعداد اقساط (ماه)</th>
                <td><input type="number" name="months" min="1" required></td>
            </tr>
            <tr>
                <th>نرخ سود (%)</th>
                <td><input type="number" step="0.01" name="interest_rate" required></td>
            </tr>
            <tr>
                <th>نرخ جریمه دیرکرد (%)</th>
                <td><input type="number" step="0.01" name="penalty_rate" required></td>
            </tr>
            <tr>
                <th>یادآوری قبل از سررسید (روز)</th>
                <td><input type="number" name="reminder_days" min="0"></td>
            </tr>
            <tr>
                <th>فعال</th>
                <td><input type="checkbox" name="is_active" checked></td>
            </tr>
        </table>

        <button type="submit" class="button button-primary">ایجاد پلن</button>
    </form>

    <hr>

    <!-- plans list -->
    <h2>پلن‌های موجود</h2>

    <?php if (empty($plans)): ?>
        <p>هیچ پلنی ثبت نشده است.</p>
    <?php else: ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>عنوان</th>
                    <th>اقساط</th>
                    <th>سود</th>
                    <th>جریمه</th>
                    <th>یادآوری</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plans as $plan): ?>
                    <tr>
                        <td><?php echo esc_html($plan->id); ?></td>
                        <td><?php echo esc_html($plan->title); ?></td>
                        <td><?php echo esc_html($plan->months); ?> ماه</td>
                        <td><?php echo esc_html($plan->interest_rate); ?>%</td>
                        <td><?php echo esc_html($plan->penalty_rate); ?>%</td>
                        <td><?php echo esc_html($plan->reminder_days); ?> روز</td>
                        <td>
                            <?php echo $plan->is_active ? 'فعال' : 'غیرفعال'; ?>
                        </td>
                        <td>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo esc_attr($plan->id); ?>">
                                <button class="button">
                                    <?php echo $plan->is_active ? 'غیرفعال' : 'فعال'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>