<p>Please fill out the form below to create a new account.</p>

<? if (isset($this->error)): ?>
<p class="error">Something failed during the creation process please try again.</p>
<? endif ?>

<form action="/secure/create-account/" method="post">
    <ol>
        <li><? $this->form_text('email') ?></li>
        <li><? $this->form_text('username') ?></li>
        <li><? $this->form_password('password') ?></li>
        <li><? $this->form_password('password-again') ?></li>
    </ol>
    <p><? $this->form_button('Create Account') ?></p>
</form>
