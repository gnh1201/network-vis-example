digraph {
  node [shape=circle fontsize=16]
  edge [length=400, color=gray, fontcolor=black]

<?php foreach($relations as $rel) { ?>
  <?php echo $rel[0]; ?> -> <?php echo $rel[1]; ?>[label="<?php echo $rel[2]; ?>"];
<?php } ?>

<?php foreach($nodes as $k=>$v) { ?>
  <?php echo $k; ?> [
    label="<?php echo $v; ?>"
<?php if(current(explode("_", $k)) == "pp") { ?>
    fontcolor=white
    color=green
<?php } ?>
<?php if(current(explode("_", $k)) == "dv") { ?>
    fontcolor=white
    color=red
<?php } ?>
  ]
<?php } ?>
}
