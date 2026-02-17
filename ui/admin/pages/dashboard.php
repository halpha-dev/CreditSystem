<?php
require_once __DIR__ . '/../../partials/header.php';
require_once __DIR__ . '/../../partials/sidebar.php';
require_once __DIR__ . '/../../partials/notices.php';

if (!is_admin()) {
    die('دسترسی غیرمجاز');
}

global $pdo;

/* =========================
   آمار کلی
========================= */

// کاربران
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_merchants = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'merchant'")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

// KYC
$kyc_pending = $pdo->query("SELECT COUNT(*) FROM kyc_requests WHERE status = 'pending'")->fetchColumn();
$kyc_approved = $pdo->query("SELECT COUNT(*) FROM kyc_requests WHERE status = 'approved'")->fetchColumn();
$kyc_rejected = $pdo->query("SELECT COUNT(*) FROM kyc_requests WHERE status = 'rejected'")->fetchColumn();

// Installments
$total_installments = $pdo->query("SELECT COUNT(*) FROM installments")->fetchColumn();
$overdue_installments = $pdo->query("SELECT COUNT(*) FROM installments WHERE status = 'overdue'")->fetchColumn();
$active_plans = $pdo->query("SELECT COUNT(*) FROM installment_plans WHERE status = 'active'")->fetchColumn();

// Penalties
$total_penalties = $pdo->query("SELECT COUNT(*) FROM penalties")->fetchColumn();
$unpaid_penalties = $pdo->query("SELECT COUNT(*) FROM penalties WHERE status = 'unpaid'")->fetchColumn();

// Reminders
$pending_reminders = $pdo->query("SELECT COUNT(*) FROM reminders WHERE status = 'pending'")->fetchColumn();

// Credit Codes
$total_credit_codes = $pdo->query("SELECT COUNT(*) FROM credit_codes")->fetchColumn();
$active_credit_codes = $pdo->query("SELECT COUNT(*) FROM credit_codes WHERE status = 'active'")->fetchColumn();
$expired_credit_codes = $pdo->query("SELECT COUNT(*) FROM credit_codes WHERE expires_at < NOW()")->fetchColumn();

// فروش کل
$total_sales = $pdo->query("SELECT SUM(total_amount) FROM transactions WHERE status = 'completed'")->fetchColumn();
?>

<div class="admin-dashboard">

    <h1>داشبورد مدیریت سیستم اقساط</h1>

    <!-- آمار نقش‌ها -->
    <div class="card-grid">
        <div class="card">
            <h3>کاربران</h3>
            <p><?php echo number_format($total_users); ?></p>
        </div>
        <div class="card">
            <h3>فروشندگان</h3>
            <p><?php echo number_format($total_merchants); ?></p>
        </div>
        <div class="card">
            <h3>ادمین‌ها</h3>
            <p><?php echo number_format($total_admins); ?></p>
        </div>
    </div>

    <!-- KYC -->
    <h2>KYC</h2>
    <div class="card-grid">
        <div class="card warning">
            <h3>در انتظار بررسی</h3>
            <p><?php echo $kyc_pending; ?></p>
        </div>
        <div class="card success">
            <h3>تایید شده</h3>
            <p><?php echo $kyc_approved; ?></p>
        </div>
        <div class="card danger">
            <h3>رد شده</h3>
            <p><?php echo $kyc_rejected; ?></p>
        </div>
    </div>

    <!-- اقساط -->
    <h2>اقساط و پلن‌ها</h2>
    <div class="card-grid">
        <div class="card">
            <h3>کل اقساط</h3>
            <p><?php echo $total_installments; ?></p>
        </div>
        <div class="card danger">
            <h3>اقساط معوق</h3>
            <p><?php echo $overdue_installments; ?></p>
        </div>
        <div class="card">
            <h3>پلن‌های فعال</h3>
            <p><?php echo $active_plans; ?></p>
        </div>
    </div>

    <!-- جریمه -->
    <h2>جریمه‌ها</h2>
    <div class="card-grid">
        <div class="card">
            <h3>کل جریمه‌ها</h3>
            <p><?php echo $total_penalties; ?></p>
        </div>
        <div class="card danger">
            <h3>جریمه پرداخت نشده</h3>
            <p><?php echo $unpaid_penalties; ?></p>
        </div>
    </div>

    <!-- یادآوری -->
    <h2>یادآوری‌ها</h2>
    <div class="card">
        <h3>در انتظار ارسال</h3>
        <p><?php echo $pending_reminders; ?></p>
    </div>

    <!-- Credit Code -->
    <h2>کردیت کد</h2>
    <div class="card-grid">
        <div class="card">
            <h3>کل کدها</h3>
            <p><?php echo $total_credit_codes; ?></p>
        </div>
        <div class="card success">
            <h3>فعال</h3>
            <p><?php echo $active_credit_codes; ?></p>
        </div>
        <div class="card danger">
            <h3>منقضی شده</h3>
            <p><?php echo $expired_credit_codes; ?></p>
        </div>
    </div>

    <!-- فروش -->
    <h2>گردش مالی</h2>
    <div class="card highlight">
        <h3>مجموع فروش تکمیل شده</h3>
        <p><?php echo number_format($total_sales); ?> تومان</p>
    </div>

</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
