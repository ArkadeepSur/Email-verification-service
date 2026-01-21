

<?php $__env->startSection('content'); ?>
    <h1 class="text-2xl font-bold mb-4">API Tokens</h1>

    <?php if(session('token_plain')): ?>
        <div class="p-4 bg-green-100 mb-4">New token: <code><?php echo e(session('token_plain')); ?></code> — copy it now, it will not be shown again.</div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('tokens.store')); ?>" class="mb-6">
        <?php echo csrf_field(); ?>
        <input type="text" name="name" placeholder="Token name" class="border p-2" required>
        <button class="bg-blue-600 text-white px-3 py-2">Create Token</button>
    </form>

    <table class="w-full bg-white shadow rounded">
        <thead><tr class="text-left"><th class="p-3">Name</th><th class="p-3">Last Used</th><th class="p-3">Actions</th></tr></thead>
        <tbody>
            <?php $__currentLoopData = $tokens; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $token): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="border-t"><td class="p-3"><?php echo e($token->name); ?></td><td class="p-3"><?php echo e($token->last_used_at); ?></td><td class="p-3">
                    <form method="POST" action="<?php echo e(route('tokens.destroy', $token->id)); ?>" onsubmit="return confirm('Revoke token?');">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button class="bg-red-500 text-white px-3 py-1">Revoke</button>
                    </form>
                </td></tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Email-verification-service\resources\views\dashboard\tokens.blade.php ENDPATH**/ ?>