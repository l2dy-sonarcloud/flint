<? if (isset($this->new)): ?>
<p class="success">Your account was created and you are now logged in.</p>
<? endif ?>

<? if (isset($this->pull)): ?>
<p class="success"><?= $this->pull ?> was successfully updated.</p>
<? endif ?>

<h3>Public Repositories</h3>
<? if (count($this->public)): ?>
    <ul>
    <? foreach ($this->public as $repo): ?>
        <li><a href="/user/<?= $this->user['username'] ?>/repository/<?= $repo['name'] ?>"><?= $repo['name'] ?></a> - <? if ($repo['cloned']): ?><a href="/secure/repository/pull/id/<?= $repo['id'] ?>">Pull</a> | <? endif ?><a href="/secure/repository/edit/id/<?= $repo['id'] ?>">Edit</a> | <a href="/secure/repository/remove/id/<?= $repo['id'] ?>" class="remove">Remove</a> | <a href="/secure/repository/help_clone/id/<?= $repo['id'] ?>">How to Clone</a></li>
    <? endforeach ?>
    </ul>
<? else: ?>
<p>No public repositories</p>
<? endif ?>
<h3>Private Repositories</h3>
<? if (count($this->private)): ?>
    <ul>
    <? foreach ($this->private as $repo): ?>
        <li><a href="/user/<?= $this->user['username'] ?>/repository/<?= $repo['name'] ?>"><?= $repo['name'] ?></a> - <? if ($repo['cloned']): ?><a href="/secure/repository/pull/id/<?= $repo['id'] ?>">Pull</a> | <? endif ?><a href="/secure/repository/edit/id/<?= $repo['id'] ?>">Edit</a> | <a href="/secure/repository/remove/id/<?= $repo['id'] ?>" class="remove">Remove</a> | <a href="/secure/repository/help_clone/id/<?= $repo['id'] ?>">How to Clone</a></li>
    <? endforeach ?>
    </ul>
<? else: ?>
<p>No private repositories</p>
<? endif ?>
