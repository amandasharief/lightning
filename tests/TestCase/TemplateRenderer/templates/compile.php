
<?php
$i = 0;
$records = ['one','two','three'];
?>
@for ($i = 1; $i <= 10; $i++)
    <?= $i ?>
@endfor

@foreach (['apples','orange','kiwi'] as $fruit)
    name:{{ $fruit }}
@endforeach

@while ($i <= 10)
    <?php
        echo $i;
$i++;
?>
@endwhile

@if (count($records) > 1)
    <?php echo 'You have many records' ?>
@elseif (count($records) === 1)
    <?php echo 'You have 1 record' ?>
@else
    <?php echo 'You have no records' ?>
@endif

