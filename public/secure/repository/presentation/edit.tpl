<p>Please fill out the form below to update your repositories settings. Changing the clone url causes a pull.</p>

<? if (isset($this->error)): ?>
<p class="error">Something failed during the update process please try again.
<? if (isset($this->errormsg)): ?>
<pre><?= $this->errormsg ?></pre>
<? endif ?>
</p>
<? endif ?>

<form action="<?= $_SERVER['REQUEST_URI'] ?>" method="post">
    <ol>
        <li><? $this->form_password('repository-password', 'Reset repository password') ?></li>
        <li><? $this->form_text('clone-url', $this->repo['clone-url'], 'Clone url <em>(http(s)://user:pass@host.tld/repository)</em>') ?></li>
        <li>
            <? $this->form_checkbox('auto-update', $this->repo['auto_update'], 'Automatically pull in changes on a periodic basis? <em>(Good for mirrors.)</em>') ?>
        </li>
        <li>
            <? $this->form_checkbox('private', $this->repo['private'], 'Make this repository private? <em>(Prevents repositories from being listed as public only, lock down within fossil still required.)</em>') ?>
        </li>
    </ol>
    <p>
        <? $this->form_button('Update Repository') ?>   <? $this->form_button('Download File') ?> 
    </p>
</form>
