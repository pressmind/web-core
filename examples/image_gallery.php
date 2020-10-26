<?php
error_reporting(-1);
ini_set('display_errors', true);
use Pressmind\ORM\Object\MediaObject;

require_once dirname(__DIR__) . '/bootstrap.php';
$mediaObject = new MediaObject(intval($_GET['id']));
/** @var \Custom\MediaType\Reise $data */
$data = $mediaObject->data[0];
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <style>
        pre {
            max-height: 400px;
            overflow: auto;
        }
    </style>
    <title>An example page</title>
</head>
<body>
<div class="container">
    <?php foreach ($data->bilder_default as $image) {?>
        <pre><?php echo($image->getUri('thumbnail'));?></pre>
        <img src="<?php echo($image->getUri('thumbnail'));?>">
        <pre><?php echo($image->getUri('teaser'));?></pre>
        <img src="<?php echo($image->getUri('teaser'));?>">
    <?php }?>
</div>
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
</body>
</html>
