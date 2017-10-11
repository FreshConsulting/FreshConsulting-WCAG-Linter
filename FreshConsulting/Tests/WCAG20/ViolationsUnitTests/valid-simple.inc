<img src="foo.jpg" alt="">
<a href="/" aria-label="foo">
    <span class="x"></span>
    <span class="y"></span>
    <span class="z"><i></i></span>
</a>
<label for="username">Click me</label>
<input type="text" id="username">
<?php

$foo = 'foo';

// parser doesn't know what is in the variable; it could contain an `alt` attribute
echo '<img src="' . $foo . '">';
?>

<!-- parser doesn't know what is in the variable; it could contain an `alt` attribute -->
<img src="<?php echo $foo; ?>">

