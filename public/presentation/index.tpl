<? if (isset($this->notfound)): ?>
<h1>404 File Not Found</h1>
<? elseif (isset($this->user)): ?>
<h3><?= $this->user['username'] ?>'<? if (substr($this->user['username'], -1, 1) != 's'): ?>s<? endif ?> Public Repositories</h3>
<? if (count($this->public)): ?>
    <ul>
    <? foreach ($this->public as $repo): ?>
        <li><a href="/user/<?= $this->user['username'] ?>/repository/<?= $repo['name'] ?>"><?= $repo['name'] ?></a></li>
    <? endforeach ?>
    </ul>
<? else: ?>
<p>No public repositories found.</p>
<? endif ?>
<? else: ?>
<div id="content">
    <h2>Why choose <strong>Flint</strong> for your fossil hosting needs?</h2>
    <ul id="features">
        <li class="free">
            <strong>Free</strong>
        </li>
        <li class="clouds">
            <strong>Backups</strong>
        </li>
        <li class="lock">
            <strong>Security</strong>
        </li>
        <li class="help">
            <strong>Help</strong>
        </li>
    </ul>
</div>
<div id="side">
    <h2>Create an account <strong>now</strong>!</h2>
    <form action="https://<?= $_SERVER['SERVER_NAME'] ?>/secure/create-account/" method="post">
        <ol>
            <li><? $this->form_text('first-name') ?></li>
            <li><? $this->form_text('last-name') ?></li>
            <li><? $this->form_text('email') ?></li>
            <li><? $this->form_text('username') ?></li>
            <li><? $this->form_password('password') ?></li>
            <li><? $this->form_password('password-again') ?></li>
        </ol>
        <p><? $this->form_button('Create Account') ?></p>
    </form>
</div>
<div class="clear"></div>
<? endif ?>
