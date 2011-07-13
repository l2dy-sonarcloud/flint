<p>Please fill out the form below to log in to your account.</p>

<? if (isset($this->auth)): ?>
<p class="error">You must be logged in to do that.</p>
<? elseif (isset($this->invalid)): ?>
<p class="error">That token is invalid or expired, <a href="/secure/forgot-password/">request a new one?</a></p>
<? elseif (isset($this->forgot)): ?>
<p class="success">Reset password email sent.</p>
<? endif ?>

<form action="/secure/log-in/" method="post">
    <ol>
        <li><? $this->form_text('username') ?></li>
        <li><? $this->form_password('password') ?></li>
    </ol>
    <p><? $this->form_button('Log In') ?></p>
</form>

<p><a href="/secure/forgot-password/">Forgot your password?</a></p>
