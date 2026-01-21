

<?php $__env->startSection('content'); ?>
    <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-xl font-bold mb-4">Reset Password</h1>

        <?php if(session('status')): ?>
            <div class="mb-4 text-green-600"><?php echo e(session('status')); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('password.email')); ?>">
            <?php echo csrf_field(); ?>
            <div class="mb-4">
                <label class="block mb-1">Email</label>
                <input type="email" name="email" value="<?php echo e(old('email')); ?>" class="border p-2 w-full <?php echo e($errors->has('email') ? 'border-red-500' : ''); ?>" required>
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <button class="bg-blue-600 text-white px-4 py-2">Send reset link</button>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Email-verification-service\resources\views\auth\passwords\email.blade.php ENDPATH**/ ?>