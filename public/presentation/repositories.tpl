<h3>All public repositories</h3>
<? if (isset($this->repositories) && count($this->repositories)): ?>
<ul>
    <? foreach ($this->repositories as $repo): ?>
        <li><a href="/user/<?= $repo['username'] ?>/repository/<?= $repo['name'] ?>"><?= $repo['name'] ?></a> created by <a href="/user/<?= $repo['username'] ?>/"><?= $repo['username'] ?></a></li>
    <? endforeach ?>
</ul>
<? endif ?>
