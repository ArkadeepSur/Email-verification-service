<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo e(config('app.name', 'CatchAll Verifier')); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800">
    <div class="bg-white shadow-sm">
        <div class="container mx-auto p-4 flex justify-between items-center">
            <div><a href="/" class="font-bold"><?php echo e(config('app.name', 'CatchAll Verifier')); ?></a></div>
            <div>
                <?php if(auth()->guard()->check()): ?>
                    <span class="mr-4">Hello, <?php echo e(auth()->user()->name); ?></span>
                    <form method="POST" action="<?php echo e(route('logout')); ?>" class="inline">
                        <?php echo csrf_field(); ?>
                        <button class="bg-red-500 text-white px-3 py-1">Logout</button>
                    </form>
                <?php else: ?>
                    <a href="<?php echo e(route('login')); ?>" class="text-blue-600">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="container mx-auto p-6">
        
        <div class="mb-6 space-y-2">
            <?php if(session('success')): ?>
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded">
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?>
            <?php if(session('error')): ?>
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded">
                    <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?>
            <?php if(session('status')): ?>
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded">
                    <?php echo e(session('status')); ?>

                </div>
            <?php endif; ?>
            <?php if($errors->any()): ?>
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded">
                    <strong>There were some problems with your submission:</strong>
                    <ul class="mt-2 list-disc list-inside">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <?php echo $__env->yieldContent('content'); ?>
    </div>
</body>
</html>
<?php /**PATH E:\Email-verification-service\resources\views\layouts\app.blade.php ENDPATH**/ ?>