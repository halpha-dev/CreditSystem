<?php
/**
 * KYC Requests List - Admin
 */

use CreditSystem\Includes\Services\KycService;
use CreditSystem\Includes\Services\CreditService;
use CreditSystem\Includes\Services\InstallmentService;
use CreditSystem\Includes\Services\MerchantService;
use CreditSystem\Includes\Security\PermissionPolicy;

PermissionPolicy::adminOnly();

$kycService = new KycService();
$creditService = new CreditService();
$installmentService = new InstallmentService();
$merchantService = new MerchantService();

// پارامترها برای pagination
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$kycRequests = $kycService->getPendingList($perPage, $offset);

?>

<div class="cs-admin-kyc">

    <?php include CS_UI_ADMIN_PARTIALS . '/header.php'; ?>

    <h1>درخواست‌های KYC در انتظار</h1>

    <?php if (empty($kycRequests)) : ?>
        <p>هیچ درخواست فعالی وجود ندارد.</p>
    <?php else : ?>
        <table class="cs-table cs-table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>کاربر</th>
                    <th>نوع</th>
                    <th>ایمیل</th>
                    <th>تلفن</th>
                    <th>پلن اقساط</th>
                    <th>وضعیت حساب اعتباری</th>
                    <th>فروشنده مرتبط</th>
                    <th>تاریخ ارسال</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kycRequests as $req) :
                    $user = $req->getUser();
                    $credit = $creditService->getByUserId($user->id);
                    $installmentPlan = $credit ? $installmentService->getPlanByCreditId($credit->id) : null;
                    $merchant = $user->isMerchant() ? $merchantService->getByUserId($user->id) : null;
                ?>
                    <tr>
                        <td><?php echo esc_html($req->id); ?></td>
                        <td><?php echo esc_html($user->getFullName()); ?></td>
                        <td><?php echo esc_html($user->role); ?></td>
                        <td><?php echo esc_html($user->email); ?></td>
                        <td><?php echo esc_html($user->phone); ?></td>
                        <td>
                            <?php
                                if ($installmentPlan) {
                                    echo esc_html($installmentPlan->months . ' ماه - مبلغ هر قسط: ' . number_format_i18n($installmentPlan->monthly_amount));
                                } else {
                                    echo '—';
                                }
                            ?>
                        </td>
                        <td>
                            <?php echo $credit ? esc_html($credit->getStatus()) : 'ندارد'; ?>
                        </td>
                        <td>
                            <?php echo $merchant ? esc_html($merchant->store_name) : '—'; ?>
                        </td>
                        <td><?php echo esc_html(date_i18n('Y/m/d H:i', strtotime($req->created_at))); ?></td>
                        <td>
                            <a href="admin.php?page=creditsystem-kyc-view&id=<?php echo esc_attr($req->id); ?>" class="button">
                                مشاهده 
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- pagination -->
        <?php
        $totalRequests = $kycService->getPendingCount();
        $totalPages = ceil($totalRequests / $perPage);
        if ($totalPages > 1) :
        ?>
            <div class="cs-pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                    <a class="<?php echo $i === $page ? 'active' : ''; ?>" href="?page=creditsystem-kyc&paged=<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php include CS_UI_ADMIN_PARTIALS . '/footer.php'; ?>

</div>