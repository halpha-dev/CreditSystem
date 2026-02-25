<?php
// admin/pages/kyc-details.php

use CreditSystem\Includes\services\KycService;
use CreditSystem\Includes\Database\Repositories\KycRepository;
use CreditSystem\Includes\domain\KycRequest;
use CreditSystem\Includes\security\PermissionPolicy;

if (!defined('ABSPATH')) exit;

// دسترسی فقط برای ادمین
if (!PermissionPolicy::currentUserCan('manage_kyc')) {
    wp_die('دسترسی غیرمجاز');
}

$kycRepo = new KycRepository();
$kycService = new KycService($kycRepo);

$kycId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$kycRequest = $kycRepo->getById($kycId);

if (!$kycRequest) {
    wp_die('درخواست KYC یافت نشد.');
}

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reason = $_POST['reason'] ?? null;

    if ($action === 'approve') {
        $kycService->approve($kycRequest->id);
        $message = "درخواست KYC با موفقیت تأیید شد.";
    }

    if ($action === 'reject') {
        if (!$reason) {
            $error = "لطفاً دلیل رد را وارد کنید.";
        } else {
            $kycService->reject($kycRequest->id, $reason);
            $message = "درخواست KYC رد شد و دلیل ثبت شد.";
        }
    }
}

?>

<div class="wrap">
    <h1>جزئیات KYC</h1>

    <?php if (!empty($message)): ?>
        <div class="notice notice-success"><p><?php echo esc_html($message); ?></p></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
    <?php endif; ?>

    <table class="widefat striped">
        <tbody>
            <tr><th>کاربر</th><td><?php echo esc_html($kycRequest->user_name); ?></td></tr>
            <tr><th>ایمیل</th><td><?php echo esc_html($kycRequest->user_email); ?></td></tr>
            <tr><th>وضعیت</th><td><?php echo esc_html($kycRequest->status); ?></td></tr>
            <tr><th>تاریخ ثبت</th><td><?php echo esc_html($kycRequest->created_at); ?></td></tr>
            <?php if ($kycRequest->status === 'rejected'): ?>
                <tr><th>دلیل رد</th><td><?php echo esc_html($kycRequest->rejection_reason); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h2>عملیات</h2>

    <form method="post">
        <button type="submit" name="action" value="approve" class="button button-primary">تأیید</button>

        <button type="button" class="button button-secondary" onclick="document.getElementById('reject-reason').style.display='block';">رد</button>

        <div id="reject-reason" style="display:none; margin-top:10px;">
            <label for="reason">دلیل رد:</label>
            <textarea name="reason" id="reason" rows="3" required></textarea>
            <button type="submit" name="action" value="reject" class="button button-secondary">ثبت رد</button>
        </div>
    </form>

    <h2>اطلاعات اضافی</h2>
    <p><strong>پلن قسط انتخابی:</strong> <?php echo esc_html($kycRequest->installment_plan ?? '-'); ?></p>
    <p><strong>تعداد اقساط:</strong> <?php echo esc_html($kycRequest->installment_count ?? '-'); ?></p>
    <p><strong>فروشگاه مرتبط:</strong> <?php echo esc_html($kycRequest->merchant_name ?? '-'); ?></p>
    <p><strong>اقساط:</strong></p>
    <?php if (!empty($kycRequest->installments)): ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>قسط</th>
                    <th>سررسید</th>
                    <th>مبلغ پایه</th>
                    <th>جریمه</th>
                    <th>وضعیت</th>
                    <th>تاریخ پرداخت</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kycRequest->installments as $i => $inst): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo esc_html($inst->due_date); ?></td>
                        <td><?php echo esc_html(number_format($inst->base_amount)); ?></td>
                        <td><?php echo esc_html(number_format($inst->penalty_amount)); ?></td>
                        <td><?php echo esc_html($inst->status); ?></td>
                        <td><?php echo esc_html($inst->paid_at ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>هیچ اقساطی ثبت نشده است.</p>
    <?php endif; ?>

    <p><strong>تراکنش‌های فروشگاه:</strong></p>
    <?php if (!empty($kycRequest->merchant_transactions)): ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>تاریخ</th>
                    <th>کد اعتبار</th>
                    <th>مبلغ</th>
                    <th>وضعیت پرداخت</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kycRequest->merchant_transactions as $txn): ?>
                    <tr>
                        <td><?php echo esc_html($txn->created_at); ?></td>
                        <td><?php echo esc_html($txn->credit_code); ?></td>
                        <td><?php echo esc_html(number_format($txn->amount)); ?></td>
                        <td><?php echo esc_html($txn->status); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>هیچ تراکنشی برای فروشگاه ثبت نشده است.</p>
    <?php endif; ?>

</div>
