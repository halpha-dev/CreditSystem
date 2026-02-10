<?php
/**
 * Admin Dashboard - CreditSystem
 */

use CreditSystem\Includes\Services\KycService;
use CreditSystem\Includes\Services\InstallmentService;
use CreditSystem\Includes\Services\TransactionService;
use CreditSystem\Includes\Services\MerchantService;
use CreditSystem\Includes\Security\PermissionPolicy;

PermissionPolicy::adminOnly();

$kycService = new KycService();
$installmentService = new InstallmentService();
$transactionService = new TransactionService();
$merchantService = new MerchantService();

/**
 * داده‌های خلاصه
 */
$pendingKycCount = count($kycService->getPendingList(100, 0));
$overdueInstallments = $installmentService->getOverdueCount();
$totalMerchants = $merchantService->getActiveCount();
$todayTransactionsSum = $transactionService->getTodayTotal();
?>

<div class="cs-admin-dashboard">

    <?php include CS_UI_ADMIN_PARTIALS . '/header.php'; ?>

    <div class="cs-dashboard-grid">

        <!-- کارت KYC -->
        <div class="cs-card cs-card-warning">
            <h3>احراز هویت‌های در انتظار</h3>
            <p class="cs-metric"><?php echo esc_html($pendingKycCount); ?></p>
            <a href="admin.php?page=creditsystem-kyc" class="cs-link">
                بررسی درخواست‌ها
            </a>
        </div>

        <!-- کارت اقساط معوق -->
        <div class="cs-card cs-card-danger">
            <h3>اقساط معوق</h3>
            <p class="cs-metric"><?php echo esc_html($overdueInstallments); ?></p>
            <a href="admin.php?page=creditsystem-installments" class="cs-link">
                مشاهده اقساط
            </a>
        </div>

        <!-- کارت فروشندگان -->
        <div class="cs-card cs-card-info">
            <h3>فروشندگان فعال</h3>
            <p class="cs-metric"><?php echo esc_html($totalMerchants); ?></p>
            <a href="admin.php?page=creditsystem-merchants" class="cs-link">
                مدیریت فروشندگان
            </a>
        </div>

        <!-- کارت تراکنش امروز -->
        <div class="cs-card cs-card-success">
            <h3>مجموع تراکنش امروز</h3>
            <p class="cs-metric">
                <?php echo number_format_i18n($todayTransactionsSum); ?> تومان
            </p>
            <a href="admin.php?page=creditsystem-transactions" class="cs-link">
                جزئیات تراکنش‌ها
            </a>
        </div>

    </div>

    <!-- بخش یادآوری و جریمه -->
    <div class="cs-section">
        <h2>یادآوری‌ها و جریمه‌ها</h2>

        <div class="cs-section-grid">
            <div class="cs-box">
                <h4>یادآوری‌های ارسال‌نشده</h4>
                <p>
                    <?php echo esc_html($installmentService->getPendingRemindersCount()); ?>
                </p>
                <a href="admin.php?page=creditsystem-reminders">مدیریت یادآوری‌ها</a>
            </div>

            <div class="cs-box">
                <h4>جریمه‌های فعال</h4>
                <p>
                    <?php echo esc_html($installmentService->getActivePenaltiesCount()); ?>
                </p>
                <a href="admin.php?page=creditsystem-penalties">مدیریت جریمه‌ها</a>
            </div>
        </div>
    </div>

    <!-- وضعیت پلن‌های اقساط -->
    <div class="cs-section">
        <h2>پلن‌های اقساط</h2>
        <p>
            مدیریت پلن‌های فعال و شرایط بازپرداخت کاربران
        </p>
        <a class="button button-primary" href="admin.php?page=creditsystem-installment-plans">
            مدیریت Installment Plan ها
        </a>
    </div>

    <?php include CS_UI_ADMIN_PARTIALS . '/footer.php'; ?>

</div>