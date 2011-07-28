<? if (isset($this->success)): ?>
<p>Your new repository, <a href="/user/<?= $this->user['username'] ?>/repository/<?= $this->name ?>"><?= $this->name ?></a>, was successfully created<? if ($this->update): ?> and set to pull in changes on a periodic basis<? endif ?>!</p>
<p>Remember since fossil is an all in one solution you are required to setup repository specific permissions.<? if ($this->private): ?> While your repository won't show up on your public user page you still need to go in and lock it down yourself.<? endif ?> The default user for your new repository is the same as your flint username, however the password is <? if ($this->password == 'sha1'): ?>user set<? else: ?><?= $this->password ?><? endif ?>, we recommend you log in and change this to something else.</p>
<ul>
    <li>Username: <?= $this->user['username'] ?></li>
    <li>Password: <? if ($this->password == 'sha1'): ?>User set<? else: ?><?= $this->password ?><? endif ?></li>
    <li>URL: http(s)://<?= $_SERVER['SERVER_NAME'] ?>/user/<?= $this->user['username'] ?>/repository/<?= $this->name ?></li>
</ul>
<p><a href="/secure/">Return to dashboard</a></p>
<? else: ?>
<? if ((isset($_GET['type']) && $_GET['type'] == 'new') || !isset($_GET['type'])): ?>
<p>Please fill out the form below to create a new repository. If a password is not set a random one will be created for you.</p>

<? if (isset($this->error)): ?>
<p class="error">Something failed during the creation process please try again.</p>
<? endif ?>

<? if (isset($this->max)): ?>
<p class="error">You are currently limited to 10 repositories at a time.</p>
<? endif ?>

<form action="/secure/repository/create/type/new/" method="post">
    <ol>
        <li><? $this->form_text('repository-name') ?></li>
        <li><? $this->form_password('repository-password', 'Repository password <em>(Optional)</em>') ?></li>
        <li>
            <? $this->form_checkbox('private', null, 'Make this repository private? <em>(Prevents repositories from being listed as public only, lock down within fossil still required.)</em>') ?>
        </li>
    </ol>
    <p>
        <? $this->form_button('Create Repository') ?>
        <img class="loader" src="/global/media/loader.gif" alt="Loading..." />
    </p>
</form>
<? endif ?>
<? if (isset($_GET['type']) && $_GET['type'] == 'clone'): ?>
<p>Please fill out the form below to clone an existing repository. If a password is not set a random one will be created for you.</p>

<? if (isset($this->error)): ?>
<p class="error">Something failed during the creation process please try again.</p>
<? endif ?>

<? if (isset($this->max)): ?>
<p class="error">You are currently limited to 10 repositories at a time.</p>
<? endif ?>

<form action="/secure/repository/create/type/clone/" method="post">
    <ol>
        <li><? $this->form_text('repository-name') ?></li>
        <li><? $this->form_password('repository-password', 'Repository password <em>(Optional)</em>') ?></li>
        <li><? $this->form_text('clone-url', null, 'Clone url <em>(http(s)://user:pass@host.tld/repository)</em>') ?></li>
        <li>
            <? $this->form_checkbox('auto-update', null, 'Automatically pull in changes on a periodic basis? <em>(Good for mirrors.)</em>') ?>
        </li>
        <li>
            <? $this->form_checkbox('private', null, 'Make this repository private? <em>(Prevents repositories from being listed as public only, lock down within fossil still required.)</em>') ?>
        </li>
    </ol>
    <p>
        <? $this->form_button('Clone Repository') ?>
        <img class="loader" src="/global/media/loader.gif" alt="Loading..." />
    </p>
</form>
<? endif ?>
<? if (isset($_GET['type']) && $_GET['type'] == 'upload'): ?>
<p>Please fill out the form below to upload an existing repository. A new super-user will be created that matches your flint username and the password you provide below. Limit 8M in size. If your repository is larger than this, create a new empty project and push to it instead.</p>

<? if (isset($this->error)): ?>
<p class="error">Something failed during the creation process please try again.</p>
<? endif ?>

<? if (isset($this->max)): ?>
<p class="error">You are currently limited to 10 repositories at a time.</p>
<? endif ?>

<form action="/secure/repository/create/type/upload/" method="post" enctype="multipart/form-data">
    <ol>
        <li><? $this->form_text('repository-name') ?></li>
        <li><? $this->form_password('repository-password') ?></li>
        <li><? $this->form_file('upload') ?></li>
        <li>
            <? $this->form_checkbox('private', null, 'Make this repository private? <em>(Prevents repositories from being listed as public only, lock down within fossil still required.)</em>') ?>
        </li>
    </ol>
    <p>
        <? $this->form_button('Upload Repository') ?>
        <img class="loader" src="/global/media/loader.gif" alt="Loading..." />
    </p>
</form>
<? endif ?>
<? endif ?>
