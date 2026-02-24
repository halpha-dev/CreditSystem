<?php
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/notices.php';

if (!is_admin()) {
    die('دسترسی غیرمجاز');
}

global $pdo;

/* =========================
   تغییر وضعیت فروشنده
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['toggle_status'])) {
        $merchant_id = (int)$_POST['merchant_id'];

        $stmt = $pdo->prepare("UPDATE users 
                               SET status = IF(status='active','inactive','active') 
                               WHERE id = ? AND role='merchant'");
        $stmt->execute([$merchant_id]);
    }

    if (isset($_POST['force_kyc_reset'])) {
        $merchant_id = (int)$_POST['merchant_id'];

        $stmt = $pdo->prepare("UPDATE kyc_requests 
                               SET status = 'pending' 
                               WHERE user_id = ?");
        $stmt->execute([$merchant_id]);
    }
}

/* =========================
   دریافت لیست فروشندگان
========================= */

$merchants = $pdo->query("
    SELECT u.*,
           (SELECT status FROM kyc_requests WHERE user_id = u.id ORDER BY id DESC LIMIT 1) AS kyc_status,
           (SELECT COUNT(*) FROM transactions WHERE merchant_id = u.id) AS total_transactions,
           (SELECT SUM(total_amount) FROM transactions WHERE merchant_id = u.id AND status='completed') AS total_sales,
           (SELECT COUNT(*) FROM installments WHERE merchant_id = u.id AND status='overdue') AS overdue_installments,
           (SELECT COUNT(*) FROM penalties WHERE merchant_id = u.id AND status='unpaid') AS unpaid_penalties,
           (SELECT COUNT(*) FROM credit_codes WHERE merchant_id = u.id) AS credit_codes_count
    FROM users u
    WHERE u.role = 'merchant'
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-merchants">

    <h1>مدیریت فروشندگان</h1>

    <table class="admin-table">
        <thead>
        <tr>
            <th>نام</th>
            <th>ایمیل</th>
            <th>وضعیت حساب</th>
            <th>KYC</th>
            <th>تراکنش‌ها</th>
            <th>فروش کل</th>
            <th>اقساط معوق</th>
            <th>جریمه پرداخت‌نشده</th>
            <th>کردیت کد</th>
            <th>عملیات</th>
        </tr>
        </thead>

        <tbody>

        <?php if (empty($merchants)): ?>
            <tr>
                <td colspan="10">فروشنده‌ای ثبت نشده است.</td>
            </tr>
        <?php endif; ?>

        <?php foreach ($merchants as $merchant): ?>

            <tr>

                <td><?php echo htmlspecialchars($merchant['name']); ?></td>
                <td><?php echo htmlspecialchars($merchant['email']); ?></td>

                <!-- وضعیت حساب -->
                <td>
                    <span class="badge <?php echo $merchant['status'] === 'active' ? 'success' : 'danger'; ?>">
                        <?php echo $merchant['status'] === 'active' ? 'فعال' : 'غیرفعال'; ?>
                    </span>
                </td>

                <!-- KYC -->
                <td>
                    <?php
                    $kyc = $merchant['kyc_status'] ?? 'none';
                    ?>
                    <span class="badge 
                        <?php 
                            if ($kyc === 'approved') echo 'success';
                            elseif ($kyc === 'pending') echo 'warning';
                            elseif ($kyc === 'rejected') echo 'danger';
                            else echo 'secondary';
                        ?>">
                        <?php echo $kyc; ?>
                    </span>
                </td>

                <!-- تراکنش -->
                <td><?php echo (int)$merchant['total_transactions']; ?></td>

                <!-- فروش -->
                <td>
                    <?php echo number_format((float)$merchant['total_sales']); ?> تومان
                </td>

                <!-- اقساط معوق -->
                <td>
                    <span class="badge <?php echo $merchant['overdue_installments'] > 0 ? 'danger' : 'success'; ?>">
                        <?php echo (int)$merchant['overdue_installments']; ?>
                    </span>
                </td>

                <!-- جریمه -->
                <td>
                    <span class="badge <?php echo $merchant['unpaid_penalties'] > 0 ? 'danger' : 'success'; ?>">
                        <?php echo (int)$merchant['unpaid_penalties']; ?>
                    </span>
                </td>

                <!-- کردیت کد -->
                <td>
                    <?php echo (int)$merchant['credit_codes_count']; ?>
                </td>

                <!-- عملیات -->
                <td>

                    <form method="post" style="display:inline;">
                        <input type="hidden" name="merchant_id" value="<?php echo $merchant['id']; ?>">
                        <button type="submit" name="toggle_status" class="btn small">
                            تغییر وضعیت
                        </button>
                    </form>

                    <form method="post" style="display:inline;">
                        <input type="hidden" name="merchant_id" value="<?php echo $merchant['id']; ?>">
                        <button type="submit" name="force_kyc_reset" class="btn small warning">
                            ریست KYC
                        </button>
                    </form>

                    <a href="transactions.php?merchant_id=<?php echo $merchant['id']; ?>" class="btn small">
                        تراکنش‌ها
                    </a>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>
    </table>

</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>‌