<h3>All public repositories</h3>
<div id="sort">
Sort by: <a href="/repositories/">Default</a> &bull; <a href="/repositories/sort/user/">Developer</a> &bull; <a href="/repositories/sort/repository/">Project</a>
</div>
<? if (isset($this->repositories) && count($this->repositories)): ?>
<? foreach ($this->repositories as $column): ?>
<ul class="column">
    <? foreach ($column as $repo): ?>
        <li><a href="/user/<?= $repo['username'] ?>/repository/<?= $repo['name'] ?>"><?= $repo['name'] ?></a> by <a href="/user/<?= $repo['username'] ?>/"><?= $repo['username'] ?></a></li>
    <? endforeach ?>
</ul>
<? endforeach ?>
<? endif ?>
