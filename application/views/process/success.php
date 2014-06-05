<html>
<head>
<title>CSC564 Grad Project</title>
<link rel="stylesheet" type="text/css" href="<?php echo base_url();?>assets/css/bootstrap.css">
</head>
<body>
<div class="container">
<h3>The Canonical Cover:</h3>

<table class="table">
      <tr>
        <th>Left Attribute(s)</th>
        <th></th>
        <th>Right Attribute(s)</th> 
      </tr>
    <?php 
        foreach($file as $key => $value)
        {
            echo '<tr>';
            echo '<td>'.$key.'</td>';
            echo '<td><span class="glyphicon glyphicon-chevron-right"></span></td>';
            echo '<td>'.implode(', ',$value).'</td>';
            echo '</tr>';
        }
    ?>
</table>

<p><?php echo anchor('process', 'Upload Another File!'); ?></p>
<p>CSC-564 Jack Gregory Graduate Project</p>
</div>
</body>
</html>