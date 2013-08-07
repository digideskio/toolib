
<h1>Hello world</h1>

This is index template requested by <?php print($request->getPath()); ?> 
	with "<?php echo $request->getMethod()?>" method
	
<h2> Created objects <a href="<?php echo $url->open('item.generate')->path(); ?>">create new</a></h2>
<ul>
<?php foreach($_SESSION as $name => $time): ?>
<li><strong><a href="<?php echo $url->open('item.get')->path(array('key' => $name)); ?>"><?php toolib\html_echo($name); ?></a></strong>
 <?php toolib\html_echo($time->format(DATE_ATOM)); ?>
<?php endforeach; ?>
</ul>

<hr />
<?php if (count($request->getQuery())): ?>
<h2> Query parameters </h2>
<ul>
<?php foreach($request->getQuery() as $name => $value): ?>
<dt><?php toolib\html_echo($name); ?></dt>
<dl><?php toolib\html_echo($value); ?></dl>
<?php endforeach; ?>
</ul>
<?php endif ?>
