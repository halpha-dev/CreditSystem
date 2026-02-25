<?php
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/notices.php';

if (!is_admin()) {
    die('دسترسی غیرمجاز');
}

global $pdo;

/* =========================
   پرداخت دستی جریمه
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['mark_paid'])) {
        $penalty_id = (int)$_POST['penalty_id'];

        $pdo->prepare("
            UPDATE penalties
            SET status='paid', paid_at=NOW()
            WHERE id=?
        ")->execute([$penalty_id]);
    }

    if (isset($_POST['waive_penalty'])) {
        $penalty_id = (int)$_POST['penalty_id'];

        $pdo->prepare("
            UPDATE penalties
            SET status='waived'
            WHERE id=?
        ")->execute([$penalty_id]);
    }
}

/* =========================
   دریافت جریمه‌ها
========================= */

$penalties = $pdo->query("
SELECT pe.*,
       i.amount AS installment_amount,
       i.due_date,
       i.status AS installment_status,
       t.id AS transaction_id,
       t.total_amount,
       t.final_amount,
       u.name AS user_name,
       u.email AS user_email,
       m.name AS merchant_name,
       p.name AS plan_name,
       p.penalty_rate,
       c.code AS credit_code,
       (SELECT status FROM kyc_requests 
            WHERE user_id=u.id 
            ORDER BY id DESC LIMIT 1) AS kyc_status,
       (SELECT COUNT(*) FROM reminders 
            WHERE related_id=i.id AND type='installment') AS reminder_count
FROM penalties pe
LEFT JOIN installments i ON pe.installment_id = i.id
LEFT JOIN transactions t ON i.transaction_id = t.id
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN users m ON t.merchant_id = m.id
LEFT JOIN installment_plans p ON t.plan_id = p.id
LEFT JOIN credit_codes c ON t.credit_code_id = c.id
ORDER BY pe.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-penalties">

    <h1>مدیریت جریمه‌ها</h1>

    <table class="admin-table">

        <thead>
        <tr>
            <th>کاربر</th>
            <th>KYC</th>
            <th>فروشنده</th>
            <th>تراکنش</th>
            <th>پلن</th>
            <th>کردیت‌کد</th>
            <th>قسط</th>
            <th>مبلغ جریمه</th>
            <th>یادآوری</th>
            <th>وضعیت</th>
            <th>عملیات</th>
        </tr>
        </thead>

        <tbody>

        <?php if (empty($penalties)): ?>
            <tr>
                <td colspan="11">جریمه‌ای ثبت نشده است.</td>
            </tr>
        <?php endif; ?>

        <?php foreach ($penalties as $row): ?>

            <tr>

                <!-- کاربر -->
                <td>
                    <?php echo htmlspecialchars($row['user_name']); ?>
                    <br>
                    <small><?php echo htmlspecialchars($row['user_email']); ?></small>
                </td>

                <!-- KYC -->
                <td>
                    <span class="badge 
                        <?php
                        if ($row['kyc_status'] === 'approved') echo 'success';
                        elseif ($row['kyc_status'] === 'pending') echo 'warning';
                        else echo 'danger';
                        ?>">
                        <?php echo $row['kyc_status'] ?? 'none'; ?>
                    </span>
                </td>

                <!-- فروشنده -->
                <td><?php echo htmlspecialchars($row['merchant_name']); ?></td>

                <!-- تراکنش -->
                <td>
                    #<?php echo $row['transaction_id']; ?>
                    <br>
                    <small>
                        <?php echo number_format($row['final_amount']); ?>
                    </small>
                </td>

                <!-- پلن -->
                <td>
                    <?php echo htmlspecialchars($row['plan_name']); ?>
                    <br>
                    <small>جریمه: <?php echo $row['penalty_rate']; ?>%</small>
                </td>

                <!-- کردیت کد -->
                <td>
                    <?php if ($row['credit_code']): ?>
                        <span class="badge success">
                            <?php echo htmlspecialchars($row['credit_code']); ?>
                        </span>
                    <?php else: ?>
                        <span class="badge secondary">ندارد</span>
                    <?php endif; ?>
                </td>

                <!-- قسط -->
                <td>
                    مبلغ: <?php echo number_format($row['installment_amount']); ?>
                    <br>
                    سررسید: <?php echo $row['due_date']; ?>
                </td>

                <!-- مبلغ جریمه -->
                <td>
                    <strong>
                        <?php echo number_format($row['amount']); ?>
                    </strong>
                </td>

                <!-- یادآوری -->
                <td>
                    <?php echo $row['reminder_count']; ?> ارسال
                </td>

                <!-- وضعیت -->
                <td>
                    <span class="badge 
                        <?php
                        if ($row['status'] === 'paid') echo 'success';
                        elseif ($row['status'] === 'waived') echo 'secondary';
                        else echo 'danger';
                        ?>">
                        <?php echo $row['status']; ?>
                    </span>
                </td>

                <!-- عملیات -->
                <td>

                    <?php if ($row['status'] === 'unpaid'): ?>

                        <form method="post" style="display:inline;">
                            <input type="hidden" name="penalty_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="mark_paid" class="btn small success">
                                ثبت پرداخت
                            </button>
                        </form>

                        <form method="post" style="display:inline;">
                            <input type="hidden" name="penalty_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="waive_penalty" class="btn small warning">
                                بخشودگی
                            </button>
                        </form>

                    <?php else: ?>
                        -
                    <?php endif; ?>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>
    </table>

</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>