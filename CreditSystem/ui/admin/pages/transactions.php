<?php
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/notices.php';

if (!is_admin()) {
    die('دسترسی غیرمجاز');
}

global $pdo;

/* =========================
   فیلتر فروشنده
========================= */

$merchant_filter = isset($_GET['merchant_id']) ? (int)$_GET['merchant_id'] : null;

/* =========================
   دریافت تراکنش‌ها
========================= */

$query = "
SELECT t.*,
       u.name AS user_name,
       u.email AS user_email,
       m.name AS merchant_name,
       p.name AS plan_name,
       p.installment_count,
       c.code AS credit_code,
       c.type AS credit_type,
       c.value AS credit_value,
       (SELECT status FROM kyc_requests 
            WHERE user_id = t.user_id 
            ORDER BY id DESC LIMIT 1) AS user_kyc_status,
       (SELECT COUNT(*) FROM installments 
            WHERE transaction_id = t.id) AS total_installments,
       (SELECT COUNT(*) FROM installments 
            WHERE transaction_id = t.id AND status='overdue') AS overdue_installments,
       (SELECT COUNT(*) FROM penalties 
            WHERE installment_id IN 
                (SELECT id FROM installments WHERE transaction_id=t.id)
            AND status='unpaid') AS unpaid_penalties
FROM transactions t
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN users m ON t.merchant_id = m.id
LEFT JOIN installment_plans p ON t.plan_id = p.id
LEFT JOIN credit_codes c ON t.credit_code_id = c.id
";

if ($merchant_filter) {
    $query .= " WHERE t.merchant_id = " . $merchant_filter;
}

$query .= " ORDER BY t.created_at DESC";

$transactions = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-transactions">

    <h1>مدیریت تراکنش‌ها</h1>

    <table class="admin-table">
        <thead>
        <tr>
            <th>شناسه</th>
            <th>کاربر</th>
            <th>KYC</th>
            <th>فروشنده</th>
            <th>پلن</th>
            <th>مبلغ کل</th>
            <th>مبلغ نهایی</th>
            <th>کردیت کد</th>
            <th>اقساط</th>
            <th>معوق</th>
            <th>جریمه</th>
            <th>وضعیت</th>
            <th>تاریخ</th>
        </tr>
        </thead>

        <tbody>

        <?php if (empty($transactions)): ?>
            <tr>
                <td colspan="13">تراکنشی یافت نشد.</td>
            </tr>
        <?php endif; ?>

        <?php foreach ($transactions as $tx): ?>

            <tr>

                <td>#<?php echo $tx['id']; ?></td>

                <!-- کاربر -->
                <td>
                    <?php echo htmlspecialchars($tx['user_name']); ?>
                    <br>
                    <small><?php echo htmlspecialchars($tx['user_email']); ?></small>
                </td>

                <!-- KYC -->
                <td>
                    <span class="badge 
                        <?php
                        if ($tx['user_kyc_status'] === 'approved') echo 'success';
                        elseif ($tx['user_kyc_status'] === 'pending') echo 'warning';
                        else echo 'danger';
                        ?>">
                        <?php echo $tx['user_kyc_status']; ?>
                    </span>
                </td>

                <!-- فروشنده -->
                <td><?php echo htmlspecialchars($tx['merchant_name']); ?></td>

                <!-- پلن -->
                <td>
                    <?php echo htmlspecialchars($tx['plan_name']); ?>
                    <br>
                    <small><?php echo (int)$tx['installment_count']; ?> قسط</small>
                </td>

                <!-- مبلغ -->
                <td><?php echo number_format($tx['total_amount']); ?></td>
                <td><?php echo number_format($tx['final_amount']); ?></td>

                <!-- کردیت کد -->
                <td>
                    <?php if ($tx['credit_code']): ?>
                        <span class="badge success">
                            <?php echo htmlspecialchars($tx['credit_code']); ?>
                        </span>
                        <br>
                        <small>
                            <?php echo $tx['credit_type']; ?> -
                            <?php echo $tx['credit_value']; ?>
                        </small>
                    <?php else: ?>
                        <span class="badge secondary">ندارد</span>
                    <?php endif; ?>
                </td>

                <!-- اقساط -->
                <td><?php echo $tx['total_installments']; ?></td>

                <!-- معوق -->
                <td>
                    <span class="badge <?php echo $tx['overdue_installments'] > 0 ? 'danger' : 'success'; ?>">
                        <?php echo $tx['overdue_installments']; ?>
                    </span>
                </td>

                <!-- جریمه -->
                <td>
                    <span class="badge <?php echo $tx['unpaid_penalties'] > 0 ? 'danger' : 'success'; ?>">
                        <?php echo $tx['unpaid_penalties']; ?>
                    </span>
                </td>

                <!-- وضعیت تراکنش -->
                <td>
                    <span class="badge 
                        <?php
                        if ($tx['status'] === 'completed') echo 'success';
                        elseif ($tx['status'] === 'pending') echo 'warning';
                        else echo 'danger';
                        ?>">
                        <?php echo $tx['status']; ?>
                    </span>
                </td>

                <!-- تاریخ -->
                <td><?php echo $tx['created_at']; ?></td>

            </tr>

        <?php endforeach; ?>

        </tbody>
    </table>

</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>