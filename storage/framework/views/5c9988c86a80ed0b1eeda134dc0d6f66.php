<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
</head>
<body>
    <h2>Verify your email address</h2>

    <p>
        Thanks for signing up! Before getting started, please verify your email
        address by clicking the link we just emailed to you.
    </p>

    <?php if(session('message')): ?>
        <p style="color: green;">
            <?php echo e(session('message')); ?>

        </p>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('verification.send')); ?>">
        <?php echo csrf_field(); ?>
        <button type="submit">
            Resend Verification Email
        </button>
    </form>

    <form method="POST" action="<?php echo e(route('logout')); ?>" style="margin-top: 10px;">
        <?php echo csrf_field(); ?>
        <button type="submit">
            Logout
        </button>
    </form>
</body>
</html>
<?php /**PATH E:\Email-verification-service\resources\views/auth/verify-email.blade.php ENDPATH**/ ?>