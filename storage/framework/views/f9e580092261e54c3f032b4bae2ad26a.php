

<?php $__env->startSection('content'); ?>
    <h1 class="text-2xl font-bold mb-4">Dashboard</h1>
    <p>Welcome, <?php echo e(auth()->user()->name); ?>.</p>

    <ul class="mt-4">
        <li><a href="<?php echo e(route('tokens.index')); ?>" class="text-blue-600">Manage API Tokens</a></li>
    </ul>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Email-verification-service\resources\views\dashboard\index.blade.php ENDPATH**/ ?>