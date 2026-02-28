<?php
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/notices.php';

if (!is_admin()) {
    die('دسترسی غیرمجاز');
}

global $pdo;

/* =========================
   عملیات روی یادآوری
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['mark_sent'])) {
        $reminder_id = (int)$_POST['reminder_id'];

        $pdo->prepare("
            UPDATE reminders
            SET status='sent', sent_at=NOW()
            WHERE id=?
        ")->execute([$reminder_id]);
    }

    if (isset($_POST['cancel_reminder'])) {
        $reminder_id = (int)$_POST['reminder_id'];

        $pdo->prepare("
            UPDATE reminders
            SET status='cancelled'
            WHERE id=?
        ")->execute([$reminder_id]);
    }
}

/* =========================
   دریافت لیست یادآوری‌ها
========================= */

$reminders = $wpdb->query("
SELECT r.*,
       i.amount AS installment_amount,
       i.due_date,
       i.status AS installment_status,
       t.id AS transaction_id,
       t.final_amount,
       u.name AS user_name,
       u.email AS user_email,
       m.name AS merchant_name,
       p.name AS plan_name,
       p.reminder_days,
       c.code AS credit_code,
       (SELECT status FROM kyc_requests 
            WHERE user_id=u.id 
            ORDER BY id DESC LIMIT 1) AS kyc_status,
       (SELECT COUNT(*) FROM penalties 
            WHERE installment_id=i.id AND status='unpaid') AS unpaid_penalties
FROM reminders r
LEFT JOIN installments i ON r.related_id = i.id
LEFT JOIN transactions t ON i.transaction_id = t.id
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN users m ON t.merchant_id = m.id
LEFT JOIN installment_plans p ON t.plan_id = p.id
LEFT JOIN credit_codes c ON t.credit_code_id = c.id
WHERE r.type='installment'
ORDER BY r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-reminders">

    <h1>مدیریت یادآوری‌ها</h1>

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
            <th>جریمه فعال</th>
            <th>زمان‌بندی</th>
            <th>وضعیت</th>
            <th>عملیات</th>
        </tr>
        </thead>

        <tbody>

        <?php if (empty($reminders)): ?>
            <tr>
                <td colspan="11">یادآوری‌ای ثبت نشده است.</td>
            </tr>
        <?php endif; ?>

        <?php foreach ($reminders as $row): ?>

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
                    <small><?php echo number_format($row['final_amount']); ?></small>
                </td>

                <!-- پلن -->
                <td>
                    <?php echo htmlspecialchars($row['plan_name']); ?>
                    <br>
                    <small><?php echo $row['reminder_days']; ?> روز قبل از سررسید</small>
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

                <!-- جریمه فعال -->
                <td>
                    <?php if ($row['unpaid_penalties'] > 0): ?>
                        <span class="badge danger">
                            <?php echo $row['unpaid_penalties']; ?> جریمه
                        </span>
                    <?php else: ?>
                        <span class="badge success">ندارد</span>
                    <?php endif; ?>
                </td>

                <!-- وضعیت -->
                <td>
                    <span class="badge 
                        <?php
                        if ($row['status'] === 'sent') echo 'success';
                        elseif ($row['status'] === 'pending') echo 'warning';
                        else echo 'secondary';
                        ?>">
                        <?php echo $row['status']; ?>
                    </span>
                </td>

                <!-- عملیات -->
                <td>

                    <?php if ($row['status'] === 'pending'): ?>

                        <form method="post" style="display:inline;">
                            <input type="hidden" name="reminder_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="mark_sent" class="btn small success">
                                ثبت ارسال
                            </button>
                        </form>

                        <form method="post" style="display:inline;">
                            <input type="hidden" name="reminder_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="cancel_reminder" class="btn small warning">
                                لغو
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