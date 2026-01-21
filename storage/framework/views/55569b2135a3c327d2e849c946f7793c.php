

<?php $__env->startSection('content'); ?>
    <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-xl font-bold mb-4">Login</h1>

        <form method="POST" action="<?php echo e(route('login.attempt')); ?>">
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
            <div class="mb-4">
                <label class="block mb-1">Password</label>
                <input type="password" name="password" class="border p-2 w-full <?php echo e($errors->has('password') ? 'border-red-500' : ''); ?>" required>
                <?php $__errorArgs = ['password'];
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
            <div class="mb-4">
                <label class="inline-flex items-center"><input type="checkbox" name="remember" class="mr-2"> Remember me</label>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <button class="bg-blue-600 text-white px-4 py-2">Login</button>
                </div>
                <div>
                    <a href="<?php echo e(route('password.request')); ?>" class="text-sm text-blue-600">Forgot password?</a>
                    <span class="mx-2">|</span>
                    <a href="<?php echo e(route('register')); ?>" class="text-sm text-blue-600">Register</a>
                </div>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Email-verification-service\resources\views\auth\login.blade.php ENDPATH**/ ?>