<table>
<thead>
<tr>
</tr>
</thead>


</table>
<a href="<?php echo Url::photoGet($photo); ?>">
<img src="<?php echo Url::photoGetThumb($photo); ?>" alt="<?php echo $photo; ?>" />
</a>

<a href="<?php echo Url::to('photo', $photo); ?>">
<img src="<?php echo Url::to('users.get', $user); ?>" alt="<?php echo $photo; ?>" />
</a>

<a href="<?php echo path('photo', array('photo' => $photo)); ?>">
<img src="<?php echo path('photo.thumb', $photo); ?>" alt="<?php echo $photo; ?>" />
</a>

<a href="<?php echo $r->open('photo')->path(array('photo' => $photo)); ?>">
<img src="<?php echo $r->open('photo.thumb')->path($photo); ?>" alt="<?php echo $photo; ?>" />
</a>

<a href="{{ url(photo, photo.user, photo) }}">
<img src="{{ url(photo.thumb, photo.user, photo) }}" alt="{{ photo }}"/>
</a>

<?php
Url::to('users.get', $user);
Url::to('users.get', $user->id);
Url::anchor('users.get');
UrlFactory::open('users.get')->anchor();

Url::getUser($user);

Url::usersGet($user);
?>
<?php echo Url::usersGet($user); ?> 