<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('page-title'); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=Inter:wght@400;500;600;700;800&family=Caveat:wght@600;700&display=swap" rel="stylesheet">

    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/scss/app-auth.scss', 'resources/js/app-auth.js']); ?>
    
</head>

<body>
    <a class="sibk-skip-link" href="#main-content">Lewati ke formulir masuk</a>
    <?php echo $__env->yieldContent('body'); ?>

    <?php echo $__env->yieldContent('extra-javascript'); ?>
</body>

</html>
<?php /**PATH D:\Tugas\pepe g\smt 2\pk\sibk\sibk\resources\views/layouts/app-1.blade.php ENDPATH**/ ?>