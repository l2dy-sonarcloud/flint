<p>Update account information below.</p>
<? if (isset($this->error)): ?>
<p class="error">Something failed during the update process please try again.</p>
<? elseif (isset($this->success)): ?>
<p class="success">Account succesfully updated.</p>
<? elseif (isset($this->token)): ?>
<p class="success">Successfully logged in via token. Please reset your password.</p>
<? endif ?>

<form action="/secure/account/" method="post">
    <ol>
        <li><? $this->form_text('email', $this->user['email']) ?></li>
        <li><? $this->form_password('password') ?></li>
        <li><? $this->form_password('password-again') ?></li>
    </ol>
    <p><? $this->form_button('Update') ?></p>
</form>

<p class="special"><a href="/secure/account/delete" class="remove">Delete Account</a></p>
