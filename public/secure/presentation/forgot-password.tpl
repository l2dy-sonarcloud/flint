<p>Please fill out the form below to reset your password.</p>

<? if (isset($this->error)): ?>
<p class="error">Forgot password email was not sent.</p>
<? endif ?>

<form action="/secure/forgot-password/" method="post">
    <ol>
        <li><? $this->form_text('email') ?></li>
    </ol>
    <p><? $this->form_button('Recover') ?></p>
</form>
